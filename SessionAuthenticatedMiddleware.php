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

use Koded\Http\{ServerResponse, StatusCode};
use Koded\Stdlib\Interfaces\ConfigurationFactory;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\{MiddlewareInterface, RequestHandlerInterface};
use function Koded\Stdlib\json_serialize;


class SessionAuthenticatedMiddleware implements MiddlewareInterface
{
    public const AUTHENTICATED = 'authenticated';
    public const LOGIN_URI     = 'loginUri';

    private $redirectTo = '/';

    public function __construct(ConfigurationFactory $settings)
    {
        $this->redirectTo = $settings->get(self::LOGIN_URI, $this->redirectTo);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (true === ($_SESSION[self::AUTHENTICATED] ?? false)) {
            return $handler->handle($request);
        }

        // Ajax requests should be handled in the browser
        if ('XMLHTTPREQUEST' === strtoupper($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) {
            return (new ServerResponse(json_serialize([
                'location' => $this->redirectTo,
                'status'   => StatusCode::UNAUTHORIZED
            ]), StatusCode::UNAUTHORIZED));
        }

        return (new ServerResponse(null, StatusCode::TEMPORARY_REDIRECT))
            ->withHeader('Location', $this->redirectTo);
    }
}
