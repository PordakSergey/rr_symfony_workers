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

class TemporalJobDispatcher implements JobDispatcherInterface
{
    /**
     * @param WorkflowClientInterface $client
     * @param NormalizerInterface $serializer
     * @param string $taskQueue From rr_bundle.temporal.default_queue
     */
    public function __construct(
        protected WorkflowClientInterface $client,
        protected NormalizerInterface     $serializer,
        protected string                  $taskQueue = TemporalWorker::DEFAULT_TASK_QUEUE,
    )
    {
    }

    /**
     * @param object $command
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue
     * @return JobResponse
     * @throws ExceptionInterface
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): JobResponse
    {
        $workflow = $this->client->newWorkflowStub(
            MessengerWorkflow::class,
            WorkflowOptions::new()
                ->withTaskQueue($queue ?? $this->taskQueue)
                ->withWorkflowId($tag. '-' . uniqid())
        );

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
                ->withWorkflowExecutionTimeout(CarbonInterval::minutes(10))
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