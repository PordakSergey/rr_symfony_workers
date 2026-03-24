<?php

namespace Rr\Bundle\Workers\Workers;

use ReflectionClass;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Temporal\Enums\TemporalEntity;
use Rr\Bundle\Workers\Temporal\Services\Activities\MessengerActivity;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;
use Rr\Bundle\Workers\Temporal\Services\Workflows\MessengerWorkflow;
use Spiral\RoadRunner\Environment;
use Symfony\Component\HttpKernel\KernelInterface;
use Temporal\WorkerFactory;

final class TemporalWorker implements WorkerInterface
{
    protected const int MAX_CONCURRENT_ACTIVITIES = 10;
    protected const int MAX_CONCURRENT_WORKFLOWS = 10;

    /**
     * @param KernelInterface $kernel
     * @param TemporalStorage $storage
     */
    public function __construct(
        protected KernelInterface $kernel,
        protected TemporalStorage $storage,
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
            \Temporal\Worker\WorkerOptions::new()
                ->withMaxConcurrentActivityExecutionSize(self::MAX_CONCURRENT_ACTIVITIES)
                ->withMaxConcurrentWorkflowTaskExecutionSize(self::MAX_CONCURRENT_WORKFLOWS)
        );

        foreach ( $this->storage->getActivities() as $activity) {
            $worker->registerActivity($activity::class, fn(ReflectionClass $class) => $this->kernel->getContainer()->get($class->getName()));
        }
        foreach ($this->storage->getWorkflows() as $workflow) {
            $worker->registerWorkflowTypes($workflow::class);
        }

        $worker->registerActivityFinalizer(function (): void {
            $container = $this->kernel->getContainer();
            if ($container->has('services_resetter')) {
                $container->get('services_resetter')->reset();
            }
        });
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