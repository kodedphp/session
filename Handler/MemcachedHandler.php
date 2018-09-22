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

use Koded\Caching\Client\MemcachedClient;
use Koded\Caching\Configuration\MemcachedConfiguration;
use Koded\Session\SessionConfiguration;
use Memcached;
use SessionHandlerInterface;


final class MemcachedHandler implements SessionHandlerInterface
{

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var Memcached
     */
    private $client;

    public function __construct(SessionConfiguration $settings)
    {
        $this->ttl    = (int)$settings->get('gc_maxlifetime', ini_get('session.gc_maxlifetime'));
        $this->client = (new MemcachedClient(new Memcached, $this->configuration($settings)))->client();
    }

    public function close(): bool
    {
        return true;
    }

    public function destroy($sessionId): bool
    {
        return $this->client->delete($sessionId);
    }

    public function open($savePath, $sessionId): bool
    {
        return true;
    }

    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?: '';
    }

    public function write($sessionId, $sessionData): bool
    {
        return $this->client->set($sessionId, $sessionData, $this->ttl + time());
    }

    /**
     * @codeCoverageIgnore
     */
    public function gc($maxLifetime): bool
    {
        return true;
    }

    private function configuration(SessionConfiguration $settings): MemcachedConfiguration
    {
        return new MemcachedConfiguration([
            'id'      => $settings->get('id', $settings->get('name', ini_get('session.name'))),
            'servers' => $settings->get('servers', []),
            'options' => $settings->get('options', []) + [
                    Memcached::OPT_PREFIX_KEY => $settings->prefix ?? 'session.'
                ]
        ]);
    }
}
