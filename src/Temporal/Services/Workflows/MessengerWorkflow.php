<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerWorkflowInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Temporal\Activity\ActivityOptions;
use Temporal\Common\RetryOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

#[Autoconfigure(tags: ['temporal.workflow'])]
class MessengerWorkflow implements MessengerWorkflowInterface
{

    /**
     * @param string $class
     * @param array $payload
     * @return \Generator
     */
    #[WorkflowMethod(name: 'run')]
    public function run(string $class, array $payload): \Generator
    {
        $activity = Workflow::newActivityStub(
            MessengerActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::minutes(2))
                ->withTaskQueue('taskQueue')
                ->withRetryOptions(RetryOptions::new()->withMaximumAttempts(1))
        );

        yield $activity->dispatch($class, $payload);
    }
}