<?php

namespace Rr\Bundle\Workers\Contracts\Storage;

use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;

interface WorkerStorageInterface
{
    /**
     * @param string $mode
     * @param WorkerInterface $worker
     * @return void
     */
    public function registerWorker(string $mode, WorkerInterface $worker): void;

    /**
     * @param string $mode
     * @return WorkerInterface|null
     */
    public function getWorker(string $mode): ?WorkerInterface;
}