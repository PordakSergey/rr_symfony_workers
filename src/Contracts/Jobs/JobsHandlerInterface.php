<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

use Rr\Bundle\Workers\Jobs\Response\JobResponse;

interface JobsHandlerInterface
{
    /**
     * @param object $command
     * @param bool $returnResult
     * @param string $tag
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger'): JobResponse;

    /**
     * @param array $commands
     * @param bool $returnResult
     * @param string $tag
     * @return JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger'): array;
}