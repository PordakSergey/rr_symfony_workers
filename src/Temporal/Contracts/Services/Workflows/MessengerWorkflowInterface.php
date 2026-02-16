<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface MessengerWorkflowInterface
{
    #[WorkflowMethod(name: 'run')]
    public function run(object $command): \Generator;
}