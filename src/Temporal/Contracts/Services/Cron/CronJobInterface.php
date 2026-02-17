<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Cron;

interface CronJobInterface
{
    /**
     * @return string
     */
    public function getTaskId(): string;

    /**
     * @return string
     */
    public function getCron(): string;

    /**
     * @return object
     */
    public function getCommand(): object;

    /**
     * @return string
     */
    public function getTimezone(): string;

    /**
     * @return string
     */
    public function getWorkflowType(): string;

    /**
     * @return string
     */
    public function getTaskQueue(): string;
}