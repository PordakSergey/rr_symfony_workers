<?php

namespace Rr\Bundle\Workers\Temporal\Services\JobsDispatcher;

use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Temporal\Client\WorkflowOptions;

class TemporalJobDispatcher implements JobDispatcherInterface
{
    /**
     * @param TemporalClientInterface $client
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected TemporalClientInterface $client,
        protected SerializerInterface $serializer,
    )
    {
    }

    /**
     * @param object $command
     * @return string
     * @throws ExceptionInterface
     */
    public function dispatch(object $command): ?string
    {
        $workflow = $this->client->getClient()->newWorkflowStub(
            MessengerWorkflow::class,
            WorkflowOptions::new()
                ->withTaskQueue('taskQueue')
                ->withWorkflowId('messenger-'.uniqid())
        );

        $payload = json_decode($this->serializer->serialize($command, 'json'), true);

        $handle = $this->client->getClient()->start($workflow, $command::class, $payload);
        return $handle->getExecution()->getID();
    }
}