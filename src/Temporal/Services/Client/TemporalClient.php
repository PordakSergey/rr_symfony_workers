<?php

namespace Rr\Bundle\Workers\Temporal\Services\Client;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\WorkflowClient;

#[Exclude]
class TemporalClient implements TemporalClientInterface
{
    private WorkflowClient $temporalClient;

    /**
     * @param string $temporalUrl
     */
    public function __construct(
        private string $temporalUrl,
    )
    {
        $serviceClient = ServiceClient::create($this->temporalUrl);
        $this->temporalClient = WorkflowClient::create($serviceClient);
    }

    /**
     * @return WorkflowClient
     */
    public function getClient() : WorkflowClient
    {
        return $this->temporalClient;
    }
}