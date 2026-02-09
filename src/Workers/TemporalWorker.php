<?php

namespace Rr\Bundle\Workers\Workers;

use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Temporal\DemoActivity;
use Rr\Bundle\Workers\Temporal\DemoWorkflow;
use Spiral\RoadRunner\Environment;
use Symfony\Component\HttpKernel\KernelInterface;
use Temporal\WorkerFactory;

final class TemporalWorker implements WorkerInterface
{
    public function __construct(
        private KernelInterface $kernel,
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

        $worker->registerWorkflowTypes(DemoWorkflow::class);
        $worker->registerActivity(DemoActivity::class);
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