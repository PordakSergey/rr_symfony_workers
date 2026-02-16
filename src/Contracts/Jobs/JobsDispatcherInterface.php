<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

interface JobsDispatcherInterface
{
    /**
     * @param object $command
     * @return mixed
     */
    public function dispatch(object $command): ?string;
}