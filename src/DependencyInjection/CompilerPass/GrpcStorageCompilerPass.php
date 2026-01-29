<?php

namespace Rr\Bundle\Workers\DependencyInjection\CompilerPass;

use Rr\Bundle\Workers\Storage\GrpcServiceStorage;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GrpcStorageCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(GrpcServiceStorage::class)) {
            return;
        }

        $provider = $container->findDefinition(GrpcServiceStorage::class);
        $taggedServices = $container->findTaggedServiceIds('roadrunner.grpc_service');

        foreach ($taggedServices as $id => $tags) {
            $definition = $container->getDefinition($id);

            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            $grpcServiceInterfaces = $this->findGrpcServiceInterfaces($class);

            foreach ($grpcServiceInterfaces as $grpcServiceInterface) {
                $provider->addMethodCall('registerService', [$grpcServiceInterface, new Reference($id)]);
            }
        }
    }

    /**
     * @param string $className
     * @return \Generator
     */
    private function findGrpcServiceInterfaces(string $className): \Generator
    {
        $interfaces = class_implements($className);

        if (!$interfaces) {
            return;
        }

        foreach ($interfaces as $interface) {
            if (is_subclass_of($interface, ServiceInterface::class, true)) {
                yield $interface;
            }
        }
    }
}