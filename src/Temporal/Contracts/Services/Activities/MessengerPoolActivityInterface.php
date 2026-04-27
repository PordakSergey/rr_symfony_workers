<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Activities;

use Symfony\Component\Messenger\Envelope;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface]
interface MessengerPoolActivityInterface
{
    /**
     * @param object[] $commands
     * @return Envelope
     */
    #[ActivityMethod(name: 'dispatchPool')]
    public function dispatchPool(array $commands): Envelope;
}