<?php

namespace Rr\Bundle\Workers\Jobs;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobCommandInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Options;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class JobsDispatcher
{
    /**
     * @param RPCFactory $rpcFactory
     * @param LoggerInterface $logger
     * @param SerializerInterface $serializer
     */
    public function __construct(
        protected RPCFactory          $rpcFactory,
        protected LoggerInterface     $logger,
        protected SerializerInterface $serializer,
    )
    {
    }

    /**
     * @param object $command
     * @param string $queueName
     * @param Options $options
     * @return string|null
     */
    public function dispatch(object $command, string $queueName = 'job', Options $options = new Options()): ?string
    {
        try {
            $jobs = new Jobs($this->rpcFactory::fromEnvironment(Environment::fromGlobals()));
            $queue = $jobs->connect($queueName);
            $task = $queue->create($command::class, $this->serializer->serialize($command, 'json'), $options);
            $sendTask = $queue->dispatch($task);

            return $sendTask->getId();
        } catch (JobsException|ExceptionInterface $e) {
            $this->logger->error('Error job: ' . $command::class . ' error: ' . $e->getMessage());
        }
        return null;
    }
}