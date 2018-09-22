<?php

namespace Koded\Session\Handler;

use Koded\Session\{PhpSession, SessionTestCaseTrait};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use function Koded\Session\session_register_custom_handler;

class RedisHandlerTest extends TestCase
{

    use SessionTestCaseTrait;

    protected function setUp()
    {
        $config = (new Config)->import([
            'session' => [
                'name'                    => 'test',
                'save_handler'            => 'redis',
                'expire_at_browser_close' => false,

                'host'           => 'redis',
                'use_cookies'    => false,
                'cache_limiter'  => '',
                'gc_maxlifetime' => 60,
            ]
        ]);

        if (defined('CI')) {
            $config->host = '127.0.0.1';
        }

        $settings = session_register_custom_handler($config);
        session_start($settings->sessionParameters());

        $this->SUT = new PhpSession;

        $_SESSION['foo'] = 'bar';
    }
}
