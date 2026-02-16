<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Client;

use Temporal\Client\WorkflowClient;

interface TemporalClientInterface
{
    /**
     * @return WorkflowClient
     */
    public function getClient() : WorkflowClient;

    /**
     * @param object $command
     * @return string
     */
    public function dispatch(object $command) : string;
}