<?php

namespace Rr\Bundle\Workers\Factories;

use Rr\Bundle\Workers\Exceptions\BadConfigurationException;
use Spiral\Goridge\RPC\RPC;
use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\EnvironmentInterface;

class RPCFactory
{
    /**
     * @param EnvironmentInterface $environment
     * @return RPCInterface
     */
    public static function fromEnvironment(EnvironmentInterface $environment): RPCInterface
    {
        $rpcAddr = $_ENV['RR_RPC'] ?? $_SERVER['RR_RPC'] ?? null;
        if ($rpcAddr === null) {
            throw BadConfigurationException::rpcNotEnabled();
        }

        return RPC::create($environment->getRPCAddress() ?: throw BadConfigurationException::missingRpcAddr());
    }
}