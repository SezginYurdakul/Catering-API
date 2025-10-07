<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\AuthMiddleware;
use App\Plugins\Http\Exceptions\Unauthorized;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddlewareTest extends TestCase
{
    private string $secretKey;
    private AuthMiddleware $middleware;

    protected function setUp(): void
    {
        $this->secretKey = 'test_secret_key_for_jwt_tokens_in_testing';
        $this->middleware = new AuthMiddleware();
        
        // Clear session and server globals
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
        }
        $_SERVER = [];
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        $_SESSION = [];
    }

    private function generateValidJWT(array $payload = []): string
    {
        $defaultPayload = [
            'user' => 'test_user',
            'iat' => time(),
            'exp' => time() + 3600 // Expires in 1 hour
        ];
        
        $payload = array_merge($defaultPayload, $payload);
        
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function testHandleWithValidToken(): void
    {
        $token = $this->generateValidJWT();
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Should not throw any exception
        $this->middleware->handle();
        
        // Check that user info is stored in session
        $this->assertEquals('test_user', $_SESSION['user']);
    }

    public function testHandleWithValidTokenDifferentCase(): void
    {
        $token = $this->generateValidJWT();
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Should not throw any exception
        $this->middleware->handle();
        
        // Verify session is set
        $this->assertArrayHasKey('user', $_SESSION);
    }

    public function testHandleWithMissingAuthorizationHeader(): void
    {
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithEmptyAuthorizationHeader(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = '';
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithInvalidBearerFormat(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'InvalidFormat some_token';
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithMissingBearer(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'some_token_without_bearer';
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithExpiredToken(): void
    {
        $expiredPayload = [
            'user' => 'test_user',
            'iat' => time() - 7200, // Issued 2 hours ago
            'exp' => time() - 3600  // Expired 1 hour ago
        ];
        
        $expiredToken = JWT::encode($expiredPayload, $this->secretKey, 'HS256');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $expiredToken;
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithInvalidToken(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid_jwt_token';
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithTamperedToken(): void
    {
        $validToken = $this->generateValidJWT();
        $tamperedToken = $validToken . 'tampered';
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tamperedToken;
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithTokenSignedWithDifferentKey(): void
    {
        $differentKey = 'different_secret_key';
        $tokenWithDifferentKey = JWT::encode([
            'user' => 'test_user',
            'iat' => time(),
            'exp' => time() + 3600
        ], $differentKey, 'HS256');
        
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $tokenWithDifferentKey;
        
        $this->expectException(Unauthorized::class);
        $this->expectExceptionMessage('Unauthorized');
        
        $this->middleware->handle();
    }

    public function testHandleWithExtraSpacesInHeader(): void
    {
        $token = $this->generateValidJWT();
        
        $_SERVER['HTTP_AUTHORIZATION'] = '  Bearer   ' . $token . '  ';
        
        $this->expectException(Unauthorized::class);
        // This should fail because the regex expects exact format
        
        $this->middleware->handle();
    }

    public function testHandleWithCustomUserData(): void
    {
        $customPayload = [
            'user' => [
                'id' => 123,
                'username' => 'john_doe',
                'role' => 'admin'
            ]
        ];
        
        $token = $this->generateValidJWT($customPayload);
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Should not throw any exception
        $this->middleware->handle();
        
        // Check that complex user data is stored correctly
        // Note: JWT decode returns stdClass, so we compare with stdClass
        $expected = (object) $customPayload['user'];
        $this->assertEquals($expected, $_SESSION['user']);
    }

    public function testHandleWithMinimumViableToken(): void
    {
        $minimalPayload = [
            'user' => 'minimal_user'
        ];
        
        $token = JWT::encode($minimalPayload, $this->secretKey, 'HS256');
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
        
        // Should not throw any exception
        $this->middleware->handle();
        
        $this->assertEquals('minimal_user', $_SESSION['user']);
    }
}