<?php

namespace Tests\Koded\Session;

use Koded\Session\SessionConfiguration;
use Koded\Session\SessionMiddleware;
use Koded\Http\{ServerRequest, ServerResponse};
use Koded\Stdlib\Config;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddlewareTest extends TestCase
{
    private SessionMiddleware $middleware;

    public function test_should_start_the_session_and_close_the_session()
    {
        $request = new ServerRequest;
        $this->assertNull($request->getAttribute(SessionMiddleware::SESSION_STARTED));

        $this->middleware->process($request, new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                Assert::assertTrue($request->getAttribute(SessionMiddleware::SESSION_STARTED), 'Session is started');
                return new ServerResponse;
            }
        });

        $this->assertSame(PHP_SESSION_NONE, session_status());
    }

    public function test_should_start_the_session_and_keep_the_session()
    {
        $request = new ServerRequest;
        $this->assertNull($request->getAttribute(SessionMiddleware::SESSION_STARTED));

        $this->middleware->process($request, new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                Assert::assertTrue($request->getAttribute(SessionMiddleware::SESSION_STARTED), 'Session is started');
                return new ServerResponse('', 500);
            }
        });

        $this->assertSame(PHP_SESSION_ACTIVE, session_status());
    }

    protected function setUp(): void
    {
        $this->markTestSkipped('WIP: need more research...');

        $settings = new SessionConfiguration((new Config)->import([
            'session' => ['use_cookies' => false]
        ]));

        $this->middleware = new SessionMiddleware($settings);
    }

    protected function tearDown(): void
    {
        session_write_close();
    }
}
