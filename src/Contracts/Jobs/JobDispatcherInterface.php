<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

interface JobDispatcherInterface
{
    /**
     * @param object $command
     * @return string|null
     */
    public function dispatch(object $command): ?string;
}