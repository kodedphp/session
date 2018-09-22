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

    public const SESSION_NAME    = 'session';
    public const SESSION_STARTED = 'session_started';

    /**
     * @var array Options for session_start()
     * @see http://php.net/manual/en/session.configuration.php
     */
    private $options = [];
    private $class;

    public function __construct(ConfigurationFactory $settings)
    {
        $this->options = session_register_custom_handler($settings)->sessionParameters();
        $this->class   = $settings->get('class', PhpSession::class);

        if (false === is_a($this->class, Session::class, true)) {
            throw SessionException::forInvalidClass($this->class);
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        session_write_close();

        if (PHP_SESSION_ACTIVE !== session_status() /* && 'cli' !== php_sapi_name() */) {
            $request = $request
                ->withAttribute(self::SESSION_STARTED, session_start($this->options))
                ->withAttribute(self::SESSION_NAME, new $this->class);
        }

        $response = $handler->handle($request);

        if (500 !== $response->getStatusCode()) {
            session_write_close();
        }

        return $response;
    }
}
