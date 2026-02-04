<?php

namespace Rr\Bundle\Workers\DependencyInjection\CompilerPass;

use Rr\Bundle\Workers\Contracts\Middlewares\MiddlewareInterface;
use Rr\Bundle\Workers\Handlers\MiddlewareStackHandler;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

class MiddlewaresCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(MiddlewareStackHandler::class)) {
            return;
        }

        $stack = $container->getDefinition(MiddlewareStackHandler::class);

        $defaultMiddlewares = $container->getParameter('middlewares.default');

        $middlewaresToRemove = [];
        $beforeMiddlewares = array_diff($defaultMiddlewares['before'], $middlewaresToRemove);
        $afterMiddlewares = array_diff($defaultMiddlewares['after'], $middlewaresToRemove);

        foreach (array_merge($beforeMiddlewares, $afterMiddlewares) as $m) {
            if (!$container->has($m)) {
                throw new LogicException("No service found for middleware '$m'.");
            }

            $definition = $container->findDefinition($m);
            $class = $definition->getClass();

            if (null === $class) {
                throw new InvalidArgumentException("Missing class definition for service '$m'.");
            }

            if (!is_a($class, MiddlewareInterface::class, true)) {
                throw new InvalidArgumentException(\sprintf("Service '%s' should implements '%s'.", $m, MiddlewareInterface::class));
            }

            $stack->addMethodCall('pipe', [new Reference($m)]);
        }
    }
}