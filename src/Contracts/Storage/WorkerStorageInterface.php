<?php

namespace Rr\Bundle\Workers\Contracts\Storage;

use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;

interface WorkerStorageInterface
{
    /**
     * @param string $mode
     * @return WorkerInterface|null
     */
    public function getWorker(string $mode): ?WorkerInterface;
}