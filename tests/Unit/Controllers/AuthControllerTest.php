<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\AuthController;
use App\Helpers\Logger;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthControllerTest extends TestCase
{
    private string $testSecretKey = 'test_secret_key_for_jwt';
    private string $testUsername = 'test_admin';
    private string $testPassword = 'test_password';
    private string $testPasswordHash;
    private $mockLogger;

    protected function setUp(): void
    {
        // Create password hash for testing
        $this->testPasswordHash = password_hash($this->testPassword, PASSWORD_DEFAULT);

        // Mock logger
        $this->mockLogger = $this->createMock(Logger::class);

        // Clean output buffer
        if (ob_get_level()) {
            ob_clean();
        }
    }

    protected function tearDown(): void
    {
        // Clean up output buffer after each test
        if (ob_get_level()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        // Restore php stream wrapper if it was mocked
        if (in_array('php', stream_get_wrappers())) {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Helper method to create controller with test credentials
     */
    private function createController(): AuthController
    {
        return new AuthController(
            $this->testSecretKey,
            $this->testUsername,
            $this->testPasswordHash,
            $this->mockLogger
        );
    }

    /**
     * Helper method to mock php://input
     */
    private function mockJsonInput(array $data): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestInputStreamWrapper::class);
        TestInputStreamWrapper::$data = json_encode($data);
    }

    /**
     * Test successful login with valid credentials
     */
    public function testLoginWithValidCredentials(): void
    {
        $this->mockJsonInput([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);

        // Verify token is valid
        $decoded = JWT::decode($data['token'], new Key($this->testSecretKey, 'HS256'));
        $this->assertEquals($this->testUsername, $decoded->user);
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void
    {
        $this->mockJsonInput([
            'username' => $this->testUsername,
            'password' => 'wrong_password'
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    /**
     * Test login with wrong username
     */
    public function testLoginWithWrongUsername(): void
    {
        $this->mockJsonInput([
            'username' => 'wrong_user',
            'password' => $this->testPassword
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    /**
     * Test login with missing username
     */
    public function testLoginWithMissingUsername(): void
    {
        $this->mockJsonInput([
            'password' => $this->testPassword
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid JSON or missing username/password', $data['error']);
    }

    /**
     * Test login with missing password
     */
    public function testLoginWithMissingPassword(): void
    {
        $this->mockJsonInput([
            'username' => $this->testUsername
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid JSON or missing username/password', $data['error']);
    }

    /**
     * Test login with empty credentials
     */
    public function testLoginWithEmptyCredentials(): void
    {
        $this->mockJsonInput([
            'username' => '',
            'password' => ''
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    /**
     * Test login with invalid JSON
     */
    public function testLoginWithInvalidJson(): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestInputStreamWrapper::class);
        TestInputStreamWrapper::$data = 'invalid json{';

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('Invalid JSON or missing username/password', $data['error']);
    }

    /**
     * Test JWT token structure
     */
    public function testJwtTokenStructure(): void
    {
        $this->mockJsonInput([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $token = $data['token'];

        // Decode and verify token structure
        $decoded = JWT::decode($token, new Key($this->testSecretKey, 'HS256'));

        $this->assertObjectHasProperty('iss', $decoded);
        $this->assertObjectHasProperty('iat', $decoded);
        $this->assertObjectHasProperty('exp', $decoded);
        $this->assertObjectHasProperty('user', $decoded);
        $this->assertEquals($this->testUsername, $decoded->user);
        $this->assertGreaterThan(time(), $decoded->exp);
    }

    /**
     * Test that logger is called on successful login
     */
    public function testLoggerCalledOnSuccessfulLogin(): void
    {
        // Set $_SERVER variables for logger
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';

        $this->mockLogger
            ->expects($this->once())
            ->method('info')
            ->with(
                'Successful login',
                $this->callback(function ($context) {
                    return is_array($context) &&
                           array_key_exists('ip', $context) &&
                           array_key_exists('user_agent', $context) &&
                           array_key_exists('time', $context);
                })
            );

        $this->mockJsonInput([
            'username' => $this->testUsername,
            'password' => $this->testPassword
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        // Verify output is not empty
        $this->assertNotEmpty($output);
    }

    /**
     * Test that logger is called on failed login
     */
    public function testLoggerCalledOnFailedLogin(): void
    {
        // Set $_SERVER variables for logger
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';

        $this->mockLogger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Failed login attempt',
                $this->callback(function ($context) {
                    return is_array($context) &&
                           array_key_exists('ip', $context) &&
                           array_key_exists('user_agent', $context) &&
                           array_key_exists('time', $context);
                })
            );

        $this->mockJsonInput([
            'username' => $this->testUsername,
            'password' => 'wrong_password'
        ]);

        $controller = $this->createController();

        ob_start();
        $controller->login();
        $output = ob_get_clean();

        // Verify output is not empty
        $this->assertNotEmpty($output);
    }
}

/**
 * Mock stream wrapper for testing file_get_contents('php://input')
 */
class TestInputStreamWrapper
{
    public static $data = '';
    private $position = 0;
    public $context;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $result = substr(self::$data, $this->position, $count);
        $this->position += strlen($result);
        return $result;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat()
    {
        return [];
    }

    public function url_stat($path, $flags)
    {
        return [];
    }
}
