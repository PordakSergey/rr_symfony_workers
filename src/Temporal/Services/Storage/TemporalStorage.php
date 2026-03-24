<?php

namespace Rr\Bundle\Workers\Temporal\Services\Storage;

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
     * @return array
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