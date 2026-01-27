<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Handlers\JobHandlerInterface;

final class JobHandler implements JobHandlerInterface
{
    public function handle(string $name, array $payload, array $meta = []): void
    {

    }
}