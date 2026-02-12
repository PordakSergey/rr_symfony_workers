<?php

namespace Rr\Bundle\Workers\Temporal\Services\Workflows;

use Rr\Bundle\Workers\Temporal\Services\Activities\DemoActivity;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Temporal\Activity\ActivityOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
#[Autoconfigure(tags: ['temporal.workflow'])]
class DemoWorkflow
{
    #[WorkflowMethod]
    public function run(string $message)
    {
        $actvity = Workflow::newActivityStub(
            DemoActivity::class,
            ActivityOptions::new()
        );

        return yield $actvity->print($message);
    }
}