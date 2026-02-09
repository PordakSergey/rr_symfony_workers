<?php

namespace Rr\Bundle\Workers\Temporal;

use Temporal\Activity\ActivityOptions;
use Temporal\Workflow;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
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