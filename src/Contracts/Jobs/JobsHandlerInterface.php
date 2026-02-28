<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

interface JobsHandlerInterface
{
    /**
     * @param object $command
     * @return string|null
     */
    public function dispatch(object $command): ?string;
}