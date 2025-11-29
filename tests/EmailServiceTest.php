<?php

declare(strict_types=1);

namespace Tests;

use App\Services\EmailService;
use PHPUnit\Framework\TestCase;

/**
 * Email Service Test
 *
 * Tests for email sending functionality using Resend API
 */
class EmailServiceTest extends TestCase
{
    private EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();

        // Load environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
            $dotenv->load();
        }

        $this->emailService = new EmailService();
    }

    /**
     * Test: EmailService can be instantiated
     */
    public function testCanInstantiateEmailService(): void
    {
        $this->assertInstanceOf(EmailService::class, $this->emailService);
    }

    /**
     * Test: RESEND_API_KEY is configured
     */
    public function testResendApiKeyIsConfigured(): void
    {
        $this->assertNotEmpty($_ENV['RESEND_API_KEY'], 'RESEND_API_KEY must be set in .env');
        $this->assertStringStartsWith('re_', $_ENV['RESEND_API_KEY'], 'API key should start with re_');
    }

    /**
     * Test: Email from address is configured
     */
    public function testEmailFromAddressIsConfigured(): void
    {
        $this->assertNotEmpty($_ENV['MAIL_FROM_ADDRESS'], 'MAIL_FROM_ADDRESS must be set');
        $this->assertMatchesRegularExpression(
            '/^.+@.+\..+$/',
            $_ENV['MAIL_FROM_ADDRESS'],
            'MAIL_FROM_ADDRESS must be valid email format'
        );
    }

    /**
     * Test: Send welcome email with valid data
     *
     * Note: This sends a real test email. Make sure you have a verified email in Resend.
     */
    public function testSendWelcomeEmailWithValidData(): void
    {
        $employeeData = [
            'name' => 'Test Employee',
            'email' => 'test@example.com', // Change to your verified test email
            'facility_names' => 'Test Facility 1, Test Facility 2',
            'address' => '123 Test Street, Test City'
        ];

        $result = $this->emailService->sendEmployeeWelcomeEmail($employeeData);

        $this->assertTrue($result, 'Email should be sent successfully');
    }

    /**
     * Test: Send welcome email with minimal data
     */
    public function testSendWelcomeEmailWithMinimalData(): void
    {
        $employeeData = [
            'name' => 'Minimal Employee',
            'email' => 'minimal@example.com', // Change to your verified test email
        ];

        $result = $this->emailService->sendEmployeeWelcomeEmail($employeeData);

        $this->assertTrue($result, 'Email should be sent with minimal data');
    }

    /**
     * Test: Email fails gracefully with invalid email
     */
    public function testEmailFailsGracefullyWithInvalidEmail(): void
    {
        $employeeData = [
            'name' => 'Invalid Email Employee',
            'email' => 'not-a-valid-email',
        ];

        $result = $this->emailService->sendEmployeeWelcomeEmail($employeeData);

        $this->assertFalse($result, 'Email should fail with invalid email address');
    }

    /**
     * Test: Email template includes employee data
     *
     * This tests the HTML template generation without sending
     */
    public function testEmailTemplateIncludesEmployeeData(): void
    {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('getWelcomeEmailTemplate');
        $method->setAccessible(true);

        $employeeData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'facility_names' => 'Facility A, Facility B',
            'address' => '456 Oak Street'
        ];

        $template = $method->invoke($this->emailService, $employeeData);

        $this->assertStringContainsString('John Doe', $template);
        $this->assertStringContainsString('john@example.com', $template);
        $this->assertStringContainsString('Facility A, Facility B', $template);
        $this->assertStringContainsString('456 Oak Street', $template);
        $this->assertStringContainsString('Welcome', $template);
    }

    /**
     * Test: Plain text version includes employee data
     */
    public function testPlainTextVersionIncludesEmployeeData(): void
    {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('getWelcomeEmailTextVersion');
        $method->setAccessible(true);

        $employeeData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'facility_names' => 'Test Facility',
        ];

        $plainText = $method->invoke($this->emailService, $employeeData);

        $this->assertStringContainsString('Jane Smith', $plainText);
        $this->assertStringContainsString('jane@example.com', $plainText);
        $this->assertStringContainsString('Test Facility', $plainText);
        $this->assertStringNotContainsString('<html>', $plainText, 'Plain text should not contain HTML');
    }

    /**
     * Test: HTML is properly escaped in template
     */
    public function testHtmlIsEscapedInTemplate(): void
    {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('getWelcomeEmailTemplate');
        $method->setAccessible(true);

        $employeeData = [
            'name' => '<script>alert("XSS")</script>',
            'email' => 'test@example.com',
        ];

        $template = $method->invoke($this->emailService, $employeeData);

        $this->assertStringNotContainsString('<script>', $template, 'Scripts should be escaped');
        $this->assertStringContainsString('&lt;script&gt;', $template, 'HTML should be entity encoded');
    }
}
