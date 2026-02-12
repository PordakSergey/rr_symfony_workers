<?php

namespace Rr\Bundle\Workers\Temporal\Services\Storage;

use Rr\Bundle\Workers\Temporal\Enums\TemporalEntity;

class TemporalStorage
{
    /**
     * @var array
     */
    public array $storage = [];

    /**
     * @param object $entityClass
     * @param TemporalEntity $entityType
     * @return void
     */
    public function setEntityStorage(object $entityClass, TemporalEntity $entityType): void
    {
        $this->storage[$entityType->value][$entityClass::class] = $entityClass;
    }

    /**
     * @param TemporalEntity $entityType
     * @return array
     */
    public function getEntity(TemporalEntity $entityType) : array
    {
        return $this->storage[$entityType->value] ?? [];
    }
}