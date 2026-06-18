<?php

namespace Rr\Bundle\Workers\Jobs\Services\Handlers;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;
use Rr\Bundle\Workers\Jobs\Contracts\Services\Map\JobMapInterface;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class JobHandler implements JobHandlerInterface
{
    /**
     * @param SerializerInterface $serializer
     * @param JobMapInterface $jobMap
     * @param MessageBusInterface $messageBus
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected SerializerInterface $serializer,
        protected JobMapInterface     $jobMap,
        protected MessageBusInterface $messageBus,
        protected LoggerInterface     $logger,
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
            $taskName = $task->getName();
            $job = $this->jobMap->getByName($taskName);

            $command = $this->serializer->deserialize($task->getPayload(), $job::class, 'json');
            $this->messageBus->dispatch($command);
        } catch (ExceptionInterface|\Symfony\Component\Serializer\Exception\ExceptionInterface $e) {
            $this->logger->error('Failed dispatch job', ['exception' => $e->getMessage()]);
        }
    }
}