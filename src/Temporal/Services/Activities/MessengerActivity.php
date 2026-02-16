<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Temporal\Activity\ActivityMethod;

class MessengerActivity implements MessengerActivityInterface
{
    /**
     * @param MessageBusInterface $messageBus
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected MessageBusInterface $messageBus,
        protected SerializerInterface $serializer,
    )
    {
    }

    /**
     * @param string $class
     * @param object $payload
     * @return Envelope
     * @throws ExceptionInterface
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): Envelope
    {
        $command = $this->serializer->deserialize($payload, $class, 'json');

        return $this->messageBus->dispatch($command);
    }
}