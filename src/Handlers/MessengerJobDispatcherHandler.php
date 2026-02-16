<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobsHandlerInterface;

class MessengerJobDispatcherHandler implements JobsHandlerInterface
{
    /**
     * @param JobDispatcherInterface $jobsDispatcher
     */
    public function __construct(
        protected JobDispatcherInterface $jobsDispatcher,
    )
    {
    }

    /**
     * @param object $command
     * @return string
     */
    public function dispatch(object $command): ?string
    {
        return $this->jobsDispatcher->dispatch($command);
    }
}