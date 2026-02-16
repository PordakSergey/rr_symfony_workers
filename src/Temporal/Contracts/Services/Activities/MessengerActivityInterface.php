<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Activities;

use Symfony\Component\Messenger\Envelope;
use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface]
interface MessengerActivityInterface
{
    /**
     * @param string $class
     * @param object $payload
     * @return Envelope
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, string|\Stringable $payload): Envelope;
}