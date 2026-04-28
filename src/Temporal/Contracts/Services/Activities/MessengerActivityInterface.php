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
     * @param array $payload
     * @return mixed
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(string $class, array $payload): array;
}