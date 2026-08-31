<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobsHandlerInterface;
use Rr\Bundle\Workers\Jobs\Response\JobResponse;

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
     * @param string $tag
     * @param string|null $queue
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): JobResponse
    {
        return $this->jobsDispatcher->dispatch($command, $returnResult, $tag, $queue);
    }

    /**
     * @param array $commands
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue
     * @return array|JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): array
    {
        return $this->jobsDispatcher->dispatchPool($commands, $returnResult, $tag, $queue);
    }
}