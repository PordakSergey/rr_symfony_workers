<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivityOptions;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows\MessengerPoolWorkflowInterface;
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
            MessengerActivityOptions::make()
        );

        $promises = array_map(function ($command) use ($activity) {
            return $activity->dispatch($command['class'], $command['payload'])
                ->catch(fn(\Throwable $e) => ['error' => $e->getMessage()]);
        }, $commands);

        return yield Promise::all($promises);
    }
}