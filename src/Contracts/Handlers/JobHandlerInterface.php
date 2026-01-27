<?php

namespace Rr\Bundle\Workers\Contracts\Handlers;

interface JobHandlerInterface
{
    public function handle(string $name, array $payload, array $meta = []) : void;
}