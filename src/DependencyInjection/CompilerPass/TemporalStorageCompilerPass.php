<?php

namespace Rr\Bundle\Workers\DependencyInjection\CompilerPass;

use ReflectionException;
use Rr\Bundle\Workers\Temporal\Enums\TemporalEntity;
use Rr\Bundle\Workers\Temporal\Services\Storage\TemporalStorage;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Temporal\Activity\ActivityInterface;
use Temporal\Workflow\WorkflowInterface;

#[Autoconfigure(tags: [])]
class TemporalStorageCompilerPass implements CompilerPassInterface
{
    private Definition $storage;
    private ContainerBuilder $container;

    /**
     * @param ContainerBuilder $container
     * @return void
     * @throws ReflectionException
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(TemporalStorage::class)) {
            return;
        }

        $this->storage = $container->findDefinition(TemporalStorage::class);
        $this->container = $container;

        $this->registerTemporalService('temporal.activity', TemporalEntity::ACTIVITY, ActivityInterface::class);
        $this->registerTemporalService('temporal.workflow', TemporalEntity::WORKFLOW,  WorkflowInterface::class);
    }

    /**
     * @param string $serviceTag
     * @param TemporalEntity $entity
     * @param string $attributeClass
     * @return void
     * @throws ReflectionException
     */
    private function registerTemporalService(string $serviceTag, TemporalEntity $entity, string $attributeClass): void
    {
        $services = $this->container->findTaggedServiceIds($serviceTag);
        foreach ($services as $id => $tags) {
            $definition = $this->container->getDefinition($id);
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }

            $reflector = new \ReflectionClass($class);
            $attributes = $reflector->getAttributes($attributeClass);
            if (empty($attributes)) {
                continue;
            }

            $this->storage->addMethodCall('setEntityStorage', [$class, $entity]);
        }
    }
}