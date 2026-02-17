<?php

namespace Rr\Bundle\Workers\Temporal\Commands;

use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;

class CronJobMap
{
    public static function all()
    {
        return [
            'flightsearch:reindex' => [
                'workflowType' => MessengerWorkflow::class,
                'taskQueue' => 'taskQueue',
                'timezone' => 'Europe/Berlin',
                'cron' => '*/5 * * * *',
                'args' => [['tenantId' => 1]],
            ],
        ];
    }
}