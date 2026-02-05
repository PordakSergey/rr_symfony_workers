<?php

namespace Rr\Bundle\Workers\Handlers;

use Rr\Bundle\Workers\Contracts\Handlers\RequestHandlerInterface;
use Rr\Bundle\Workers\Contracts\Middlewares\MiddlewareInterface;
use Rr\Bundle\Workers\Http\HttpKernelRunner;
use Symfony\Component\HttpFoundation\Request;

class MiddlewareStackHandler implements RequestHandlerInterface
{
    public function __construct(
        private RequestHandlerInterface $kernelHandler,
        /**@var \SplStack<MiddlewareInterface> */
        private \SplStack               $middlewares = new \SplStack(),
    )
    {

    }

    /**
     * @param Request $request
     * @return \Iterator
     */
    public function handle(Request $request): \Iterator
    {
        $middlewares = clone $this->middlewares;

        $runner = new HttpKernelRunner($middlewares, $this->kernelHandler);

        yield $runner->handle($request);

        $runner->close();
    }

    /**
     * @param MiddlewareInterface $middleware
     * @return void
     */
    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->middlewares->push($middleware);
    }
}