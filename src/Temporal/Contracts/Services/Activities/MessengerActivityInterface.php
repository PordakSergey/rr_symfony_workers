<?php

namespace Rr\Bundle\Workers\Temporal\Contracts\Services\Activities;

use Temporal\Activity\ActivityInterface;
use Temporal\Activity\ActivityMethod;

#[ActivityInterface]
interface MessengerActivityInterface
{
    /**
     * @param object $command
     * @return void
     */
    #[ActivityMethod(name: 'dispatch')]
    public function dispatch(object $command): void;
}