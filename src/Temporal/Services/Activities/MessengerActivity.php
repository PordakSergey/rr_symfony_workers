<?php

namespace Rr\Bundle\Workers\Temporal\Services\Activities;

use Rr\Bundle\Workers\Temporal\Contracts\Services\Activities\MessengerActivityInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Temporal\Activity\ActivityMethod;

class MessengerActivity implements MessengerActivityInterface
{
    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        protected MessageBusInterface $messageBus
    )
    {
    }

    /**
     * @param object $command
     * @return void
     * @throws ExceptionInterface
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(object $command): Envelope
    {
        return $this->messageBus->dispatch($command);
    }
}