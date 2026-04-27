<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerPoolWorkflowInterface;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Promise;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

class MessengerPoolWorkflow implements MessengerPoolWorkflowInterface
{

    /**
     * @param array $commands
     * @return \Generator
     */
    #[WorkflowMethod(name: 'runPool')]
    public function run(array $commands): \Generator
    {
        $activity = Workflow::newActivityStub(
            MessengerActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(3))
                ->withTaskQueue('taskQueue')
                ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );

        $promises = array_map(function ($command) use ($activity) {
            return $activity->compose($command['class'], $command['payload']);
        }, $commands);

        $results = yield Promise::all($promises);

        return yield $results;
    }
}