<?php

namespace Rr\Bundle\Workers\Temporal\Services\JobsDispatcer;

use Rr\Bundle\Workers\Contracts\Jobs\JobsDispatcherInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Temporal\Client\WorkflowOptions;

#[Autoconfigure(tags: ['jobs.dispatcher.temporal'])]
class TemporalJobsDispatcher implements JobsDispatcherInterface
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