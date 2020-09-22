<?php

namespace Koded\Session;

use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function test_session_function_singleton()
    {
        $this->assertSame(session(), session());
    }

    public function test_should_throw_exception_on_invalid_handler_class()
    {
        $this->expectException(SessionException::class);
        $this->expectExceptionCode(0);
        $this->expectExceptionMessage('Failed to load the session handler class. Requested Koded\Session\Handler\StdClassHandler');

        $config = (new Config)->import([
            'session' => [
                'save_handler' => \stdClass::class, // invalid session handler
            ]
        ]);

        session_create_custom_handler(new SessionConfiguration($config));
    }

    public function test_should_register_session_cookie()
    {
        $this->markTestSkipped();

        $config = (new Config)->import([
            'session' => [
                'save_handler' => 'files',

                'use_cookies'     => true,
                'cookie_lifetime' => 120,
                'cookie_path'     => '/tmp',
                'cookie_secure'   => true,
                'cookie_httponly' => true,
            ]
        ]);

        session_register_custom_handler($config);

        if (version_compare(PHP_MINOR_VERSION, '3', '<')) {
            $this->assertEquals([
                'lifetime' => 120,
                'path'     => '/tmp',
                'domain'   => '',
                'secure'   => true,
                'httponly' => true,
            ], session_get_cookie_params());
        } else {
            $this->assertEquals([
                'lifetime' => 120,
                'path'     => '/tmp',
                'domain'   => '',
                'secure'   => true,
                'httponly' => true,
                'samesite' => '',
            ], session_get_cookie_params());
        }
    }
}
