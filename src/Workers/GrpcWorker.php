<?php

namespace Rr\Bundle\Workers\Workers;

use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;

final class GrpcWorker implements WorkerInterface
{

    public function run(): void
    {

    }

    public static function supports(string $name): bool
    {
        return true;
    }
}