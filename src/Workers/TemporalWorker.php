<?php

namespace Rr\Bundle\Workers\Workers;

use ReflectionClass;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;
use Spiral\RoadRunner\Environment;
use Symfony\Component\HttpKernel\KernelInterface;
use Temporal\Worker\WorkerOptions;
use Temporal\WorkerFactory;

final class TemporalWorker implements WorkerInterface
{
    public const string DEFAULT_TASK_QUEUE = 'taskQueue';

    protected const int MAX_CONCURRENT_ACTIVITIES = 100;
    protected const int MAX_CONCURRENT_WORKFLOWS = 100;
    private const int ACTIVITY_POLLERS = 10;
    private const int WORKFLOW_POLLERS = 5;

    /**
     * @param KernelInterface $kernel
     * @param TemporalStorage $storage
     * @param array<string, array> $workers Task queue name => options, from rr_bundle.temporal.workers
     */
    public function __construct(
        protected KernelInterface $kernel,
        protected TemporalStorage $storage,
        protected array $workers = [self::DEFAULT_TASK_QUEUE => []],
    )
    {
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $factory = WorkerFactory::create();

        foreach ($this->workers as $taskQueue => $options) {
            $worker = $factory->newWorker(
                $taskQueue,
                WorkerOptions::new()
                    ->withMaxConcurrentActivityExecutionSize($options['max_concurrent_activities'] ?? self::MAX_CONCURRENT_ACTIVITIES)
                    ->withMaxConcurrentWorkflowTaskExecutionSize($options['max_concurrent_workflows'] ?? self::MAX_CONCURRENT_WORKFLOWS)
                    ->withMaxConcurrentActivityTaskPollers($options['activity_pollers'] ?? self::ACTIVITY_POLLERS)
                    ->withMaxConcurrentWorkflowTaskPollers($options['workflow_pollers'] ?? self::WORKFLOW_POLLERS)
            );

            foreach ($this->only($this->storage->getActivities(), $options['activities'] ?? []) as $activity) {
                $worker->registerActivity($activity::class, fn(ReflectionClass $class) => $this->kernel->getContainer()->get($class->getName()));
            }
            foreach ($this->only($this->storage->getWorkflows(), $options['workflows'] ?? []) as $workflow) {
                $worker->registerWorkflowTypes($workflow::class);
            }

            $worker->registerActivityFinalizer(function (): void {
                $container = $this->kernel->getContainer();
                if ($container->has('services_resetter')) {
                    $container->get('services_resetter')->reset();
                }
            });
        }

        $factory->run();
    }

    /**
     * @param object[] $entities
     * @param string[] $classes Empty means "all"
     * @return object[]
     */
    private function only(array $entities, array $classes): array
    {
        return $classes
            ? array_filter($entities, static fn(object $entity): bool => in_array($entity::class, $classes, true))
            : $entities;
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