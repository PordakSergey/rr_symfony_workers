<?php

namespace Rr\Bundle\Workers\Workers;

use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\JobsFoundationWorkerInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Spiral\RoadRunner\Environment;

final class JobsWorker implements WorkerInterface
{
    public function __construct(
        private JobsFoundationWorkerInterface $worker,
        private JobHandlerInterface $handler,
        private ?ResetInterface $reset = null,
    )
    {
    }

    /**
     * @return void
     */
    public function run(): void
    {
        while ($task = $this->worker->getConsumer()->waitTask()) {
            try {
                $this->handler->handle($task);
                $task->ack();
            } catch (\Throwable $e) {
                $task->nack($e::class.': '.$e->getMessage());
            } finally {
                $this->reset?->reset();
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function supports(string $name): bool
    {
        return $name == Environment\Mode::MODE_JOBS;
    }
}