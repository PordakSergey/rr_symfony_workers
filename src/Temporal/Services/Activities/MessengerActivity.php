<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Temporal\Activity\ActivityMethod;

class MessengerActivity implements MessengerActivityInterface
{
    /**
     * @param MessageBusInterface $messageBus
     * @param DenormalizerInterface $serializer
     */
    public function __construct(
        protected MessageBusInterface $messageBus,
        protected DenormalizerInterface $serializer,
    )
    {
    }

    /**
     * @param string $class
     * @param array $payload
     * @return Envelope
     * @throws ExceptionInterface
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): Envelope
    {
        $command = $this->serializer->denormalize($payload, $class, 'json');

        return $this->messageBus->dispatch($command);
    }
}