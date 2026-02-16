<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Rr\Bundle\Workers\Exceptions\BadConfigurationException;
use Rr\Bundle\Workers\Temporal\Contracts\Services\Client\TemporalClientInterface;
use Rr\Bundle\Workers\Temporal\Services\Client\TemporalClient;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

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

        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        return new TemporalClient($temporalUrl, $serializer);
    }
}