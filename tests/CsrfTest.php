<?php

use PHPUnit\Framework\TestCase;
use Lithe\Http\Request;
use Lithe\Http\Response;
use Lithe\Support\Session;
use function Lithe\Middleware\Security\csrf;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Start session before each test
        session_start();
    }

    protected function tearDown(): void
    {
        // Clear session after each test
        Session::destroy();
    }

    /**
     * @runInSeparateProcess
     */
    public function testTokenGeneration()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $middleware = csrf();

        $next = function () {
            // Pass-through for the next middleware
        };

        // Execute the middleware
        $middleware($request, $response, $next);

        // Assert that the token has been stored in the session
        $this->assertTrue(Session::has('_token'));
        $this->assertNotEmpty(Session::get('_token'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testTokenExpiration()
    {
        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);
        $middleware = csrf(['expire' => 1]); // Set expiration to 1 second

        $next = function () {
            // Pass-through for the next middleware
        };

        // Generate token and store it in the session
        $middleware($request, $response, $next);

        // Simulate token expiration
        sleep(2);

        // Assert that the token is no longer valid
        $this->assertFalse($request->csrf->exists());
    }

    /**
 * @runInSeparateProcess
 */
public function testTokenValidation()
{
    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $middleware = csrf();

    $next = function () {
        // Pass-through for the next middleware
    };

    // Generate token and store it in the session
    $middleware($request, $response, $next);

    // Mock token in the request body (ensure method 'input' is mocked)
    $request->expects($this->once())
        ->method('input')
        ->willReturn(Session::get('_token'));

    // Assert that the token validation is valid
    $this->assertTrue($request->csrf->verifyToken(Session::get('_token')));
}

/**
 * @runInSeparateProcess
 */
public function testInvalidToken()
{
    $request = $this->createMock(Request::class);
    $response = $this->createMock(Response::class);
    $middleware = csrf();

    $next = function () {
        // Pass-through for the next middleware
    };

    // Generate token and store it in the session
    $middleware($request, $response, $next);

    // Mock an invalid token in the request body (ensure method 'input' is mocked)
    $request->expects($this->once())
        ->method('input')
        ->willReturn('invalid_token');

    // Assert that the invalid token does not pass validation
    $this->assertFalse($request->csrf->verifyToken('invalid_token'));
}

}
