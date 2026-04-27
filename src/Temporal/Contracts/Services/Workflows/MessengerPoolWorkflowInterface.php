<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface MessengerPoolWorkflowInterface
{
    /**
     * @param object[] $commands
     * @return \Generator
     */
    #[WorkflowMethod(name: 'run')]
    public function run(array $commands): \Generator;
}