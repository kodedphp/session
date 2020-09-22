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
use function Koded\Caching\simple_cache_factory;

final class MemcachedHandler implements SessionHandlerInterface
{
    protected int $ttl;
    private \Memcached $client;
    private array $settings;

    public function __construct(SessionConfiguration $settings)
    {
        //ini_set('session.gc_probability', '0');
        $this->settings = $this->configuration($settings);
        if (1 > $this->ttl = (int)session_get_cookie_params()['lifetime']) {
            $this->ttl = (int)ini_get('session.gc_maxlifetime');
        }
    }

    public function close(): bool
    {
        $this->client->quit();
        return true;
    }

    public function destroy($sessionId): bool
    {
        return $this->client->delete($sessionId);
    }

    public function open($savePath, $sessionId): bool
    {
        $this->client = simple_cache_factory('memcached', $this->settings)->client();
        return true;
    }

    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?: '';
    }

    public function write($sessionId, $sessionData): bool
    {
        return $this->client->set($sessionId, $sessionData, time() + $this->ttl);
    }

    /**
     * @codeCoverageIgnore
     */
    public function gc($maxLifetime): bool
    {
        return true;
    }

    private function configuration(SessionConfiguration $settings): array
    {
        return [
            'id'      => (string)$settings->get('id', $settings->get('name', ini_get('session.name'))),
            'servers' => (array)$settings->get('servers', [['127.0.0.1', 11211]]),
            'options' => (array)$settings->get('options', []) + [
                    \Memcached::OPT_PREFIX_KEY => (string)$settings->get('prefix', 'session.')
                ]
        ];
    }
}
