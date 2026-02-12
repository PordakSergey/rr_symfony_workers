<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Workflows;

use phpDocumentor\Reflection\Types\ClassString;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Temporal\Workflow\WorkflowInterface;
use Temporal\Workflow\WorkflowMethod;

#[WorkflowInterface]
#[Autoconfigure(tags: ['temporal.workflow'])]
interface CronWorkflowInterface
{
    /**
     * @return string
     */
    public static function getWorkflowId(): string;

    /**
     * @param ClassString $class
     * @param object $payload
     * @return string
     */
    #[WorkflowMethod(name: 'cron.execute')]
    public function execute(ClassString $class, object $payload): string;
}