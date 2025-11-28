<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use App\Repositories\FacilityRepository;
use App\Helpers\PaginationHelper;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\DatabaseException;

class EmployeeService implements IEmployeeService
{
    private EmployeeRepository $employeeRepository;
    private FacilityRepository $facilityRepository;
    private EmailService $emailService;

    public function __construct(
        EmployeeRepository $employeeRepository,
        FacilityRepository $facilityRepository,
        EmailService $emailService
    ) {
        $this->employeeRepository = $employeeRepository;
        $this->facilityRepository = $facilityRepository;
        $this->emailService = $emailService;
    }

    /**
     * Get all employees with pagination.
     */
    public function getEmployees(int $page, int $perPage, array $searchParams = []): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $whereData = $this->buildWhereClause($searchParams);
            $whereClause = $whereData['whereClause'];
            $bind = $whereData['bind'];

            $employeeData = $this->employeeRepository->getEmployees($whereClause, $bind, $perPage, $offset);

            if ($whereClause === '1') {
                $totalEmployees = $this->employeeRepository->getTotalEmployeesCount();
            } else {
                $totalEmployees = $this->employeeRepository->getFilteredEmployeesCount($whereClause, $bind);
            }

            $employees = $this->mapToEmployeeObjects($employeeData);
            $pagination = PaginationHelper::paginate($totalEmployees, $page, $perPage);

