<?php

namespace Rr\Bundle\Workers\Storage;

use Spiral\RoadRunner\GRPC\ServiceInterface;

final class GrpcServiceStorage
{
    /**
     * @var array<class-string<ServiceInterface>, ServiceInterface>
     */
    private array $services = [];

    /**
     * @template T of ServiceInterface
     *
     * @param class-string<T> $interface
     * @param object $service
     */
    public function registerService(string $interface, object $service): void
    {
        $this->services[$interface] = $service;
    }

    /**
     * @return array<class-string<ServiceInterface>, ServiceInterface>
     */
    public function getRegisteredServices(): array
    {
        return $this->services;
    }
}