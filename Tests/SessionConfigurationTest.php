<?php

namespace Koded\Session;

use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;

class SessionConfigurationTest extends TestCase
{

//    public function test_should_throw_exception_on_invalid_handler_name()
//    {
//        $this->expectException(SessionException::class);
//        $this->expectExceptionCode(SessionException::E_INVALID_HANDLER_NAME);
//        $this->expectExceptionMessage('Invalid configuration parameter for the session handler');
//
//        session_register_handler(new Config);
//    }

    public function test_expire_at_browser_close_should_set_cookie_lifetime_to_zero()
    {
        $current = ini_get('session.cookie_lifetime');

        $config = (new Config)->import([
            'session' => [
                'save_handler' => 'files',
                'expire_at_browser_close' => true,
            ]
        ]);

        $settings = new SessionConfiguration($config);

        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
        $this->assertEquals(0, $settings->cookie_lifetime);
        $this->assertEquals(0, session_get_cookie_params()['cookie_lifetime']);

        $this->assertNotEquals($current, $settings->get('cookie_lifetime'));
    }
}
