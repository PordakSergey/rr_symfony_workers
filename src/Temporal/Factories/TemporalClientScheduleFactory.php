<?php

namespace Rr\Bundle\Workers\Temporal\Factories;

use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\ScheduleClient;
use Temporal\Client\ScheduleClientInterface;

class TemporalClientScheduleFactory
{
    /**
     * @param ServiceClientInterface $serviceClient
     */
    public function __construct(
        protected ServiceClientInterface $serviceClient
    )
    {}

    /**
     * @return ScheduleClientInterface
     */
    public function make() : ScheduleClientInterface
    {
        return ScheduleClient::create($this->serviceClient);
    }
}