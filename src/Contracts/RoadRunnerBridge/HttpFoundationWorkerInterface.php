<?php

namespace Rr\Bundle\Workers\Contracts\RoadRunnerBridge;

use Spiral\RoadRunner\WorkerInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

interface HttpFoundationWorkerInterface
{
    /**
     * @return WorkerInterface
     */
    public function getWorker(): WorkerInterface;

    /**
     * @return SymfonyRequest|null
     */
    public function waitRequest(): ?SymfonyRequest;

    /**
     * @param SymfonyResponse $symfonyResponse
     * @return void
     */
    public function respond(SymfonyResponse $symfonyResponse): void;
}