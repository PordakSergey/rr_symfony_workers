<?php

namespace Rr\Bundle\Workers\Jobs;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobCommandInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Options;

class JobsDispatcher
{
    public function __construct(
        private RPCFactory      $rpcFactory,
        private LoggerInterface $logger,

    )
    {
    }

    /**
     * @param string $queueName
     * @param JobCommandInterface $command
     * @param Options $options
     * @return string|null
     */
    public function dispatch(string $queueName, JobCommandInterface $command, Options $options = new Options()): ?string
    {
        try {
            $jobs = new Jobs($this->rpcFactory::fromEnvironment(Environment::fromGlobals()));
            $queue = $jobs->connect($queueName);
            $task = $queue->create($command::class, json_encode($command->getPayload()), $options);
            $sendTask = $queue->dispatch($task);

            return $sendTask->getId();
        } catch (JobsException $e) {
            $this->logger->error('Error job: ' . $command::class . ' payload: ' . json_encode($command->getPayload()) . ' error: ' . $e->getMessage());
        }
        return null;
    }
}