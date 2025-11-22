<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;

interface IEmployeeService
{
    /**
     * Get all employees with pagination.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getEmployees(int $page, int $perPage, array $searchParams = []): array;

    /**
     * Get an employee by ID.
     *
     * @param int $id
     * @return Employee|null
     */
    public function getEmployeeById(int $id): ?Employee;

    /**
     * Create a new employee.
     *
     * @param array $data
     * @return Employee
     */
    public function createEmployee(array $data): Employee;

    /**
     * Update an existing employee.
     *
     * @param int $id
     * @param array $data
     * @return Employee|null
     */
    public function updateEmployee(int $id, array $data): ?Employee;

    /**
     * Delete an employee.
     *
     * @param int $id
     * @return bool
     */
    public function deleteEmployee(int $id): bool;
}
