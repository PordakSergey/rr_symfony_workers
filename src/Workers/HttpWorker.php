<?php

namespace Rr\Bundle\Workers\Workers;

use Psr\Log\LoggerInterface;
use Rr\Bundle\Workers\Contracts\RoadRunnerBridge\HttpFoundationWorkerInterface;
use Rr\Bundle\Workers\Contracts\Workers\WorkerInterface;
use Rr\Bundle\Workers\Event\WorkerStartEvent;
use Rr\Bundle\Workers\Event\WorkerStopEvent;
use Rr\Bundle\Workers\Handlers\MiddlewareStackHandler;
use Rr\Bundle\Workers\Traits\ErrorRenderer;
use Rr\Bundle\Workers\Traits\GeneratorConsumes;
use Symfony\Component\HttpKernel\KernelInterface;
use Spiral\RoadRunner\Environment;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class HttpWorker implements WorkerInterface
{
    use ErrorRenderer;
    use GeneratorConsumes;

    /**
     * @param KernelInterface $kernel
     * @param HttpFoundationWorkerInterface $httpFoundationWorker
     * @param MiddlewareStackHandler $requestHandler
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private KernelInterface               $kernel,
        private HttpFoundationWorkerInterface $httpFoundationWorker,
        private MiddlewareStackHandler        $requestHandler,
        private LoggerInterface               $logger,
        private EventDispatcherInterface      $eventDispatcher,
    )
    {
        $this->initErrorRenderer($this->kernel);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $this->eventDispatcher->dispatch(new WorkerStartEvent());
        $resetter = $this->kernel->getContainer()->has('services_resetter') ? $this->kernel->getContainer()->get('services_resetter') : null;

        while ($request = $this->httpFoundationWorker->waitRequest()) {
            $send = false;
            try {
                $gen = $this->requestHandler->handle($request);
                $response = $gen->current();

                $this->httpFoundationWorker->respond($response);

                if ($this->kernel instanceof TerminableInterface) {
                    $this->kernel->terminate($request, $response);
                }

                $send = true;
                $this->consumes($gen);
            } catch (\Throwable $e) {
                if (!$send) {
                    $response = ($this->renderError)($e);
                    $this->httpFoundationWorker->respond($response);
                }

                $this->logger->error('An error occured: ' . $e->getMessage(), ['throwable' => $e]);
                $this->httpFoundationWorker->getWorker()->stop();
            } finally {
                $resetter->reset();
            }
        }

        $this->eventDispatcher->dispatch(new WorkerStopEvent());
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function supports(string $name): bool
    {
        return $name == Environment\Mode::MODE_HTTP;
    }
}