<?php

use Rr\Bundle\Workers\Contracts\Http\RequestHandlerInterface;
use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\HttpFoundationWorkerInterface;
use Rr\Bundle\Workers\Contracts\Storage\WorkerStorageInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Rr\Bundle\Workers\Helpers\BasicAuthHandler;
use Rr\Bundle\Workers\Helpers\ServerParser;
use Rr\Bundle\Workers\Http\KernelHandler;
use Rr\Bundle\Workers\RoadRunnerBridge\HttpFoundationWorker;
use Rr\Bundle\Workers\Storage\WorkerStorage;
use Rr\Bundle\Workers\Workers\HttpWorker as InternalHttpWorker;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\EnvironmentInterface;
use Spiral\RoadRunner\Http\HttpWorker;
use Spiral\RoadRunner\Http\HttpWorkerInterface;
use Spiral\RoadRunner\Worker as RoadRunnerWorker;
use Spiral\RoadRunner\WorkerInterface as RoadRunnerWorkerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('intercept_side_effect', true);

    $services = $container->services();

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

    // Bundle
    $services->set(ServerParser::class);
    $services->set(BasicAuthHandler::class);
    $services->set(HttpFoundationWorkerInterface::class, HttpFoundationWorker::class)->args([
        service(HttpWorkerInterface::class),
        service(ServerParser::class),
    ]);
    $services->set(RequestHandlerInterface::class, KernelHandler::class)->args([
        service('kernel'),
        service(BasicAuthHandler::class)
    ]);

    // Workers
    $services->set(InternalHttpWorker::class)
        ->public()
        ->args([
            service('kernel'),
            service(HttpFoundationWorkerInterface::class),
            service(RequestHandlerInterface::class),
            service(\Psr\Log\LoggerInterface::class)
        ]);

    // Set Workers to storage
    $services->set(WorkerStorageInterface::class, WorkerStorage::class)->public();
    $services->get(WorkerStorageInterface::class)->call('registerWorker', [
        Environment\Mode::MODE_HTTP,
        service(HttpWorker::class)
    ]);
};