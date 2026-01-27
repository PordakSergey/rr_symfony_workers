<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Handlers\RequestHandlerInterface;
use Rr\Bundle\Workers\Helpers\BasicAuthHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\TerminableInterface;

final class KernelHandler implements RequestHandlerInterface
{
    private \Closure $startTimeReset;

    /**
     * @param HttpKernelInterface $kernel
     * @param BasicAuthHandler $basicAuthHandler
     */
    public function __construct(
        private HttpKernelInterface $kernel,
        private BasicAuthHandler $basicAuthHandler,
    )
    {
        if ($kernel instanceof Kernel && $kernel->isDebug()) {
            $this->startTimeReset = (function () use ($kernel) {
                $kernel->startTime = microtime(true);
            })->bindTo(null, Kernel::class);
        } else {
            $this->startTimeReset = function () {
            };
        }
    }

    /**
     * @param Request $request
     * @return \Iterator
     * @throws \Exception
     */
    public function handle(Request $request): \Iterator
    {
        ($this->startTimeReset)();

        $this->basicAuthHandler->handle($request);

        $response = $this->kernel->handle($request);

        yield $response;

        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
    }
}