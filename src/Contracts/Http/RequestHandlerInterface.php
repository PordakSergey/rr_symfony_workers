<?php

namespace Rr\Bundle\Workers\Contracts\Http;

use Symfony\Component\HttpFoundation\Request;

interface RequestHandlerInterface
{
    /**
     * @param Request $request
     * @return \Iterator
     */
    public function handle(Request $request): \Iterator;
}