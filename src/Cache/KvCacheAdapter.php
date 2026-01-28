<?php

namespace Rr\Bundle\Workers\Cache;

use Spiral\Goridge\RPC\RPCInterface;
use Spiral\RoadRunner\KeyValue\Factory;
use Symfony\Component\Cache\Adapter\Psr16Adapter;

class KvCacheAdapter extends Psr16Adapter
{
    /**
     * @param string $dsn
     * @param array $options
     * @return self
     */
    public static function createConnection(#[\SensitiveParameter] string $dsn, array $options = []): self
    {
        /** @var RPCInterface $rpc */
        $rpc = $options['rpc'];
        $factory = new Factory($rpc);
        $storage = $factory->select($options['storage']);

        return new self($storage);
    }
}