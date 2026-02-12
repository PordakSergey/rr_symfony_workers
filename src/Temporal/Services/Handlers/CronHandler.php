<?php

namespace Rr\Bundle\Workers\Temporal\Services\Handlers;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\CronWorkflowInterface;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;
use Temporal\Client\WorkflowOptions;

class CronHandler
{
    public function __construct(
        private TemporalClientInterface $client,
        private TemporalStorage         $storage,
    )
    {
    }

    public function handle(): void
    {
        foreach ($this->storage->getTasks() as $id => $task) {
            assert($task instanceof CronWorkflowInterface);
            $this->client->getClient()->newWorkflowStub(CronWorkflowInterface::class, WorkflowOptions::new()
                ->withWorkflowId($id)
                ->withCronSchedule('* * * * *')
                ->withWorkflowExecutionTimeout(CarbonInterval::minute(10))
                ->withWorkflowRunTimeout(CarbonInterval::minute())
            );
        }
    }
}