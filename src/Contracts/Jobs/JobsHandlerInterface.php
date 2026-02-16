<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

interface JobsHandlerInterface
{
    /**
     * @param object $command
     * @return string
     */
    public function dispatch(object $command): ?string;
}