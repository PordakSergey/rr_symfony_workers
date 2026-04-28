<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Temporal\Activity\ActivityMethod;

class MessengerActivity implements MessengerActivityInterface
{
    use HandleTrait;

    /**
     * @param MessageBusInterface $messageBus
     * @param DenormalizerInterface $serializer
     */
    public function __construct(
        protected DenormalizerInterface $serializer,
        MessageBusInterface $messageBus,
    )
    {
        $this->messageBus = $messageBus;
    }

    /**
     * @param string $class
     * @param array $payload
     * @return mixed
     * @throws ExceptionInterface
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): mixed
    {
        $command = $this->serializer->denormalize($payload, $class, 'json');

        return $this->handle($command);
    }
}