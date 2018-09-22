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

use Koded\Http\{StatusCode, ServerResponse};
use Koded\Stdlib\Interfaces\ConfigurationFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};


class NotAuthenticatedMiddleware implements MiddlewareInterface
{

    public const SESSION_AUTHENTICATED = 'authenticated';
    public const SESSION_REDIRECT_TO   = 'login_uri';

    private $redirectTo = '/';

    public function __construct(ConfigurationFactory $configuration)
    {
        $this->redirectTo = $configuration->get(self::SESSION_REDIRECT_TO, '/');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (true !== $_SESSION[self::SESSION_AUTHENTICATED]) {
            // Ajax requests should be handled in the browser
            if (strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHTTPREQUEST') {
                return (new ServerResponse(json_encode([
                    'location' => $this->redirectTo,
                    'status'   => StatusCode::UNAUTHORIZED
                ], JSON_UNESCAPED_SLASHES), StatusCode::UNAUTHORIZED));
            }

            return (new ServerResponse(null, StatusCode::TEMPORARY_REDIRECT))
                ->withHeader('Location', $this->redirectTo);
        }

        return $handler->handle($request);
    }
}
