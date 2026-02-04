<?php

namespace Rr\Bundle\Workers\Contracts\Middlewares;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

interface MiddlewareInterface
{
    public function process(Request $request, HttpKernelInterface $next): \Iterator;
}