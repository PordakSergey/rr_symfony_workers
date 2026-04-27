<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobsHandlerInterface;
use Rr\Bundle\Workers\Jobs\Responce\JobResponse;

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
     * @param bool $returnResult
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false): JobResponse
    {
        return $this->jobsDispatcher->dispatch($command, $returnResult);
    }

    /**
     * @param array $commands
     * @param bool $returnResult
     * @return array|JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false): array
    {
        return $this->jobsDispatcher->dispatchPool($commands, $returnResult);
    }
}