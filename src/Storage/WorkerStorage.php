<?php

namespace Rr\Bundle\Workers\Storage;

use Rr\Bundle\Workers\Contracts\Storage\WorkerStorageInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;

final class WorkerStorage implements WorkerStorageInterface
{
    /** @var iterable<WorkerInterface> */
    public function __construct(private iterable $workers) {}

    /**
     * @param string $mode
     * @return WorkerInterface|null
     */
    public function getWorker(string $mode): ?WorkerInterface
    {
        foreach ($this->workers as $worker) {
            if ($worker::supports($mode)) {
                return $worker;
            }
        }
        return null;
    }
}