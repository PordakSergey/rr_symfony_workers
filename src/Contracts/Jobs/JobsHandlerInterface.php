<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

use Rr\Bundle\Workers\Jobs\Responce\JobResponse;

interface JobsHandlerInterface
{
    /**
     * @param object $command
     * @param bool $returnResult
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false): JobResponse;

    /**
     * @param array $commands
     * @param bool $returnResult
     * @return JobResponse[]
     */
    public function dispatchPool(array $commands, bool $returnResult = false): array;
}