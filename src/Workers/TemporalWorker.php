<?php

namespace Rr\Bundle\Workers\Workers;

use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Temporal\Enums\TemporalEntity;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;
use Spiral\RoadRunner\Environment;
use Symfony\Component\HttpKernel\KernelInterface;
use Temporal\WorkerFactory;

final class TemporalWorker implements WorkerInterface
{
    public function __construct(
        private KernelInterface $kernel,
        private TemporalStorage $storage,
    )
    {
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $factory = WorkerFactory::create();

        $worker = $factory->newWorker(
            'taskQueue',
            \Temporal\Worker\WorkerOptions::new()->withMaxConcurrentActivityExecutionSize(10)
        );

        foreach ($this->storage->getEntity(TemporalEntity::ACTIVITY) as $activity) {
            $worker->registerActivityImplementations($activity);
        }
        foreach ($this->storage->getEntity(TemporalEntity::WORKFLOW) as $workflow) {
            $worker->registerWorkflowTypes($workflow::class);
        }

        $worker->registerActivityFinalizer(fn() => $this->kernel->shutdown());
        $factory->run();
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function supports(string $name): bool
    {
        return $name == Environment\Mode::MODE_TEMPORAL;
    }
}