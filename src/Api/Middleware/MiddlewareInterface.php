<?php

namespace WeDesignIt\Common\Api\Middleware;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface;
}
