<?php

namespace Tests\Koded\Session\Handler;

use Koded\Session\PhpSession;
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use Tests\Koded\Session\SessionTestCaseTrait;
use function Koded\Session\session_register_custom_handler;

class RedisHandlerTest extends TestCase
{
    use SessionTestCaseTrait;

    protected function setUp(): void
    {
        if (false === extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension is not loaded');
        }

        $config = (new Config)->import([
            'session' => [
                'name'                    => 'test',
                'save_handler'            => 'redis',
                'expire_at_browser_close' => false,
                'use_cookies'             => false,
                'cache_limiter'           => '',
                'gc_maxlifetime'          => 60,

                'host' => 'redis'
            ]
        ]);

        $settings = session_register_custom_handler($config);
        session_start($settings->sessionParameters());

        $this->SUT = new PhpSession;

        $_SESSION['foo'] = 'bar';
    }
}
