<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Session;

use Koded\Stdlib\Interfaces\ConfigurationFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};


class SessionMiddleware implements MiddlewareInterface
{
    public const SESSION_STARTED = 'sessionStarted';

    public function __construct(ConfigurationFactory $settings)
    {
        $options = session_register_custom_handler($settings)->sessionParameters();
        session_start($options);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request  = $request->withAttribute(self::SESSION_STARTED, PHP_SESSION_ACTIVE === session_status());
        $response = $handler->handle($request);

        if (500 !== $response->getStatusCode()) {
            session_write_close();
        }

        return $response;
    }
}
