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

use Koded\Exceptions\KodedException;
use Koded\Stdlib\{Immutable, UUID};
use Koded\Stdlib\Interfaces\Data;


interface Session
{

    /**
     * The time session has started for the last request.
     */
    const STAMP = '_stamp';

    /**
     * The web browser signature.
     */
    const AGENT = '_agent';

    /**
     * CSRF token
     */
    const TOKEN = '_token';

    /**
     * Flash data
     */
    const FLASH = '_flash';

    public function id(): string;

    public function get(string $name, $default = null);

    public function set(string $name, $value): Session;

    public function pull(string $name, $default = null);

    public function add(string $name, $value): void;

    public function import(array $data): Session;

    public function remove(string $name): void;

    public function all(): array;

    public function toArray(): array;

    public function toData(): Data;

    public function clear(): bool;

    public function destroy(): bool;

    public function regenerate(bool $deleteOldSession = false): bool;

    /**
     * Exchange the session data for another one.
     *
     * @param array $data The new array or object to exchange with the current session data
     *
     * @return array The old session data
     */
    public function replace(array $data): array;

    public function has(string $name): bool;

    public function accessed(): bool;

    public function modified(): bool;

    public function token(): string;

    public function starttime(): int;

    public function agent(): string;

    public function isEmpty(): bool;

    public function count(): int;

    public function flash(string $name, $value = null);
}

/**
 * Class PhpSession
 */
final class PhpSession implements Session
{
    /**
     * @var bool
     */
    private $accessed = false;

    /**
     * @var bool
     */
    private $modified = false;

    /**
     * @var int
     */
    private $stamp = 0;

    /**
     * @var string
     */
    private $agent = '';

    /**
     * @var string
     */
    private $token = '';

    public function __construct()
    {
        if (false === isset($_SESSION)) {
            global $_SESSION;
            $_SESSION = [];
        }

        $this->loadMetadata();
        $this->accessed = false;
        $this->modified = false;
        session($this);
    }

    public function __destruct()
    {
        $_SESSION[self::STAMP] = $this->stamp;
        $_SESSION[self::AGENT] = $this->agent;
        $_SESSION[self::TOKEN] = $this->token;
    }

    public function get(string $name, $default = null)
    {
        $this->accessed = true;

        return $_SESSION[$name] ?? $default;
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function all(): array
    {
        $this->accessed = true;

        return (array)$_SESSION + $this->getMetadata();
    }

    function __set(string $name, $value)
    {
        return $this->set($name, $value);
    }

    public function toArray(): array
    {
        $this->accessed = true;

        return $_SESSION ?? [];
    }

    public function toData(): Data
    {
        $this->accessed = true;

        return new Immutable($_SESSION ?? []);
    }

    /*
     *
     * (mutator methods)
     *
     */

    public function set(string $name, $value): Session
    {
        $this->modified  = true;
        $_SESSION[$name] = $value;

        return $this;
    }

    public function add(string $name, $value): void
    {
        $this->has($name) || $this->set($name, $value);
    }

    public function import(array $data): Session
    {
        $data     = array_filter($data, 'is_string', ARRAY_FILTER_USE_KEY);
        $_SESSION = array_replace($_SESSION, $data);

        $this->modified = true;

        return $this;
    }

    public function pull(string $name, $default = null)
    {
        $value = $_SESSION[$name] ?? $default;
        unset($_SESSION[$name]);

        return $value;
    }

    public function remove(string $name): void
    {
        $this->modified = true;
        unset($_SESSION[$name]);
    }

    public function flash(string $name, $value = null)
    {
        if (null === $value) {
            $value = $this->pull(self::FLASH);
        } else {
            $_SESSION[self::FLASH][$name] = $value;
        }

        $this->modified = true;

        return $value;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $_SESSION ?? []);
    }

    /*
     *
     * (support methods)
     *
     */

    /**
     * Exchange the session data for another one.
     *
     * @param array $data The new array or object to exchange with the current session data
     *
     * @return array The old session data
     */
    public function replace(array $data): array
    {
        $oldSession = $_SESSION;
        $_SESSION   = [];
        $this->import($data);

        return $oldSession;
    }

    public function clear(): bool
    {
        $_SESSION       = [];
        $this->modified = true;

        return empty($_SESSION);
    }

    public function regenerate(bool $deleteOldSession = false): bool
    {
        return session_regenerate_id($deleteOldSession);
    }

    public function destroy(): bool
    {
        // FIXME
        $updated = session_regenerate_id(true);
        $this->replace([]);
        $this->resetMetadata();

        return $updated;
    }

    public function id(): string
    {
        return session_id();
    }

    public function accessed(): bool
    {
        return $this->accessed;
    }

    public function modified(): bool
    {
        return $this->modified;
    }

    public function token(): string
    {
        return $this->token;
    }

    public function starttime(): int
    {
        return $this->stamp;
    }

    public function agent(): string
    {
        return $this->agent;
    }

    public function isEmpty(): bool
    {
        return 0 === $this->count();
    }

    public function count(): int
    {
        return count($_SESSION);
    }

    /*
     *
     * Session metadata
     *
     */

    /**
     * move CSRF token, start time and user agent into object properties.
     */
    private function loadMetadata(): void
    {
        $this->stamp = $this->pull(self::STAMP, time());
        $this->agent = $this->pull(self::AGENT, $_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->token = $this->pull(self::TOKEN, UUID::v4());
    }

    private function resetMetadata(): void
    {
        $_SESSION[self::STAMP] = time();
        $_SESSION[self::AGENT] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION[self::TOKEN] = UUID::v4();
    }

    private function getMetadata(): array
    {
        return [
            self::STAMP => $this->stamp,
            self::AGENT => $this->agent,
            self::TOKEN => $this->token,
        ];
    }
}

/**
 * Class SessionException
 */
class SessionException extends KodedException
{
    private const E_HANDLER_NOT_FOUND = 0;

    protected $messages = [
        self::E_HANDLER_NOT_FOUND => 'Failed to load the session handler class. Requested :handler',
    ];

    public static function forNotFoundHandler(string $handler): SessionException
    {
        return new self(self::E_HANDLER_NOT_FOUND, [':handler' => $handler]);
    }
}
