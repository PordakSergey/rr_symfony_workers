<?php

namespace Rr\Bundle\Workers\Contracts\Handlers;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;

interface JobHandlerInterface
{
    /**
     * @param ReceivedTaskInterface $task
     * @return void
     */
    public function handle(ReceivedTaskInterface $task): void;
}