            return [
                'data' => $employees,
                'pagination' => $pagination
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Employees', $e->getMessage());
        }
    }

    /**
     * Build WHERE clause and bindings for employee search.
     */
    private function buildWhereClause(array $searchParams): array
    {
        $conditions = [];
        $bind = [];

        $operator = strtoupper($searchParams['operator'] ?? 'AND');
        if (!in_array($operator, ['AND', 'OR'], true)) {
            $operator = 'AND';
        }

        $fieldMap = [
            'employee_name' => 'e.name',
            'email' => 'e.email',
            'phone' => 'e.phone',
            'address' => 'e.address',
            'facility_name' => 'f.name',
            'city' => 'l.city',
        ];

        foreach ($fieldMap as $param => $column) {
            if (!empty($searchParams[$param])) {
                $placeholder = ':' . $param;
                $conditions[] = "$column LIKE $placeholder";
                $bind[$placeholder] = '%' . $searchParams[$param] . '%';
            }
        }

        $query = $searchParams['query'] ?? null;
        $filters = $searchParams['filters'] ?? [];

        if ($query !== null && $query !== '') {
            $queryConditions = [];
            $queryFieldMap = empty($filters) ? ['employee_name', 'address','email'] : $filters;
            
            foreach ($queryFieldMap as $filter) {
                if (isset($fieldMap[$filter])) {
                    $queryConditions[] = $fieldMap[$filter] . ' LIKE :query';
                }
            }

            if (!empty($queryConditions)) {
                $conditions[] = '(' . implode(' OR ', $queryConditions) . ')';
                $bind[':query'] = '%' . $query . '%';
            }
        }

        if (empty($conditions)) {
            return ['whereClause' => '1', 'bind' => []];
        }

        return [
            'whereClause' => implode(" $operator ", $conditions),
            'bind' => $bind
        ];
    }

    /**
     * Get an employee by ID.
     */
    public function getEmployeeById(int $id): ?Employee
    {
        try {
            $employeeData = $this->employeeRepository->getEmployeeById($id);
            
            if (!$employeeData) {
                return null;
            }

            $facilityIds = $this->employeeRepository->getFacilityIdsByEmployeeId($id);

            return new Employee(
                (int) $employeeData['id'],
                $employeeData['name'],
                $employeeData['address'],
                $employeeData['phone'],
                $employeeData['email'],
                $employeeData['created_at'],
                $facilityIds
            );
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Employees', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Create a new employee.
     * 
     * @throws DuplicateResourceException If email already exists
     * @throws DatabaseException If database operation fails
     */
    public function createEmployee(array $data): Employee
    {
        // Business rule: Email must be unique
        if (!$this->employeeRepository->isEmployeeEmailUnique($data['email'])) {
            throw new DuplicateResourceException('Employee', 'email', $data['email']);
        }

        try {
            error_log("Creating employee with data: " . json_encode($data));

            $employeeId = $this->employeeRepository->createEmployee([
                'name' => $data['name'],
                'address' => $data['address'] ?? '',
                'phone' => $data['phone'] ?? '',
                'email' => $data['email']
            ]);

            if (!$employeeId) {
                throw new DatabaseException('INSERT', 'Employees', 'Failed to retrieve employee ID');
            }

            error_log("Employee created with ID: {$employeeId}");

            // Add facility relationships if provided
            if (isset($data['facilityIds']) && is_array($data['facilityIds']) && !empty($data['facilityIds'])) {
                error_log("Adding facility relationships: " . json_encode($data['facilityIds']));
                $this->employeeRepository->addEmployeeFacilities($employeeId, $data['facilityIds']);
            }

            error_log("Fetching employee by ID: {$employeeId}");
            $employee = $this->getEmployeeById($employeeId);

            if (!$employee) {
                throw new DatabaseException('SELECT', 'Employees', 'Failed to retrieve created employee');
            }

            error_log("Employee fetched successfully, sending email...");

            // Send welcome email if email is provided
            if (!empty($data['email'])) {
                $this->sendWelcomeEmail($employee);
            }

            return $employee;
        } catch (\PDOException $e) {
            error_log("PDO Exception in createEmployee: " . $e->getMessage());
            throw new DatabaseException('INSERT', 'Employees', $e->getMessage(), [
                'email' => $data['email']
            ]);
        } catch (\Exception $e) {
            error_log("Exception in createEmployee: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update an existing employee.
     * 
     * @throws DuplicateResourceException If email is already in use by another employee
     * @throws DatabaseException If database operation fails
     */
    public function updateEmployee(int $id, array $data): ?Employee
    {
        try {
            // Fetch the existing employee record
            $existing = $this->employeeRepository->getEmployeeById($id);
            if (!$existing) {
                return null;
            }

            // Business rule: Email must be unique (excluding self)
            if (isset($data['email']) && $data['email'] !== $existing['email']) {
                if (!$this->employeeRepository->isEmployeeEmailUniqueForUpdate($data['email'], $id)) {
                    throw new DuplicateResourceException('Employee', 'email', $data['email']);
                }
            }

            // Merge provided fields with existing values
            $updateData = [
                'name' => $data['name'] ?? $existing['name'],
                'address' => $data['address'] ?? $existing['address'],
                'phone' => $data['phone'] ?? $existing['phone'],
                'email' => $data['email'] ?? $existing['email'],
            ];

            $success = $this->employeeRepository->updateEmployee($id, $updateData);
            if (!$success) {
                throw new DatabaseException('UPDATE', 'Employees', 'Update operation returned false', ['id' => $id]);
            }

            // Update facility relationships if provided
            if (isset($data['facilityIds']) && is_array($data['facilityIds'])) {
                $this->employeeRepository->addEmployeeFacilities($id, $data['facilityIds']);
            }

            return $this->getEmployeeById($id);
        } catch (DuplicateResourceException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\PDOException $e) {
            throw new DatabaseException('UPDATE', 'Employees', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Delete an employee.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function deleteEmployee(int $id): bool
    {
        try {
            $result = $this->employeeRepository->deleteEmployee($id);
            
            if (!$result) {
                throw new DatabaseException('DELETE', 'Employees', 'Delete operation returned false', ['id' => $id]);
            }
            
            return $result;
        } catch (\PDOException $e) {
            throw new DatabaseException('DELETE', 'Employees', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Check if an employee email is unique.
     */
    public function isEmailUnique(string $email): bool
    {
        try {
            return $this->employeeRepository->isEmployeeEmailUnique($email);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Employees', 'Failed to check email uniqueness', ['email' => $email]);
        }
    }

    /**
     * Check if an employee email is unique for update (excluding a specific ID).
     */
    public function isEmailUniqueForUpdate(string $email, int $excludeId): bool
    {
        try {
            return $this->employeeRepository->isEmployeeEmailUniqueForUpdate($email, $excludeId);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Employees', 'Failed to check email uniqueness', [
                'email' => $email,
                'exclude_id' => $excludeId
            ]);
        }
    }

    /**
     * Send welcome email to employee.
     */
    private function sendWelcomeEmail(Employee $employee): void
    {
        try {
            // Prepare email data
            $emailData = [
                'name' => $employee->name,
                'email' => $employee->email,
                'address' => $employee->address ?? 'Not provided',
                'facility_names' => $this->getFacilityNames($employee->facilityIds ?? []),
            ];

            // Send email (non-blocking, don't fail if email fails)
            $sent = $this->emailService->sendEmployeeWelcomeEmail($emailData);

            if ($sent) {
                error_log("Welcome email sent successfully to: {$employee->email}");
            } else {
                error_log("Failed to send welcome email to: {$employee->email}");
            }
        } catch (\Exception $e) {
            // Log error but don't throw exception
            // Employee creation should not fail if email fails
            error_log("Welcome email error: " . $e->getMessage());
        }
    }

    /**
     * Get facility names from facility IDs.
     */
    private function getFacilityNames(array $facilityIds): string
    {
        if (empty($facilityIds)) {
            return 'Not assigned';
        }

        try {
            $names = [];
            foreach ($facilityIds as $id) {
                $facility = $this->facilityRepository->getFacilityById($id);
                if ($facility && isset($facility['facility_name'])) {
                    $names[] = $facility['facility_name'];
                }
            }

            return !empty($names) ? implode(', ', $names) : 'Not assigned';
        } catch (\Exception $e) {
            error_log("Error fetching facility names: " . $e->getMessage());
            return 'Not assigned';
        }
    }

    /**
     * Map employee data to Employee objects.
     */
    private function mapToEmployeeObjects(array $employeeData): array
    {
        $employees = [];
        foreach ($employeeData as $data) {
            try {
                $facilityIds = $this->employeeRepository->getFacilityIdsByEmployeeId((int) $data['id']);

                $employees[] = new Employee(
                    (int) $data['id'],
                    $data['name'] ?? '',
                    $data['address'] ?? '',
                    $data['phone'] ?? '',
                    $data['email'] ?? '',
                    $data['created_at'] ?? date('Y-m-d H:i:s'),
                    $facilityIds
                );
            } catch (\PDOException | \Exception $e) {
                // Log and skip problematic employee
                error_log("Failed to map employee {$data['id']}: " . $e->getMessage());
                continue;
            }
        }
        return $employees;
    }
}