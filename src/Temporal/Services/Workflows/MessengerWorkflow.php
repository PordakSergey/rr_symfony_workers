<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivityOptions;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerWorkflowInterface;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowMethod;

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
            MessengerActivityOptions::make()
        );

        yield $activity->dispatch($class, $payload);
    }
}