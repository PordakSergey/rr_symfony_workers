<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface MessengerWorkflowInterface
{
    /**
     * @param string $class
     * @param array $payload
     * @return \Generator
     */
    #[WorkflowMethod(name: 'run')]
    public function run(string $class, array $payload): \Generator;
}