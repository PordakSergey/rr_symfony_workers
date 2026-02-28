<?php

namespace Rr\Bundle\Workers\Temporal\Services\JobsDispatcher;

use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Temporal\Client\WorkflowClientInterface;
use Temporal\Client\WorkflowOptions;

class TemporalJobDispatcher implements JobDispatcherInterface
{
    /**
     * @param WorkflowClientInterface $client
     * @param NormalizerInterface $serializer
     */
    public function __construct(
        protected WorkflowClientInterface $client,
        protected NormalizerInterface  $serializer,
    )
    {
    }

    /**
     * @param object $command
     * @return string|null
     * @throws ExceptionInterface
     */
    public function dispatch(object $command): ?string
    {
        $workflow = $this->client->newWorkflowStub(
            MessengerWorkflow::class,
            WorkflowOptions::new()
                ->withTaskQueue('taskQueue')
                ->withWorkflowId('messenger-'.uniqid())
        );

        $payload = $this->serializer->normalize($command, 'json');

        $handle = $this->client->start($workflow, $command::class, $payload);
        return $handle->getExecution()->getID();
    }
}