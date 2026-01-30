<?php

namespace Rr\Bundle\Workers\Handlers;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class JobHandler implements JobHandlerInterface
{
    /**
     * @param SerializerInterface $serializer
     * @param MessageBusInterface $messageBus
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected SerializerInterface $serializer,
        protected MessageBusInterface $messageBus,
        protected LoggerInterface       $logger,
    )
    {
    }

    /**
     * @param ReceivedTaskInterface $task
     * @return void
     */
    public function handle(ReceivedTaskInterface $task): void
    {
        try {
            $taskClass = $task->getName();
            $command = $this->serializer->deserialize($task->getPayload(), $taskClass, 'json');
            $this->messageBus->dispatch($command);
        } catch (ExceptionInterface|\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            $this->logger->error('Failed dispatch job', ['exception' => $e->getMessage()]);
        }
    }
}