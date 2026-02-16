<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Jobs\JobsDispatcherInterface;

class JobsHandler
{
    /**
     * @param JobsDispatcherInterface $jobsDispatcher
     */
    public function __construct(
        protected JobsDispatcherInterface $jobsDispatcher,
    )
    {
    }

    /**
     * @param object $command
     * @return string
     */
    public function dispatch(object $command): string
    {
        return $this->jobsDispatcher->dispatch($command);
    }
}