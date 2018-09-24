<?php

namespace Koded\Session\Handler;

use Koded\Session\{PhpSession, SessionTestCaseTrait};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use function Koded\Session\session_register_custom_handler;

class MemcachedHandlerTest extends TestCase
{
    use SessionTestCaseTrait;

    protected function setUp()
    {
        if (false === extension_loaded('memcached')) {
            $this->markTestSkipped('Memcached extension is not loaded.');
        }

        $servers = [['memcached', 11211]];

        if (getenv('CI')) {
            $servers = [['127.0.0.1', 11211]];
        }

        $this->SUT = new PhpSession;

        $config = (new Config)->import([
            'session' => [
                'name'                    => 'test',
                'save_handler'            => 'memcached',
                'expire_at_browser_close' => false,

                'servers' => $servers,

                'options' => [
                    \Memcached::OPT_DISTRIBUTION => null,
                    \Memcached::OPT_PREFIX_KEY   => 'sess.'
                ],

                'use_cookies'    => false,
                'cache_limiter'  => '',
                'gc_maxlifetime' => 60,
            ]
        ]);

        $settings = session_register_custom_handler($config);
        session_start($settings->sessionParameters());

        $_SESSION['foo'] = 'bar';
    }
}
