<?php

namespace Koded\Session;

use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;

class SessionConfigurationTest extends TestCase
{

    public function test_expire_at_browser_close_should_set_cookie_lifetime_to_zero()
    {
        $current = ini_get('session.cookie_lifetime');

        $config = (new Config)->import([
            'session' => [
                'save_handler'            => 'files',
                'expire_at_browser_close' => true,
            ]
        ]);

        $settings = new SessionConfiguration($config);

        $this->assertEquals(0, ini_get('session.cookie_lifetime'));
        $this->assertEquals(0, $settings->get('cookie_lifetime'));
        $this->assertEquals(0, session_get_cookie_params()['lifetime']);

        $this->assertNotEquals($current, $settings->get('cookie_lifetime'));
    }
}
