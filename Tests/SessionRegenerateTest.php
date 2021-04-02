<?php

namespace Tests\Koded\Session;

use Koded\Session\PhpSession;
use Koded\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionRegenerateTest extends TestCase
{
    public function test_regenerate_metadata()
    {
        $err = error_reporting(0);
        session_start();

        $session = new PhpSession;
        $token = $session->token();
        $agent = $session->agent();
        $stamp = $session->starttime();

        $session->regenerate();

        $this->assertArrayNotHasKey(Session::AGENT, $_SESSION);
        $this->assertArrayNotHasKey(Session::STAMP, $_SESSION);
        $this->assertArrayNotHasKey(Session::TOKEN, $_SESSION);

        $this->assertNotSame($token, $session->token(), 'Token is changed');
        $this->assertSame($agent, $session->agent(), 'Agent is not changed');
        $this->assertSame($stamp, $session->starttime(), 'Timestamp is not changed');

        error_reporting($err);
    }
}
