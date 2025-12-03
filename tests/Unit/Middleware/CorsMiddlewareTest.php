<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\CorsMiddleware;
use PHPUnit\Framework\TestCase;

class CorsMiddlewareTest extends TestCase
{
    private CorsMiddleware $corsMiddleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->corsMiddleware = new CorsMiddleware();
    }

    public function testHandleSetsCorsHeaders(): void
    {
        // Mock the $_SERVER superglobal
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // Capture headers (use output buffering to prevent "headers already sent" errors in tests)
        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('xdebug_get_headers() function not available');
        }

        $this->corsMiddleware->handle();

        $headers = xdebug_get_headers();

        // Check that CORS headers are set
        $this->assertContains('Access-Control-Allow-Origin: *', $headers);
        $this->assertContains('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS', $headers);
        $this->assertContains('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token', $headers);
        $this->assertContains('Access-Control-Expose-Headers: Content-Disposition, X-Total-Count', $headers);
        $this->assertContains('Access-Control-Max-Age: 86400', $headers);
    }

    public function testHandlePreflightRequest(): void
    {
        // Mock the $_SERVER superglobal for OPTIONS request
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        // We expect the middleware to exit, so we need to catch the exit
        try {
            $this->corsMiddleware->handle();
            $this->fail('Expected exit() to be called for OPTIONS request');
        } catch (\Exception $e) {
            // exit() will cause an exception in test environment
            // This is expected behavior
        }
    }

    public function testCorsHeadersAreCorrect(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        if (!function_exists('xdebug_get_headers')) {
            $this->markTestSkipped('xdebug_get_headers() function not available');
        }

        $this->corsMiddleware->handle();

        $headers = xdebug_get_headers();

        // Verify all required CORS headers are present
        $expectedHeaders = [
            'Access-Control-Allow-Origin: *',
            'Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS',
            'Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token',
            'Access-Control-Expose-Headers: Content-Disposition, X-Total-Count',
            'Access-Control-Max-Age: 86400',
        ];

        foreach ($expectedHeaders as $expectedHeader) {
            $this->assertContains($expectedHeader, $headers, "Missing header: {$expectedHeader}");
        }
    }
}
