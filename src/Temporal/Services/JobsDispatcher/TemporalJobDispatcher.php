<?php

namespace Rr\Bundle\Workers\Temporal\Services\JobsDispatcher;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Jobs\Response\JobResponse;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerPoolWorkflow;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Rr\Bundle\Workers\Workers\TemporalWorker;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;
use Temporal\Common\RetryOptions;

class TemporalJobDispatcher implements JobDispatcherInterface
{
    /**
     * @param WorkflowClientInterface $client
     * @param NormalizerInterface $serializer
     * @param string $taskQueue From rr_bundle.temporal.default_queue
     * @param bool $debug kernel.debug: одна попытка workflow, чтобы остановка на брейкпоинте не запускала вторую
     */
    public function __construct(
        protected WorkflowClientInterface $client,
        protected NormalizerInterface     $serializer,
        protected string                  $taskQueue = TemporalWorker::DEFAULT_TASK_QUEUE,
        protected bool                    $debug = false,
    )
    {
    }

    /**
     * @param object $command
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue
     * @param int $delayMs Отложенный старт workflow (Messenger DelayStamp), 0 — сразу
     * @return JobResponse
     * @throws ExceptionInterface
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null, int $delayMs = 0): JobResponse
    {
        $options = WorkflowOptions::new()
            ->withTaskQueue($queue ?? $this->taskQueue)
            ->withWorkflowId($tag. '-' . uniqid())
            ->withRetryOptions($this->retryOptions());

        if ($delayMs > 0) {
            $options = $options->withWorkflowStartDelay(CarbonInterval::milliseconds($delayMs)->cascade());
        }

        $workflow = $this->client->newWorkflowStub(MessengerWorkflow::class, $options);

        $payload = $this->serializer->normalize($command, 'json');

        $handle = $this->client->start($workflow, $command::class, $payload);

        $result = $returnResult ? $handle->getResult() : null;
        $id = $handle->getExecution()->getID();

        return new JobResponse($id, $result);
    }

    /**
     * @param array $commands
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue
     * @return array|JobResponse[]
     * @throws ExceptionInterface
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): array
    {
        $workflow = $this->client->newWorkflowStub(
            MessengerPoolWorkflow::class,
            WorkflowOptions::new()
                ->withTaskQueue($queue ?? $this->taskQueue)
                ->withWorkflowId($tag .'-'. uniqid())
                ->withRetryOptions($this->retryOptions())
                ->withWorkflowExecutionTimeout(CarbonInterval::minutes($this->debug ? 120 : 10))
        );

        $request = [];
        foreach ($commands as $command) {
            $request[] = [
                'class' => $command::class,
                'payload' => $this->serializer->normalize($command, 'json')
            ];
        }

        $handle = $this->client->start($workflow, $request);

        if ($returnResult) {
            $results = [];
            foreach ($handle->getResult() as $result) {
                $results[] = new JobResponse(
                    $handle->getExecution()->getID(),
                    $this->deepToArray($result)
                );
            }
        }

        return $results ?? [];
    }

    /**
     * @return RetryOptions|null В debug — одна попытка, иначе дефолт temporal (workflow не ретраится)
     */
    private function retryOptions(): ?RetryOptions
    {
        return $this->debug ? RetryOptions::new()->withMaximumAttempts(1) : null;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function deepToArray(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            return array_map(fn(mixed $item) => $this->deepToArray($item), $value);
        }

        return $value;
    }
}