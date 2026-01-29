<?php

namespace Rr\Bundle\Workers;

use Rr\Bundle\Workers\DependencyInjection\CompilerPass\GrpcStorageCompilerPass;
use Spiral\RoadRunner\GRPC\ServiceInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RrWorkersBundle extends Bundle
{
    /**
     * @param ContainerBuilder $container
     * @return void
     */
    public function build(ContainerBuilder $container): void
    {
        if (interface_exists(ServiceInterface::class)) {
            $container->addCompilerPass(new GrpcStorageCompilerPass());
        }
    }
}