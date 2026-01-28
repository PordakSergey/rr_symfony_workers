<?php

namespace Rr\Bundle\Workers\Contracts\Jobs;

interface JobCommandInterface
{
    /**
     * @return mixed
     */
    public function getPayload(): mixed;

    /**
     * @param array $payload
     * @return void
     */
    public function setPayload(array $payload): void;
}