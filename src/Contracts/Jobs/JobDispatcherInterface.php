<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

use Rr\Bundle\Workers\Jobs\Response\JobResponse;

interface JobDispatcherInterface
{
    /**
     * @param object $command
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue Null means the dispatcher default
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): JobResponse;

    /**
     * @param object[] $commands
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue Null means the dispatcher default
     * @return JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): array;
}