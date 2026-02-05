<?php

use Rr\Bundle\Workers\Contracts\Storage\WorkerStorageInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Rr\Bundle\Workers\Handlers\RequestHandler;
use Rr\Bundle\Workers\Middlewares\DoctrineORMMiddleware;
use Rr\Bundle\Workers\Storage\WorkerStorage;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\EnvironmentInterface;
use Spiral\RoadRunner\GRPC\Invoker;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\Http\HttpWorker;
use Spiral\RoadRunner\Http\HttpWorkerInterface;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\ConsumerInterface;
use Spiral\RoadRunner\Worker as RoadRunnerWorker;
use Spiral\RoadRunner\WorkerInterface as RoadRunnerWorkerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container) {
    // params
    $container->parameters()
        ->set('intercept_side_effect', true)
        ->set('middlewares.default', [
            'before' => [
                service(DoctrineORMMiddleware::class)
            ],
            'after' => [],
        ])
        ->set('interceptors.default', [
            'before' => [
                service(DoctrineORMMiddleware::class)
            ],
            'after' => [],
        ])
    ;

    $services = $container->services()->defaults()
        ->autowire()
        ->autoconfigure()
        ->private()
        ->bind('iterable $workers',  tagged_iterator('rr.worker'));

    // RoadRuner
    $services->set(EnvironmentInterface::class)
        ->factory([Environment::class, 'fromGlobals']);

    $services->set(RoadRunnerWorkerInterface::class, RoadRunnerWorker::class)
        ->factory([RoadRunnerWorker::class, 'createFromEnvironment'])
        ->args([service(EnvironmentInterface::class), '%intercept_side_effect%']);

    $services->set(HttpWorkerInterface::class, HttpWorker::class)
        ->args([service(RoadRunnerWorkerInterface::class)]);

    $services->set(RPCInterface::class)
        ->factory([RPCFactory::class, 'fromEnvironment'])
        ->args([service(EnvironmentInterface::class)]);

    $services->set(ConsumerInterface::class, Consumer::class);
    $services->set(InvokerInterface::class, Invoker::class);

    // autoload
    $services
        ->load('Rr\\Bundle\\Workers\\', realpath(__DIR__ . '/../src').'/')
        ->public();
    
    // Bundle
    $services
        ->instanceof(WorkerInterface::class)
        ->tag('rr.worker');

    $services->set(\Rr\Bundle\Workers\Workers\JobsWorker::class)->autowire()->public()->tag('rr.worker');
    $services->set(\Rr\Bundle\Workers\Workers\HttpWorker::class)->autowire()->public()->tag('rr.worker');
    $services->set(\Rr\Bundle\Workers\Workers\GrpcWorker::class)->autowire()->public()->tag('rr.worker');

    $services->alias(WorkerStorageInterface::class, WorkerStorage::class)->public();
    $services->alias(\Rr\Bundle\Workers\Contracts\Handlers\RequestHandlerInterface::class, RequestHandler::class);
};