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
use function Koded\Stdlib\{json_serialize, json_unserialize};

final class RedisHandler implements SessionHandlerInterface
{
    protected int $ttl;
    private \Redis $client;
    private array $settings;

    public function __construct(SessionConfiguration $settings)
    {
        //ini_set('session.gc_probability', '0');
//        if (1 > $this->ttl = (int)session_get_cookie_params()['lifetime']) {
            $this->ttl = (int)ini_get('session.gc_maxlifetime');
//        }
        $this->settings = [
            'prefix'  => (string)$settings->get('prefix', 'session.'),
            'host'    => (string)$settings->get('host'),
            'port'    => (int)$settings->get('port', 6379),
            'timeout' => (float)$settings->get('timeout', 0.0),
            'retry'   => (int)$settings->get('retry', 0),
            'db'      => (int)$settings->get('db', 0),

//            'serializer' => (string)$settings->get('serializer', 'json'),
//            'binary' => (string)$settings->get('binary', 'json'),
//            'ttl' => $this->ttl,
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
    public function gc($maxLifetime): bool
    {
        return true;
    }

    public function read($sessionId): string
    {
        return $this->client->get($sessionId) ?: '';

        //$_SESSION = (array)json_unserialize($this->client->get($sessionId) ?: '[]');
        //return '';
    }

    public function write($sessionId, $sessionData): bool
    {
//        error_log('-- (redis) cookie.lifetime: ' . session_get_cookie_params()['lifetime']);
//        error_log('-- (redis) ttl: ' . $this->ttl());

        return $this->client->setex($sessionId, $this->ttl(), $sessionData);
        //return $this->client->setex($sessionId, $this->ttl(), json_serialize($_SESSION));
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
