<?php

namespace Rr\Bundle\Workers\Http;

use Rr\Bundle\Workers\Contracts\Handlers\RequestHandlerInterface;
use Rr\Bundle\Workers\Contracts\Middlewares\MiddlewareInterface;
use Rr\Bundle\Workers\Traits\GeneratorConsumes;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Exclude]
class HttpKernelRunner implements HttpKernelInterface
{
    use GeneratorConsumes;

    /**
     * @param \SplStack $middlewares
     * @param RequestHandlerInterface $handler
     * @param \SplStack $iterators
     */
    public function __construct(
        /** @var \SplStack<MiddlewareInterface> */
        private \SplStack $middlewares,
        private RequestHandlerInterface $handler,
        /** @var \SplStack<\Iterator<Response>> */
        private \SplStack $iterators = new \SplStack(),
    ){
    }

    /**
     * @param Request $request
     * @param int $type
     * @param bool $catch
     * @return Response
     */
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        if ($this->middlewares->isEmpty()) {
            $gen = $this->handler->handle($request);

            return $this->getResponse($gen, \get_class($this->handler).'::handle()');
        }

        /** @var MiddlewareInterface $middleware */
        $middleware = $this->middlewares->shift();

        $gen = $middleware->process($request, $this);

        return $this->getResponse($gen, \get_class($middleware).'::process()');
    }

    /**
     * @param \Iterator $iterator
     * @param string $caller
     * @return Response
     */
    private function getResponse(\Iterator $iterator, string $caller): Response
    {
        $this->iterators->push($iterator);

        $resp = $iterator->current();

        if (!($resp instanceof Response)) {
            throw new \UnexpectedValueException(\sprintf("'%s' first yield should be a '%s' object, '%s' given", $caller, Response::class, \is_object($resp) ? \get_class($resp) : \gettype($resp)));
        }

        return $resp;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        foreach ($this->iterators as $gen) {
            $this->consumes($gen);
        }
    }
}