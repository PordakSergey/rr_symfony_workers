<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\GRPC\ServiceClientInterface;

class TemporalClientServiceFactory
{
    public const string DEFAULT_ADDRESS = 'localhost:7233';

    /**
     * @return ServiceClientInterface
     */
    public static function make() : ServiceClientInterface
    {
        return ServiceClient::create(self::address());
    }

    /**
     * @return string TEMPORAL_URL или адрес temporal по умолчанию
     */
    public static function address(): string
    {
        return $_ENV['TEMPORAL_URL'] ?? $_SERVER['TEMPORAL_URL'] ?? self::DEFAULT_ADDRESS;
    }
}