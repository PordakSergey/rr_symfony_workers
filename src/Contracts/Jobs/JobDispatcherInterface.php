<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

use Rr\Bundle\Workers\Jobs\Response\JobResponse;

interface JobDispatcherInterface
{
    /**
     * @param object $command
     * @param bool $returnResult
     * @param string $tag
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger'): JobResponse;

    /**
     * @param object[] $commands
     * @param bool $returnResult
     * @param string $tag
     * @return JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger'): array;
}