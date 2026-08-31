<?php

namespace Rr\Bundle\Workers\Temporal\Services\Cron;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Cron\CronJobInterface;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Rr\Bundle\Workers\Workers\TemporalWorker;
use Symfony\Component\DependencyInjection\Attribute\Exclude;

#[Exclude]
class CronJob implements CronJobInterface
{
    public function __construct(
        protected string $taskId,
        protected string $cron,
        protected object $command,
        protected string $taskQueue = TemporalWorker::DEFAULT_TASK_QUEUE,
    )
    {}

    /**
     * @return string
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * @return string
     */
    public function getCron(): string
    {
        return $this->cron;
    }

    /**
     * @return object
     */
    public function getCommand(): object
    {
        return $this->command;
    }

    /**
     * @return string
     */
    public function getTimezone(): string
    {
        return 'UTC';
    }

    /**
     * @return string
     */
    public function getWorkflowType(): string
    {
        return MessengerWorkflow::class;
    }

    /**
     * @return string
     */
    public function getTaskQueue(): string
    {
        return $this->taskQueue;
    }
}