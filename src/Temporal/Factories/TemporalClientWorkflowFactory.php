<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\WorkflowClient;
use Temporal\Client\WorkflowClientInterface;

class TemporalClientWorkflowFactory
{
    /**
     * @param ServiceClientInterface $serviceClient
     */
    public function __construct(
        protected ServiceClientInterface $serviceClient
    )
    {
    }

    /**
     * @return WorkflowClientInterface
     */
    public function make() : WorkflowClientInterface
    {
        return WorkflowClient::create($this->serviceClient);
    }
}