<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Cron;

interface CronMapInterface
{
    /**
     * @return array
     */
    public function getAll() : array;
}