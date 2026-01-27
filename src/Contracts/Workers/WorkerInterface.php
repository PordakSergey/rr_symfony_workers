<?php

namespace Rr\Bundle\Workers\Contracts\Workers;

interface WorkerInterface
{
    /**
     * @return void
     */
    public function run(): void;
}