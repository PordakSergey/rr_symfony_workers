<?php

namespace Rr\Bundle\Workers\Exceptions;


class BadConfigurationException extends \RuntimeException
{
    /**
     * @return self
     */
    public static function rpcNotEnabled(): self
    {
        return new self("The RoadRunner plugin 'rpc' is disabled, try to add a `rpc` section to the configuration file.");
    }

    /**
     * @return self
     */
    public static function missingRpcAddr(): self
    {
        return new self("The RoadRunner plugin 'rpc' is enabled but no address has been found.");
    }
}