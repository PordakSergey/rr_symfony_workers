<?php

namespace Rr\Bundle\Workers\Contracts\RoadRunnerBridge;

use Spiral\RoadRunner\Jobs\ConsumerInterface;

interface JobsFoundationWorkerInterface
{
    /**
     * @return ConsumerInterface
     */
    public function getConsumer(): ConsumerInterface;
}