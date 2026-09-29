<?php

namespace Rr\Bundle\Workers\Temporal\SymfonyIntegration\Messenger\Factories;

use Rr\Bundle\Workers\Temporal\Services\JobsDispatcher\TemporalJobDispatcher;
use Rr\Bundle\Workers\Temporal\SymfonyIntegration\Messenger\Services\Transport\TemporalTransport;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

/**
 * DSN: temporal://<queue>?tag=<tag>. queue "default" или пусто — rr_workers.temporal.default_queue.
 * Регистрируется autoconfigure-тегом messenger.transport_factory.
 */
class TransportFactory implements TransportFactoryInterface
{
    /**
     * @param TemporalJobDispatcher $dispatcher
     */
    public function __construct(
        protected TemporalJobDispatcher $dispatcher,
    )
    {
    }

    /**
     * Messenger-сериализатор не используется: payload нормализует TemporalJobDispatcher.
     *
     * @param string $dsn
     * @param array $options
     * @param SerializerInterface $serializer
     * @return TransportInterface
     */
    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        // "temporal://" без хоста parse_url не разбирает — это дефолтная очередь
        $url = parse_url($dsn) ?: [];
        parse_str($url['query'] ?? '', $query);

        $queue = $url['host'] ?? null;

        return new TemporalTransport(
            $this->dispatcher,
            $queue === null || $queue === 'default' ? null : $queue,
            $query['tag'] ?? 'messenger',
        );
    }

    /**
     * @param string $dsn
     * @param array $options
     * @return bool
     */
    public function supports(string $dsn, array $options): bool
    {
        return str_starts_with($dsn, 'temporal://');
    }
}