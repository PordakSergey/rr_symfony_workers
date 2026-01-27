<?php

namespace Rr\Bundle\Workers\RoadRunnerBridge;

use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\JobsFoundationWorkerInterface;
use Spiral\RoadRunner\Jobs\ConsumerInterface;

final class JobsFoundationWorker implements JobsFoundationWorkerInterface
{
    public function __construct(
       private ConsumerInterface $consumer,
    )
    {
    }

    public function getConsumer(): ConsumerInterface
    {
        return $this->consumer;
    }
}