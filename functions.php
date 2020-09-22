<?php declare(strict_types=1);
/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 */
namespace Koded\Session;

use Koded\Stdlib\Configuration;
use SessionHandlerInterface;

/**
 * Creates the Session object if not already instantiated,
 * or returns an instance of Session.
 *
 * @param Session $instance [optional]
 *
 * @return Session
 */
function session(Session $instance = null): Session
{
    static $session;
    if ($instance) {
        return $session = $instance;
    }
    return $session ??= new PhpSession;
}

/**
 * Creates and registers a new session handler.
 *
 * @param Configuration $configuration
 *
 * @return SessionConfiguration The session configuration instance
 * @throws SessionException
 */
function session_register_custom_handler(Configuration $configuration): SessionConfiguration
{
    $configuration = new SessionConfiguration($configuration);
    if (PHP_SESSION_ACTIVE !== session_status()) {
        session_set_cookie_params($configuration->cookieParameters());
        session_set_save_handler(session_create_custom_handler($configuration), false);
    }
    return $configuration;
}

/**
 * Factory for creating a session handler.
 *
 * @param SessionConfiguration $configuration
 *
 * @return SessionHandlerInterface
 */
function session_create_custom_handler(SessionConfiguration $configuration): SessionHandlerInterface
{
    $handler = __NAMESPACE__ . '\\Handler\\' . ucfirst($configuration->handler()) . 'Handler';
    if (false === class_exists($handler, true)) {
        throw SessionException::forHandlerNotFound($handler);
    }
    return new $handler($configuration);
}
