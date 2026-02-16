<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerWorkflowInterface;
use Temporal\Activity\ActivityOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

class MessengerWorkflow implements MessengerWorkflowInterface
{

    /**
     * @param string $class
     * @param string|\Stringable $payload
     * @return \Generator
     */
    #[WorkflowMethod(name: 'run')]
    public function run(string $class, array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            MessengerActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::second(10))
                ->withTaskQueue('taskQueue')
        );

        yield $activity->dispatch($class, $payload);
    }
}