<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
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
     * @return array
     * @throws \JsonException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): array
    {
        $command = $this->denormalizer->denormalize($payload, $class, 'json');

        return $this->normalizeResult($this->handle($command));
    }

    /**
     * @param mixed $result
     * @return array
     * @throws \JsonException
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    private function normalizeResult(mixed $result): array
    {
        if (is_array($result)) {
            return json_decode(json_encode($result, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return $this->normalizer->normalize($result, 'json');
    }
}