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
    protected const int MAX_CONCURRENT_ACTIVITIES = 100;
    protected const int MAX_CONCURRENT_WORKFLOWS = 100;
    private const int ACTIVITY_POLLERS = 10;
    private const int WORKFLOW_POLLERS = 5;

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
                ->withMaxConcurrentActivityTaskPollers(self::ACTIVITY_POLLERS)
                ->withMaxConcurrentWorkflowTaskPollers(self::WORKFLOW_POLLERS)
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