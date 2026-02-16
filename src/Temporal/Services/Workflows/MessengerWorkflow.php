<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Carbon\CarbonInterval;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerWorkflowInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Temporal\Activity\ActivityOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

class MessengerWorkflow implements MessengerWorkflowInterface
{

    /**
     * @param object $command
     * @return void
     * @throws ExceptionInterface
     */
    #[WorkflowMethod(name: 'run')]
    public function run(object $command): void
    {
        $activity = Workflow::newActivityStub(
            MessengerActivityInterface::class,
            ActivityOptions::new()
                ->withStartToCloseTimeout(CarbonInterval::second(10))
        );

        $activity->dispatch($command);
    }
}