<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows;

use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
interface MessengerWorkflowInterface
{
    /**
     * @param string $class
     * @param string|\Stringable $payload
     * @return \Generator
     */
    #[WorkflowMethod(name: 'run')]
    public function run(string $class, string|\Stringable $payload): \Generator;
}