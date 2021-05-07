<?php

namespace Koded\Session;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};

class SessionAuthMiddleware implements MiddlewareInterface
{
    public const ENTITY_NAME = 'profile';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ((($entity = session()->get(static::ENTITY_NAME)) && \is_object($entity))
            && \method_exists($entity, 'getToken')) {
            $request = $request
                ->withHeader('Authorization', (string)\call_user_func([$entity, 'getToken']))
                ->withAttribute('@user', $entity);
        }
        return $handler->handle($request);
    }
}
