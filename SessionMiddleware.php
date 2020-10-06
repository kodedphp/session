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

class SessionMiddleware implements MiddlewareInterface
{
    public const SESSION_STARTED = 'sessionStarted';
    public const SESSION_EXPIRE_IN = 'X-Session-ExpireIn';

    private array $options;

    public function __construct(Configuration $settings)
    {
        $this->options = session_register_custom_handler($settings)->sessionParameters();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            return $handler->handle($request);
        }

        session_start($this->options);
        $request = $request->withAttribute(self::SESSION_STARTED, PHP_SESSION_ACTIVE === session_status());

        $response = $handler->handle($request);

//        $expireIn = $response->getHeaderLine(self::SESSION_EXPIRE_IN);
//        if ($response->getStatusCode() < StatusCode::INTERNAL_SERVER_ERROR) {
            session_write_close();
            session_start();
//        }

        return $response;
    }
}
