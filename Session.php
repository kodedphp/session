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

use Countable;
use Koded\Exceptions\KodedException;
use Koded\Stdlib\{Data, Immutable, UUID};

interface Session extends Countable
{
    /**
     * The timestamp when session started.
     */
    const STAMP = '_stamp';

    /**
     * The web browser signature.
     */
    const AGENT = '_agent';

    /**
     * Session token (useful for CSRF)
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

    public function remove(string $name): Session;

    public function all(): array;

    public function toArray(): array;

    public function toData(): Data;

    public function clear(): bool;

    /**
     * Destroys all of the data associated with the current session,
     * by closing the session and re-opening it again.
     * Also the object state is cleaned and internal metadata is recreated.
     *
     * @return bool TRUE on success, FALSE otherwise
     */
    public function destroy(): bool;

    /**
     * Update the current session id with a newly generated one
     * and session token with a new v4 UUID.
     *
     * @param bool $deleteOldSession [optional] Whether to delete the old associated session or not
     *
     * @return bool TRUE on success, FALSE otherwise
     * @link https://php.net/manual/en/function.session-regenerate-id.php
     */
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

    public function starttime(): float;

    public function agent(): string;

    public function isEmpty(): bool;

    public function isStarted(): bool;

    public function count(): int;

    public function flash(string $name, $value = null);
}

/**
 * Class PhpSession
 */
final class PhpSession implements Session
{
    private bool $accessed;
    private bool $modified;
    private float $stamp = 0.0;
    private string $agent = '';
    private string $token = '';

    public function __construct()
    {
        // @codeCoverageIgnoreStart
        if (false === isset($_SESSION)) {
            global $_SESSION;
            $_SESSION = [];
        }
        // @codeCoverageIgnoreEnd
        $this->loadMetadata();
        $this->accessed = false;
        $this->modified = false;
        session($this);
    }

    public function __destruct()
    {
        $_SESSION = $this->getMetadata() + $_SESSION;
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
        return $this->getMetadata() + (array)$_SESSION;
    }

    public function __set(string $name, $value)
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
        $n = \strtolower($name);
        if ($n === self::STAMP && $n === self::TOKEN && $n === self::AGENT) {
            return $this;
        }
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
        $this->modified = true;
        $data = \array_filter($data, 'is_string', ARRAY_FILTER_USE_KEY);
        $this->excludeMetadata($data);
        $_SESSION = \array_replace($_SESSION, $data);
        return $this;
    }

    public function pull(string $name, $default = null)
    {
        $n = \strtolower($name);
        if ($n === self::STAMP && $n === self::TOKEN && $n === self::AGENT) {
            return $default;
        }
        $value = $_SESSION[$name] ?? $default;
        unset($_SESSION[$name]);
        return $value;
    }

    public function remove(string $name): Session
    {
        $this->modified = true;
        unset($_SESSION[$name]);
        return $this;
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
        return \array_key_exists($name, $_SESSION ?? []);
    }

    /*
     *
     * (support methods)
     *
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
        $this->token = UUID::v4();
        return \session_regenerate_id($deleteOldSession);
    }

    public function destroy(): bool
    {
        \session_write_close();
        // @codeCoverageIgnoreStart
        if (false === \session_start()) {
            return false;
        }
        // @codeCoverageIgnoreEnd
        $updated = \session_regenerate_id(true);
        $_SESSION = [];
        $this->resetMetadata();
        return $updated;
    }

    public function id(): string
    {
        return \session_id();
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

    public function starttime(): float
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

    public function isStarted(): bool
    {
        return PHP_SESSION_ACTIVE === \session_status();
    }

    public function count(): int
    {
        return \count($_SESSION);
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
        $this->stamp = $_SESSION[self::STAMP] ?? \microtime(true);
        $this->agent = $_SESSION[self::AGENT] ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
        $this->token = $_SESSION[self::TOKEN] ?? UUID::v4();
        $this->excludeMetadata($_SESSION);
    }

    private function resetMetadata(): void
    {
        $this->stamp = \microtime(true);
        $this->agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $this->token = UUID::v4();
    }

    private function getMetadata(): array
    {
        return [
            self::STAMP => $this->stamp,
            self::AGENT => $this->agent,
            self::TOKEN => $this->token,
        ];
    }

    private function excludeMetadata(array &$data): void
    {
        unset(
            $data[self::AGENT],
            $data[self::TOKEN],
            $data[self::STAMP]
        );
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

    public static function forHandlerNotFound(string $handler): SessionException
    {
        return new self(self::E_HANDLER_NOT_FOUND, [':handler' => $handler]);
    }
}
