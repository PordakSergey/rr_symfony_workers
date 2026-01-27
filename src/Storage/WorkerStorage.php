<?php

namespace Rr\Bundle\Workers\Storage;

use Rr\Bundle\Workers\Contracts\Storage\WorkerStorageInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;

final class WorkerStorage implements WorkerStorageInterface
{
    /**
     * @var array
     */
    private array $workers = [];

    /**
     * @param string $mode
     * @param WorkerInterface $worker
     * @return void
     */
    public function registerWorker(string $mode, WorkerInterface $worker): void
    {
        $this->workers[$mode] = $worker;
    }

    /**
     * @param string $mode
     * @return WorkerInterface|null
     */
    public function getWorker(string $mode): ?WorkerInterface
    {
        return $this->workers[$mode] ?? null;
    }
}