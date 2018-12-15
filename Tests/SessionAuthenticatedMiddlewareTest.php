<?php

namespace Koded\Session;

use Koded\Http\{ServerRequest, ServerResponse, StatusCode};
use Koded\Stdlib\Config;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\{ResponseInterface, ServerRequestInterface};
use Psr\Http\Server\RequestHandlerInterface;

class SessionAuthenticatedMiddlewareTest extends TestCase
{

    /** @var SessionAuthenticatedMiddleware */
    private $middleware;

    public function test__construct()
    {
        $this->assertAttributeEquals('/signin', 'redirectTo', $this->middleware);
    }

    public function test_process_when_not_authenticated()
    {
        $handler = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new ServerResponse('hello');
            }
        };

        $this->assertFalse(isset($_SESSION[SessionAuthenticatedMiddleware::AUTHENTICATED]));

        $response = $this->middleware->process(new ServerRequest, $handler);

        $this->assertEquals('/signin', $response->getHeaderLine('location'));
        $this->assertSame(StatusCode::TEMPORARY_REDIRECT, $response->getStatusCode());
        $this->assertEmpty((string)$response->getBody());
    }

    public function test_process_ajax_when_not_authenticated()
    {
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XmlHttpRequest';

        $handler = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new ServerResponse();
            }
        };

        $this->assertFalse(isset($_SESSION[SessionAuthenticatedMiddleware::AUTHENTICATED]));

        $response = $this->middleware->process(new ServerRequest, $handler);

        $this->assertEquals('', $response->getHeaderLine('location'));
        $this->assertSame(StatusCode::UNAUTHORIZED, $response->getStatusCode());
        $this->assertSame('{"location":"\/signin","status":401}', (string)$response->getBody());
    }

    public function test_process_when_authenticated()
    {
        $_SESSION[SessionAuthenticatedMiddleware::AUTHENTICATED] = true;
        $handler                                                 = new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new ServerResponse('hello');
            }
        };

        $response = $this->middleware->process(new ServerRequest, $handler);

        $this->assertEquals('', $response->getHeaderLine('location'));
        $this->assertSame(StatusCode::OK, $response->getStatusCode());
        $this->assertEquals('hello', (string)$response->getBody());
    }

    protected function setUp()
    {
        $this->middleware = new SessionAuthenticatedMiddleware((new Config)->import([
            SessionAuthenticatedMiddleware::LOGIN_URI => '/signin',
        ]));
    }
}
