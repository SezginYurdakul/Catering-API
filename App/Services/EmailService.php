<?php

declare(strict_types=1);

namespace App\Services;

use Resend;
use Exception;

class EmailService implements IEmailService
{
    private $resend;
    private string $fromAddress;
    private string $fromName;

    public function __construct()
    {
        $apiKey = $_ENV['RESEND_API_KEY'] ?? '';
        if (empty($apiKey)) {
            throw new Exception('RESEND_API_KEY is not configured');
        }

        $this->resend = Resend::client($apiKey);
        $this->fromAddress = $_ENV['MAIL_FROM_ADDRESS'] ?? 'onboarding@resend.dev';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Catering Management';
    }

    /**
     * Send welcome email to new employee
     */
    public function sendEmployeeWelcomeEmail(array $employeeData): bool
    {
        try {
            $result = $this->resend->emails->send([
                'from' => "{$this->fromName} <{$this->fromAddress}>",
                'to' => [$employeeData['email']],
                'subject' => 'Welcome to Catering Management!',
                'html' => $this->getWelcomeEmailTemplate($employeeData),
                'text' => $this->getWelcomeEmailTextVersion($employeeData),
            ]);

            error_log("Resend email sent successfully. ID: " . json_encode($result));
            return true;
        } catch (Exception $e) {
            error_log("Email sending failed: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * HTML email template
     */
    private function getWelcomeEmailTemplate(array $data): string
    {
        $name = htmlspecialchars($data['name']);
        $facilityNames = htmlspecialchars($data['facility_names'] ?? 'Not assigned');
        $address = htmlspecialchars($data['address'] ?? 'Not provided');
        $email = htmlspecialchars($data['email']);

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .content h2 {
            color: #2563eb;
            margin-top: 0;
        }
        .info-box {
            background: #f9fafb;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #2563eb;
            border-radius: 4px;
        }
        .info-item {
            margin: 10px 0;
        }
        .info-label {
            font-weight: bold;
            color: #2563eb;
            display: inline-block;
            width: 180px;
        }
        .footer {
            background: #1f2937;
            color: #9ca3af;
            padding: 20px;
            text-align: center;
            font-size: 12px;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin: 8px 0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🎉 Welcome to Catering Management!</h1>
        </div>

        <div class="content">
            <h2>Hello {$name}!</h2>

            <p>We're excited to have you join our team. You've been successfully registered in our Catering Management System.</p>

            <div class="info-box">
                <h3 style="margin-top: 0; color: #2563eb;">Your Information:</h3>
                <div class="info-item">
                    <span class="info-label">Name:</span> {$name}
                </div>
                <div class="info-item">
                    <span class="info-label">Email:</span> {$email}
                </div>
                <div class="info-item">
                    <span class="info-label">Assigned Facilities:</span> {$facilityNames}
                </div>
                <div class="info-item">
                    <span class="info-label">Address:</span> {$address}
                </div>
            </div>

            <p><strong>Next Steps:</strong></p>
            <ul>
                <li>Check your email regularly for updates</li>
                <li>Contact your facility manager for onboarding details</li>
                <li>Review our company policies and procedures</li>
            </ul>

            <p>If you have any questions, please don't hesitate to reach out to your manager or HR department.</p>

            <p>Best regards,<br>
            <strong>Catering Management Team</strong></p>
        </div>

        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
            <p>&copy; 2024 Catering Management. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Plain text version (for email clients that don't support HTML)
     */
    private function getWelcomeEmailTextVersion(array $data): string
    {
        $name = $data['name'];
        $facilityNames = $data['facility_names'] ?? 'Not assigned';
        $address = $data['address'] ?? 'Not provided';

        return <<<TEXT
Welcome to Catering Management!

Hello {$name}!

We're excited to have you join our team. You've been successfully registered in our Catering Management System.

Your Information:
- Name: {$name}
- Email: {$data['email']}
- Assigned Facilities: {$facilityNames}
- Address: {$address}

Next Steps:
- Check your email regularly for updates
- Contact your facility manager for onboarding details
- Review our company policies and procedures

If you have any questions, please don't hesitate to reach out to your manager or HR department.

Best regards,
Catering Management Team

---
This is an automated message. Please do not reply to this email.
© 2024 Catering Management. All rights reserved.
TEXT;
    }
}
