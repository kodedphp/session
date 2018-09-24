<?php

namespace Koded\Session\Handler;

use Koded\Session\{PhpSession, SessionTestCaseTrait};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use function Koded\Session\session_register_custom_handler;

class FilesHandlerTest extends TestCase
{
    use SessionTestCaseTrait;

    protected function setUp()
    {
        $this->SUT = new PhpSession;
        $config    = new Config;

        $config->import([
            'session' => [
                'save_handler' => 'files',
                'name'         => 'test',

                'use_cookies'    => false,
                'cache_limiter'  => '',
                //                'cookie_path'    => '/tmp',
                'gc_maxlifetime' => 60,
            ]
        ]);

        $settings = session_register_custom_handler($config);
        session_start($settings->sessionParameters());

        $_SESSION['foo'] = 'bar';
    }
}
