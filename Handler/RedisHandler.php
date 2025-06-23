<?php
/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 */
namespace Koded\Session\Handler;

use Koded\Session\SessionConfiguration;
use SessionHandlerInterface;
use function ini_get;
use function Koded\Caching\simple_cache_factory;
use function trim;

final class RedisHandler implements SessionHandlerInterface
{
    protected int $ttl;
    private \Redis $client;
    private array $settings;

    public function __construct(SessionConfiguration $settings)
    {
        //ini_set('session.gc_probability', '0');
        //if (1 > $this->ttl = (int)session_get_cookie_params()['lifetime']) {
            $this->ttl = (int)ini_get('session.gc_maxlifetime');
        //}
        $this->settings = [
            'prefix'  => trim($settings->get('prefix') ?: $settings->get('name') ?: 'session', '.') . '.',
            'host'    => (string)$settings->get('host'),
            'port'    => (int)$settings->get('port', 6379),
            'timeout' => (float)$settings->get('timeout', 0.0),
            'retry'   => (int)$settings->get('retry', 0),
            'db'      => (int)$settings->get('db', 0),

            'serializer' => $settings->get('serializer'),
            'binary' => $settings->get('binary'),
        ];
    }

    public function close(): bool
    {
        $this->client->close();
        return true;
    }

    public function destroy($sessionId): bool
    {
        return $this->client->del($sessionId) > 0;
    }

    /**
     * @codeCoverageIgnore
     */
    public function gc($maxLifetime): int|false
    {
        return true;
    }

    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?: '';
    }

    public function write($sessionId, $sessionData): bool
    {
        return $this->client->setex($sessionId, $this->ttl(), $sessionData);
    }

    public function open($savePath, $sessionId): bool
    {
        $this->client = simple_cache_factory('redis', $this->settings)->client();
        return true;
    }

    private function ttl(): int
    {
        return (int)ini_get('session.gc_maxlifetime');
    }
}
