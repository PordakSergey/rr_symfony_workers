<?php

namespace Rr\Bundle\Workers\Tests\Temporal;

use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Jobs\Response\JobResponse;
use Rr\Bundle\Workers\Temporal\Services\JobsDispatcher\TemporalJobDispatcher;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerPoolWorkflow;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Workflow\WorkflowExecution;
use Temporal\Workflow\WorkflowRunInterface;

final class TemporalJobDispatcherTest extends TestCase
{
    /** @var array{0: string, 1: WorkflowOptions}|null Аргументы newWorkflowStub() */
    private ?array $stub = null;

    /** @var array|null Аргументы start() без самого стаба */
    private ?array $started = null;

    public function testDispatchStartsMessengerWorkflowWithNormalizedCommand(): void
    {
        $client = $this->client($this->workflowRun('wf-1'));

        $response = $this->dispatcher($client)->dispatch(new SendEmail(42));

        self::assertSame(MessengerWorkflow::class, $this->stub[0]);
        self::assertSame([SendEmail::class, ['id' => 42]], $this->started);
        self::assertSame('wf-1', $response->getId());
        self::assertNull($response->getResult(), 'без returnResult результат не запрашивается');
    }

    public function testDispatchWaitsForTheResultOnlyWhenAsked(): void
    {
        $run = $this->createMock(WorkflowRunInterface::class);
        $run->method('getExecution')->willReturn(new WorkflowExecution('wf-1'));
        $run->expects(self::once())->method('getResult')->willReturn('done');

        $response = $this->dispatcher($this->client($run))->dispatch(new SendEmail(42), returnResult: true);

        self::assertSame('done', $response->getResult());
    }

    public function testDispatchNeverWaitsForTheResultByDefault(): void
    {
        $run = $this->createMock(WorkflowRunInterface::class);
        $run->method('getExecution')->willReturn(new WorkflowExecution('wf-1'));
        $run->expects(self::never())->method('getResult');

        $this->dispatcher($this->client($run))->dispatch(new SendEmail(42));
    }

    public function testDispatchUsesDefaultTaskQueueAndTagPrefixedWorkflowId(): void
    {
        $this->dispatcher($this->client($this->workflowRun('wf-1')))->dispatch(new SendEmail(42));

        self::assertSame('taskQueue', $this->stub[1]->taskQueue);
        self::assertStringStartsWith('messenger-', $this->stub[1]->workflowId);
    }

    public function testDispatchOverridesQueueAndTag(): void
    {
        $this->dispatcher($this->client($this->workflowRun('wf-1')))
            ->dispatch(new SendEmail(42), tag: 'reports', queue: 'heavy');

        self::assertSame('heavy', $this->stub[1]->taskQueue);
        self::assertStringStartsWith('reports-', $this->stub[1]->workflowId);
    }

    public function testWorkflowIdIsUniquePerDispatch(): void
    {
        $dispatcher = $this->dispatcher($this->client($this->workflowRun('wf-1'), invocations: 2));

        $dispatcher->dispatch(new SendEmail(1));
        $first = $this->stub[1]->workflowId;
        $dispatcher->dispatch(new SendEmail(2));

        self::assertNotSame($first, $this->stub[1]->workflowId);
    }

    public function testDispatchPoolSendsEveryCommandAsClassPayloadPair(): void
    {
        $client = $this->client($this->workflowRun('pool-1'));

        $this->dispatcher($client)->dispatchPool([new SendEmail(1), new SendEmail(2)]);

        self::assertSame(MessengerPoolWorkflow::class, $this->stub[0]);
        self::assertSame([[
            ['class' => SendEmail::class, 'payload' => ['id' => 1]],
            ['class' => SendEmail::class, 'payload' => ['id' => 2]],
        ]], $this->started);
    }

    public function testDispatchPoolReturnsNothingWithoutReturnResult(): void
    {
        $run = $this->createMock(WorkflowRunInterface::class);
        $run->method('getExecution')->willReturn(new WorkflowExecution('pool-1'));
        $run->expects(self::never())->method('getResult');

        self::assertSame([], $this->dispatcher($this->client($run))->dispatchPool([new SendEmail(1)]));
    }

    public function testDispatchPoolReturnsOneResponsePerResult(): void
    {
        $run = $this->workflowRun('pool-1', ['first', 'second']);

        $responses = $this->dispatcher($this->client($run))
            ->dispatchPool([new SendEmail(1), new SendEmail(2)], returnResult: true);

        self::assertContainsOnlyInstancesOf(JobResponse::class, $responses);
        self::assertSame(['pool-1', 'pool-1'], array_map(fn(JobResponse $r) => $r->getId(), $responses));
        self::assertSame(['first', 'second'], array_map(fn(JobResponse $r) => $r->getResult(), $responses));
    }

    public function testDispatchPoolConvertsNestedStdClassResultsToArrays(): void
    {
        $result = (object)[
            'ok' => true,
            'items' => [(object)['id' => 1], (object)['id' => 2]],
            'nested' => (object)['deep' => (object)['value' => 'x']],
        ];

        $responses = $this->dispatcher($this->client($this->workflowRun('pool-1', [$result])))
            ->dispatchPool([new SendEmail(1)], returnResult: true);

        self::assertSame([
            'ok' => true,
            'items' => [['id' => 1], ['id' => 2]],
            'nested' => ['deep' => ['value' => 'x']],
        ], $responses[0]->getResult());
    }

    /**
     * @param WorkflowRunInterface $run
     * @param int $invocations Сколько раз ждём newWorkflowStub()/start()
     * @return WorkflowClientInterface
     */
    private function client(WorkflowRunInterface $run, int $invocations = 1): WorkflowClientInterface
    {
        $stub = new \stdClass();

        $client = $this->createMock(WorkflowClientInterface::class);
        $client->expects(self::exactly($invocations))
            ->method('newWorkflowStub')
            ->willReturnCallback(function (string $class, WorkflowOptions $options) use (&$stub): object {
                $this->stub = [$class, $options];

                return $stub;
            });
        $client->expects(self::exactly($invocations))
            ->method('start')
            ->willReturnCallback(function (object $workflow, ...$args) use ($run, $stub): WorkflowRunInterface {
                self::assertSame($stub, $workflow);
                $this->started = $args;

                return $run;
            });

        return $client;
    }

    /**
     * @param string $id
     * @param mixed $result
     * @return WorkflowRunInterface
     */
    private function workflowRun(string $id, mixed $result = null): WorkflowRunInterface
    {
        $run = $this->createMock(WorkflowRunInterface::class);
        $run->method('getExecution')->willReturn(new WorkflowExecution($id));
        $run->method('getResult')->willReturn($result);

        return $run;
    }

    /**
     * @param WorkflowClientInterface $client
     * @return TemporalJobDispatcher
     */
    private function dispatcher(WorkflowClientInterface $client): TemporalJobDispatcher
    {
        return new TemporalJobDispatcher($client, new Serializer([new ObjectNormalizer()]), 'taskQueue');
    }
}

final class SendEmail
{
    public function __construct(public int $id)
    {
    }
}