<?php

namespace Rr\Bundle\Workers\Temporal\SymfonyIntegration\Messenger\Services\Transport;

use Rr\Bundle\Workers\Temporal\Services\JobsDispatcher\TemporalJobDispatcher;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

/**
 * Send-only транспорт: сообщение стартует MessengerWorkflow, обрабатывает его temporal-воркер.
 * get/ack/reject пустые — messenger:consume для этого транспорта не нужен, ретраи делает Temporal.
 */
#[Exclude]
class TemporalTransport implements TransportInterface
{
    /**
     * @param TemporalJobDispatcher $dispatcher
     * @param string|null $queue null — rr_workers.temporal.default_queue
     * @param string $tag Префикс workflow id
     */
    public function __construct(
        protected TemporalJobDispatcher $dispatcher,
        protected ?string               $queue = null,
        protected string                $tag = 'messenger',
    )
    {
    }

    /**
     * @param Envelope $envelope
     * @return Envelope
     * @throws ExceptionInterface
     */
    public function send(Envelope $envelope): Envelope
    {
        $response = $this->dispatcher->dispatch(
            $envelope->getMessage(),
            false,
            $this->tag,
            $this->queue,
            $envelope->last(DelayStamp::class)?->getDelay() ?? 0,
        );

        return $envelope->with(new TransportMessageIdStamp($response->getId()));
    }

    /**
     * @return iterable
     */
    public function get(): iterable
    {
        return [];
    }

    /**
     * @param Envelope $envelope
     * @return void
     */
    public function ack(Envelope $envelope): void
    {
    }

    /**
     * @param Envelope $envelope
     * @return void
     */
    public function reject(Envelope $envelope): void
    {
    }
}