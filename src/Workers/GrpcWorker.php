<?php

namespace Rr\Bundle\Workers\Workers;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Storage\GrpcServiceStorage;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\GRPC\InvokerInterface;
use Spiral\RoadRunner\GRPC\Server;
use Spiral\RoadRunner\WorkerInterface as RoadrunnerWorker;

final class GrpcWorker implements WorkerInterface
{
    private Server $server;

    public function __construct(
        private GrpcServiceStorage $storage,
        private LoggerInterface $logger,
        private InvokerInterface $invoker,
        private RoadrunnerWorker $roadRunnerWorker,
    )
    {
        $this->server = new Server($this->invoker);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        foreach ($this->storage->getRegisteredServices()  as $interface => $service) {
            $this->logger->debug(
                \sprintf(
                    'Registering GRPC service for \'%s\' from \'%s\'',
                    $interface,
                    \get_class($service),
                ),
            );
            $this->server->registerService($interface, $service);
        }

        $this->server->serve($this->roadRunnerWorker);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function supports(string $name): bool
    {
        return $name == Environment\Mode::MODE_GRPC;
    }
}