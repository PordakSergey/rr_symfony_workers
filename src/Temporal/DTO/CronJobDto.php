<?php

namespace Rr\Bundle\Workers\Temporal\DTO;

use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;

class CronJobDto
{
    /**
     * @param string $cron
     * @param object $command
     * @param string $taskQueue
     * @param string $timezone
     * @param string $workflowType
     */
    public function __construct(
        public string $cron,
        public object $command,
        public string $taskQueue = 'taskQueue',
        public string $timezone = 'Europe/Berlin',
        public string $workflowType = MessengerWorkflow::class,
    )
    {
    }
}