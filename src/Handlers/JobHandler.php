<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobCommandInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

final class JobHandler implements JobHandlerInterface
{
    /**
     * @param MessageBusInterface $messageBus
     */
    public function __construct(
        protected MessageBusInterface $messageBus,
    )
    {
    }

    /**
     * @param ReceivedTaskInterface $task
     * @return void
     * @throws ExceptionInterface
     */
    public function handle(ReceivedTaskInterface $task): void
    {
        $taskClass = $task->getName();
        $command = new $taskClass();
        if ($command instanceof JobCommandInterface) {
            $command->setPayload(json_decode($task->getPayload(), true));
            $this->messageBus->dispatch($command);
        }
    }
}