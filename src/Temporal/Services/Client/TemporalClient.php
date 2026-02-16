<?php

namespace Rr\Bundle\Workers\Temporal\Services\Client;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowOptions;

#[Exclude]
class TemporalClient implements TemporalClientInterface
{
    private WorkflowClient $temporalClient;

    /**
     * @param string $temporalUrl
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private string $temporalUrl,
        private SerializerInterface $serializer,
    )
    {
        $serviceClient = ServiceClient::create($this->temporalUrl);
        $this->temporalClient = WorkflowClient::create($serviceClient);
    }

    /**
     * @return WorkflowClient
     */
    public function getClient(): WorkflowClient
    {
        return $this->temporalClient;
    }

    /**
     * @param object $command
     * @return string
     * @throws ExceptionInterface
     */
    public function dispatch(object $command) : string
    {
        $workflow = $this->temporalClient->newWorkflowStub(
            MessengerWorkflow::class,
            WorkflowOptions::new()
                ->withTaskQueue('taskQueue')
                ->withWorkflowId('messenger-'.uniqid())
        );

        $payload = json_decode($this->serializer->serialize($command, 'json'), true);

        $handle = $this->temporalClient->start($workflow, $command::class, $payload);
        return $handle->getExecution()->getID();
    }
}