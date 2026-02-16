<?php

namespace Rr\Bundle\Workers\Jobs\Services\JobsDispatcher;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Options;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RrJobDispatcher implements JobDispatcherInterface
{
    public function __construct(
        protected RPCFactory          $rpcFactory,
        protected LoggerInterface     $logger,
        protected SerializerInterface $serializer,
    )
    {

    }

    /**
     * @param object $command
     * @return string
     * @throws JobsException
     * @throws ExceptionInterface
     */
    public function dispatch(object $command): ?string
    {
        try {
            $jobs = new Jobs($this->rpcFactory::fromEnvironment(Environment::fromGlobals()));
            $queue = $jobs->connect('job');
            $task = $queue->create($command::class, $this->serializer->serialize($command, 'json'), new Options());
            $sendTask = $queue->dispatch($task);

            return $sendTask->getId();
        } catch (JobsException|ExceptionInterface $e) {
            $this->logger->error('Error job: ' . $command::class . ' error: ' . $e->getMessage());
        }

        return null;
    }
}