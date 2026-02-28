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
    public function __construct(
        private KernelInterface $kernel,
        //private TemporalStorage $storage,
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
                ->withMaxConcurrentActivityExecutionSize(10)
                ->withMaxConcurrentWorkflowTaskExecutionSize(10)
        );

        $worker->registerActivity(MessengerActivity::class, fn(ReflectionClass $class) => $this->kernel->getContainer()->get($class->getName()));
        $worker->registerWorkflowTypes(MessengerWorkflow::class);

        /*
        foreach ($this->storage->getEntity(TemporalEntity::WORKFLOW2) as $workflow) {
            $worker->registerWorkflowTypes($workflow::class);
        }*/

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