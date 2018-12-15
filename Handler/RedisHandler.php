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

namespace Koded\Session\Handler;

use Koded\Session\SessionConfiguration;
use SessionHandlerInterface;
use function Koded\Caching\simple_cache_factory;


final class RedisHandler implements SessionHandlerInterface
{
    /** @var int */
    protected $ttl;

    /** @var \Redis */
    private $client;

    public function __construct(SessionConfiguration $settings)
    {
        $this->ttl    = (int)$settings->get('gc_maxlifetime', ini_get('session.gc_maxlifetime'));
        $this->client = simple_cache_factory('redis', $this->configuration($settings))->client();
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy($sessionId): bool
    {
        return $this->client->del($sessionId) > 0;
    }

    /**
     * @codeCoverageIgnore
     */
    public function gc($maxLifetime): bool
    {
        return true;
    }

    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?: '';
    }

    public function write($sessionId, $sessionData): bool
    {
        if ($this->ttl > 0) {
            return $this->client->setex($sessionId, $this->ttl, $sessionData);
        }

        return $this->client->set($sessionId, $sessionData, $this->ttl);
    }

    public function open($savePath, $sessionId): bool
    {
        return true;
    }

    private function configuration(SessionConfiguration $settings): array
    {
        return [
            'prefix'  => (string)$settings->get('prefix', 'session:'),
            'host'    => (string)$settings->get('host'),
            'port'    => (int)$settings->get('port', 6379),
            'timeout' => (float)$settings->get('timeout', 0.0),
            'retry'   => (int)$settings->get('retry', 0),
            'db'      => (int)$settings->get('db', 0),
        ];
    }
}
