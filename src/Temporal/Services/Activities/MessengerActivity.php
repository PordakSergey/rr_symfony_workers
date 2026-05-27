<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Temporal\Activity\ActivityMethod;

class MessengerActivity implements MessengerActivityInterface
{
    use HandleTrait;

    /**
     * @param DenormalizerInterface $denormalizer
     * @param NormalizerInterface $normalizer
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        protected DenormalizerInterface $denormalizer,
        protected NormalizerInterface $normalizer,
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
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): mixed
    {
        $command = $this->denormalizer->denormalize($payload, $class, 'json');

        return $this->handle($command);
    }
}