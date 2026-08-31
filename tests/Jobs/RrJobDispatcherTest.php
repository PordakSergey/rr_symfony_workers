<?php

namespace Rr\Bundle\Workers\Tests\Jobs;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Rr\Bundle\Workers\Jobs\Services\JobsDispatcher\RrJobDispatcher;
use Spiral\RoadRunner\Jobs\JobsInterface;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\Jobs\OptionsInterface;
use Spiral\RoadRunner\Jobs\QueueInterface;
use Spiral\RoadRunner\Jobs\Task\PreparedTask;
use Spiral\RoadRunner\Jobs\Task\PreparedTaskInterface;
use Spiral\RoadRunner\Jobs\Task\QueuedTaskInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

final class RrJobDispatcherTest extends TestCase
{
    private const QUEUES = [
        'default' => [],
        'flights.storage' => ['delay' => 0, 'priority' => 10, 'auto_ack' => false],
        'slow' => ['delay' => 60, 'priority' => 1, 'auto_ack' => true],
    ];

    public function testPushSendsSerializedCommandUnderItsClassName(): void
    {
        $queue = $this->queue();
        $queue->expects(self::once())
            ->method('create')
            ->with(SendEmail::class, '{"id":42}', null)
            ->willReturn(new PreparedTask(SendEmail::class, '{"id":42}'));

        $id = $this->dispatcher($this->jobs($queue))->push(new SendEmail(42));

        self::assertSame('task-1', $id);
    }

    /**
     * @param string|null $queue Аргумент push()
     * @param string $expectedName Пайплайн, к которому подключились
     * @param array|null $expectedOptions [delay, priority, autoAck] или null, если опций нет
     */
    #[DataProvider('queueCases')]
    public function testPushConnectsToTheQueueWithItsConfiguredOptions(
        ?string $queue,
        string  $expectedName,
        ?array  $expectedOptions,
    ): void
    {
        $connected = null;

        $jobs = $this->createMock(JobsInterface::class);
        $jobs->expects(self::once())
            ->method('connect')
            ->willReturnCallback(function (string $name, ?OptionsInterface $options) use (&$connected): QueueInterface {
                $connected = [$name, $options];

                return $this->queue();
            });

        $this->dispatcher($jobs)->push(new SendEmail(42), $queue);

        [$name, $options] = $connected;
        self::assertSame($expectedName, $name);
        self::assertSame(
            $expectedOptions,
            $options === null ? null : [$options->getDelay(), $options->getPriority(), $options->getAutoAck()],
        );
    }

    /**
     * @return array<string, array{0: string|null, 1: string, 2: array|null}>
     */
    public static function queueCases(): array
    {
        return [
            'null → default_queue, у него опций нет' => [null, 'default', null],
            'объявленная очередь → её опции' => ['flights.storage', 'flights.storage', [0, 10, false]],
            'вторая очередь → свои опции' => ['slow', 'slow', [60, 1, true]],
            'необъявленная очередь → без опций' => ['adhoc', 'adhoc', null],
        ];
    }

    public function testPushPassesPerCallOptionsThrough(): void
    {
        $options = (new Options())->withDelay(5);

        $queue = $this->queue();
        $queue->expects(self::once())
            ->method('create')
            ->with(self::anything(), self::anything(), self::identicalTo($options))
            ->willReturn(new PreparedTask(SendEmail::class, '{"id":42}', $options));

        $this->dispatcher($this->jobs($queue, 'slow'))->push(new SendEmail(42), 'slow', $options);
    }

    public function testPushManyDispatchesEveryCommandThroughOnePipeline(): void
    {
        $queue = $this->queue();
        $queue->expects(self::exactly(2))->method('create');
        $queue->expects(self::once())
            ->method('dispatchMany')
            ->willReturnCallback(function (PreparedTaskInterface ...$tasks): array {
                self::assertSame([SendEmail::class, SendEmail::class], array_map(fn($t) => $t->getName(), $tasks));
                self::assertSame(['{"id":1}', '{"id":2}'], array_map(fn($t) => $t->getPayload(), $tasks));

                return [$this->queuedTask('task-1'), $this->queuedTask('task-2')];
            });

        $ids = $this->dispatcher($this->jobs($queue, 'flights.storage'))
            ->pushMany([new SendEmail(1), new SendEmail(2)], 'flights.storage');

        self::assertSame(['task-1', 'task-2'], $ids);
    }

    public function testPushManyOfNothingTouchesNoTask(): void
    {
        $queue = $this->queue();
        $queue->expects(self::never())->method('create');
        $queue->expects(self::once())->method('dispatchMany')->willReturn([]);

        self::assertSame([], $this->dispatcher($this->jobs($queue))->pushMany([]));
    }

    /**
     * @param JobsInterface $jobs
     * @return RrJobDispatcher
     */
    private function dispatcher(JobsInterface $jobs): RrJobDispatcher
    {
        return new RrJobDispatcher(
            $jobs,
            new Serializer([new ObjectNormalizer()], [new JsonEncoder()]),
            'default',
            self::QUEUES,
        );
    }

    /**
     * @param QueueInterface $queue
     * @param string $expectedName Пайплайн, к которому ждём подключения
     * @return JobsInterface
     */
    private function jobs(QueueInterface $queue, string $expectedName = 'default'): JobsInterface
    {
        $jobs = $this->createMock(JobsInterface::class);
        $jobs->expects(self::once())
            ->method('connect')
            ->with($expectedName, self::anything())
            ->willReturn($queue);

        return $jobs;
    }

    /**
     * Пайплайн, который создаёт настоящие PreparedTask и отдаёт одну задачу с id 'task-1'.
     *
     * @return QueueInterface&MockObject
     */
    private function queue(): QueueInterface&MockObject
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('create')->willReturnCallback(
            fn(string $name, string $payload, ?OptionsInterface $options = null): PreparedTaskInterface
                => new PreparedTask($name, $payload, $options)
        );
        $queue->method('dispatch')->willReturn($this->queuedTask('task-1'));

        return $queue;
    }

    /**
     * @param string $id
     * @return QueuedTaskInterface
     */
    private function queuedTask(string $id): QueuedTaskInterface
    {
        $task = $this->createMock(QueuedTaskInterface::class);
        $task->method('getId')->willReturn($id);

        return $task;
    }
}

final class SendEmail
{
    public function __construct(public int $id)
    {
    }
}