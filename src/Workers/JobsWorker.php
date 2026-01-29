<?php

namespace Rr\Bundle\Workers\Workers;

use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Spiral\RoadRunner\Jobs\ConsumerInterface;
use Symfony\Contracts\Service\ResetInterface;
use Spiral\RoadRunner\Environment;

final class JobsWorker implements WorkerInterface
{
    /**
     * @param ConsumerInterface $consumer
     * @param JobHandlerInterface $handler
     * @param ResetInterface|null $reset
     */
    public function __construct(
        private ConsumerInterface $consumer,
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
        while ($task = $this->consumer->waitTask()) {
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