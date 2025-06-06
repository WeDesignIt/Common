<?php

namespace WeDesignIt\Common\Http\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    /**
     * @param RequestInterface $request
     * @param callable(RequestInterface):ResponseInterface $next
     * @return ResponseInterface
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}
