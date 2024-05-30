<?php

namespace Tests\Koded\Session;

use Koded\Session\SessionConfiguration;
use Koded\Session\SessionException;
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use function Koded\Session\session;
use function Koded\Session\session_create_custom_handler;
use function Koded\Session\session_register_custom_handler;

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
        $config = (new Config)->import([
            'session' => [
                'save_handler' => 'files',

                'use_cookies'     => true,
                'cookie_lifetime' => 120,
                'cookie_path'     => '/tmp/sessions',
                'cookie_secure'   => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]
        ]);

        session_register_custom_handler($config);

        $this->assertEquals([
            'lifetime' => 120,
            'path'     => '/tmp/sessions',
            'domain'   => '',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ], session_get_cookie_params());
    }
}
