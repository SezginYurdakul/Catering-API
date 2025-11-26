<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\Logger;

class LoggerSanitizationTest extends TestCase
{
    private Logger $logger;
    private string $testLogFile;

    protected function setUp(): void
    {
        $this->testLogFile = __DIR__ . '/../../../logs/test_sanitization.log';
        // Clean up log file before each test
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
        $this->logger = new Logger($this->testLogFile);
    }

    protected function tearDown(): void
    {
        // Clean up log file after test
        if (file_exists($this->testLogFile)) {
            unlink($this->testLogFile);
        }
    }

    public function testValidationErrorSanitizesPassword(): void
    {
        // Simulate a validation error with sensitive data
        $_POST = [];

        // Mock php://input
        $validationErrors = ['username' => 'Username is required'];
        $endpoint = '/api/login';

        $this->logger->logValidationError($validationErrors, $endpoint);

        // Read the log file
        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // Assert password is redacted
        $this->assertEquals('[REDACTED]', $logData['context']['request_data']['password'] ?? null);
    }

    public function testSanitizationRedactsPasswordField(): void
    {
        $context = [
            'username' => 'admin',
            'password' => 'secret123',
            'email' => 'admin@example.com'
        ];

        $this->logger->info('Test message', $context);

        // Read the log
        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // Password should be redacted
        $this->assertEquals('[REDACTED]', $logData['context']['password']);
        // Username and email should remain
        $this->assertEquals('admin', $logData['context']['username']);
        $this->assertEquals('admin@example.com', $logData['context']['email']);
    }

    public function testSanitizationRedactsTokens(): void
    {
        $context = [
            'user_id' => 123,
            'access_token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...',
            'refresh_token' => 'refresh_abc123',
            'api_key' => 'sk_live_abc123'
        ];

        $this->logger->info('Auth event', $context);

        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // All tokens should be redacted
        $this->assertEquals('[REDACTED]', $logData['context']['access_token']);
        $this->assertEquals('[REDACTED]', $logData['context']['refresh_token']);
        $this->assertEquals('[REDACTED]', $logData['context']['api_key']);
        // User ID should remain
        $this->assertEquals(123, $logData['context']['user_id']);
    }

    public function testSanitizationWorksRecursively(): void
    {
        $context = [
            'user' => [
                'id' => 1,
                'username' => 'admin',
                'credentials' => [
                    'password' => 'secret123',
                    'old_password' => 'old_secret'
                ]
            ],
            'request' => [
                'headers' => [
                    'authorization' => 'Bearer token123'
                ]
            ]
        ];

        $this->logger->info('Nested test', $context);

        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // Nested passwords should be redacted
        $this->assertEquals('[REDACTED]', $logData['context']['user']['credentials']['password']);
        $this->assertEquals('[REDACTED]', $logData['context']['user']['credentials']['old_password']);
        $this->assertEquals('[REDACTED]', $logData['context']['request']['headers']['authorization']);

        // Non-sensitive data should remain
        $this->assertEquals(1, $logData['context']['user']['id']);
        $this->assertEquals('admin', $logData['context']['user']['username']);
    }

    public function testSanitizationIsCaseInsensitive(): void
    {
        $context = [
            'PASSWORD' => 'secret',
            'Password' => 'secret',
            'AccessToken' => 'token123',
            'API_KEY' => 'key123'
        ];

        $this->logger->info('Case test', $context);

        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // All should be redacted regardless of case
        $this->assertEquals('[REDACTED]', $logData['context']['PASSWORD']);
        $this->assertEquals('[REDACTED]', $logData['context']['Password']);
        $this->assertEquals('[REDACTED]', $logData['context']['AccessToken']);
        $this->assertEquals('[REDACTED]', $logData['context']['API_KEY']);
    }

    public function testSanitizationHandlesPartialMatches(): void
    {
        $context = [
            'user_password' => 'secret',
            'new_password_confirmation' => 'secret',
            'bearer_token' => 'token123',
            'username' => 'admin' // Should NOT be redacted
        ];

        $this->logger->info('Partial match test', $context);

        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        // Fields containing sensitive keywords should be redacted
        $this->assertEquals('[REDACTED]', $logData['context']['user_password']);
        $this->assertEquals('[REDACTED]', $logData['context']['new_password_confirmation']);
        $this->assertEquals('[REDACTED]', $logData['context']['bearer_token']);

        // Username should remain (doesn't contain sensitive keywords)
        $this->assertEquals('admin', $logData['context']['username']);
    }

    public function testNonArrayContextRemainsUnchanged(): void
    {
        $context = [
            'string_value' => 'test',
            'int_value' => 123,
            'bool_value' => true,
            'null_value' => null
        ];

        $this->logger->info('Non-array test', $context);

        $logContent = file_get_contents($this->testLogFile);
        $logData = json_decode($logContent, true);

        $this->assertEquals('test', $logData['context']['string_value']);
        $this->assertEquals(123, $logData['context']['int_value']);
        $this->assertTrue($logData['context']['bool_value']);
        $this->assertNull($logData['context']['null_value']);
    }
}
