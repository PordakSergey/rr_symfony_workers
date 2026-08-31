<?php

namespace Rr\Bundle\Workers\Jobs\Services\JobsDispatcher;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Jobs\JobDispatcherInterface;
use Rr\Bundle\Workers\Factories\RPCFactory;
use Rr\Bundle\Workers\Jobs\Response\JobResponse;
use Spiral\RoadRunner\Environment;
use Spiral\RoadRunner\Jobs\Exception\JobsException;
use Spiral\RoadRunner\Jobs\Jobs;
use Spiral\RoadRunner\Jobs\Options;
use Spiral\RoadRunner\KeyValue\Exception\NotImplementedException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RrJobDispatcher implements JobDispatcherInterface
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
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue RoadRunner pipeline name
     * @return JobResponse
     */
    public function dispatch(object $command, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): JobResponse
    {
        try {
            $jobs = new Jobs($this->rpcFactory::fromEnvironment(Environment::fromGlobals()));
            $pipeline = $jobs->connect($queue ?? 'flights.storage');
            $task = $pipeline->create($command::class, $this->serializer->serialize($command, 'json'), new Options());
            $sendTask = $pipeline->dispatch($task);

            return new JobResponse( $sendTask->getId(), null);
        } catch (JobsException|ExceptionInterface $e) {
            $this->logger->error('Error job: ' . $command::class . ' error: ' . $e->getMessage());
            return new JobResponse('', null);
        }
    }

    /**
     * @param array $commands
     * @param bool $returnResult
     * @param string $tag
     * @param string|null $queue
     * @return array|JobResponse[]
     * @throws NotImplementedException
     */
    public function dispatchPool(array $commands, bool $returnResult = false, string $tag = 'messenger', ?string $queue = null): array
    {
        throw new NotImplementedException('Not implemented');
    }
}