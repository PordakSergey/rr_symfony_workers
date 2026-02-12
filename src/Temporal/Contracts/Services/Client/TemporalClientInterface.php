<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Client;

use Temporal\Client\WorkflowClient;

interface TemporalClientInterface
{
    /**
     * @return WorkflowClient
     */
    public function getClient() : WorkflowClient;
}