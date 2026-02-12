<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Rr\Bundle\Workers\Exceptions\BadConfigurationException;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Services\Client\TemporalClient;

class TemporalClientFactory
{
    /**
     * @return TemporalClientInterface
     */
    public static function fromEnvironment(): TemporalClientInterface
    {
        $temporalUrl = $_ENV['TEMPORAL_URL'] ?? $_SERVER['TEMPORAL_URL'] ?? null;
        if ($temporalUrl === null) {
            throw BadConfigurationException::missingTemporalUrl();
        }

        return new TemporalClient($temporalUrl);
    }
}