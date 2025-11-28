<?php

declare(strict_types=1);

namespace App\Services;

interface IEmailService
{
    /**
     * Send welcome email to new employee
     *
     * @param array $employeeData Employee data containing name, email, facility_names, address
     * @return bool True if email sent successfully, false otherwise
     */
    public function sendEmployeeWelcomeEmail(array $employeeData): bool;
}
