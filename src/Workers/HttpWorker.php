<?php

namespace Rr\Bundle\Workers\Workers;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\Http\RequestHandlerInterface;
use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\HttpFoundationWorkerInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Traits\ErrorRenderer;
use Rr\Bundle\Workers\Traits\GeneratorConsumes;
use Symfony\Component\HttpKernel\KernelInterface;

final class HttpWorker implements WorkerInterface
{
    use ErrorRenderer;
    use GeneratorConsumes;

    /**
     * @param KernelInterface $kernel
     * @param HttpFoundationWorkerInterface $httpFoundationWorker
     * @param RequestHandlerInterface $requestHandler
     * @param LoggerInterface $logger
     */
    public function __construct(
        private KernelInterface $kernel,
        private HttpFoundationWorkerInterface $httpFoundationWorker,
        private RequestHandlerInterface $requestHandler,
        private LoggerInterface $logger
    )
    {
        $this->initErrorRenderer($this->kernel);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        while ($request = $this->httpFoundationWorker->waitRequest()) {
            $send = false;
            try {
                $gen = $this->requestHandler->handle($request);
                $response = $gen->current();

                $this->httpFoundationWorker->respond($response);

                $send = true;
                $this->consumes($gen);
            } catch (\Throwable $e) {
                if (!$send) {
                    $response = ($this->renderError)($e);
                    $this->httpFoundationWorker->respond($response);
                }

                $this->logger->error('An error occured: '.$e->getMessage(), ['throwable' => $e]);
                $this->httpFoundationWorker->getWorker()->stop();
            }
        }
    }
}