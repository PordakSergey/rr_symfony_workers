<?php

namespace Rr\Bundle\Workers\Contracts\Workers;

interface WorkerInterface
{
    /**
     * @return void
     */
    public function run(): void;

    /**
     * @param string $name
     * @return bool
     */
    public static function supports(string $name): bool;
}