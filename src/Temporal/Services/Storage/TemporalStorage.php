<?php

namespace Rr\Bundle\Workers\Temporal\Services\Storage;

use Rr\Bundle\Workers\Temporal\Enums\TemporalEntity;

final class TemporalStorage
{
    /**
     * @param iterable $activities
     * @param iterable $workflows
     */
    public function __construct(
        protected iterable $activities,
        protected iterable $workflows
    )
    {}

    /**
     * @param object $entityClass
     * @param TemporalEntity $entityType
     * @return void
     */
    public function getActivities(): array
    {
        $activities = [];
        foreach ($this->activities as $activity) {
            $activities[] = $activity;
        }
        return $activities;
    }

    /**
     * @param TemporalEntity $entityType
     * @return array
     */
    public function getWorkflows(): array
    {
        $workflows = [];
        foreach ($this->workflows as $workflow) {
            $workflows[] = $workflow;
        }

        return $workflows;
    }
}