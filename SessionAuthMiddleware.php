<?php

namespace Koded\Session;

use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function call_user_func;
use function is_object;
use function method_exists;

class SessionAuthMiddleware implements MiddlewareInterface
{
    private string $entityName = 'profile';

    public function __construct(null|string $entityName = null)
    {
        $this->entityName ??= $entityName;
    }

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        if (null === $entity = session()->get($this->entityName)) {
            return $handler->handle($request);
        }
        if (is_object($entity)) {
            $request = $request->withAttribute('@user', $entity);
        }
        if (method_exists($entity, 'getAuthHeader')) {
            $request = $request->withHeader('Authorization',
                (string)call_user_func([$entity, 'getAuthHeader'])
            );
        }
        return $handler->handle($request);
    }
}
