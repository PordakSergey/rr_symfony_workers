<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Rr\Bundle\Workers\Exceptions\BadConfigurationException;
use Temporal\Client\GRPC\ServiceClient;
use Temporal\Client\GRPC\ServiceClientInterface;

class TemporalClientServiceFactory
{
    public static function make() : ServiceClientInterface
    {
        $temporalUrl = $_ENV['TEMPORAL_URL'] ?? $_SERVER['TEMPORAL_URL'] ?? null;
        if ($temporalUrl === null) {
            throw BadConfigurationException::missingTemporalUrl();
        }

        return ServiceClient::create($temporalUrl);
    }
}