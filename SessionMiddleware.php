<?php
/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 */
namespace Koded\Session;

use Koded\Http\StatusCode;
use Koded\Stdlib\Configuration;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function session_start;
use function session_status;
use function session_write_close;

class SessionMiddleware implements MiddlewareInterface
{
    private array $options;

    public function __construct(Configuration $settings)
    {
        $this->options = session_register_custom_handler($settings)
            ->sessionParameters();
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler): ResponseInterface
    {
        if ('OPTIONS' === $request->getMethod()) {
            return $handler->handle($request);
        }
        session_start($this->options);
        $response = $handler->handle($request);
        if ('OPTIONS' === $request->getMethod()) {
            return $response;
        }
        if (PHP_SESSION_ACTIVE === session_status()
            && $response->getStatusCode() < StatusCode::INTERNAL_SERVER_ERROR) {
            session_write_close();
            session_start($this->options);
        }
        return $response;
    }
}
