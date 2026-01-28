<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class JobHandler implements JobHandlerInterface
{
    public function __construct(
        protected MessageBusInterface $messageBus,
    )
    {
    }

    /**
     * @param ReceivedTaskInterface $task
     * @return void
     */
    public function handle(ReceivedTaskInterface $task): void
    {
        $test = 0;
    }
}