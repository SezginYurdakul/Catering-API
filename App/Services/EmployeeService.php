<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Employee;
use App\Repositories\EmployeeRepository;
use App\Helpers\PaginationHelper;

class EmployeeService implements IEmployeeService
{
    private EmployeeRepository $employeeRepository;

    public function __construct(EmployeeRepository $employeeRepository)
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Get all employees with pagination.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getEmployees(int $page, int $perPage, array $searchParams = []): array
    {
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
     *
     * @param int $id
     * @return Employee|null
     */
    public function getEmployeeById(int $id): ?Employee
    {
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
    }

    /**
     * Create a new employee.
     *
     * @param array $data
     * @return Employee
     */
    public function createEmployee(array $data): Employee
    {
        // Check for duplicate email
        if (!$this->employeeRepository->isEmployeeEmailUnique($data['email'])) {
            throw new \Exception('An employee with this email already exists.');
        }

        $employeeId = $this->employeeRepository->createEmployee([
            'name' => $data['name'],
            'address' => $data['address'],
            'phone' => $data['phone'],
            'email' => $data['email']
        ]);

        // Add facility relationships if provided
        if (isset($data['facilityIds']) && is_array($data['facilityIds'])) {
            $this->employeeRepository->addEmployeeFacilities($employeeId, $data['facilityIds']);
        }

        return $this->getEmployeeById($employeeId);
    }

    /**
     * Update an existing employee.
     *
     * @param int $id
     * @param array $data
     * @return Employee|null
     */
    public function updateEmployee(int $id, array $data): ?Employee
    {
        // Fetch the existing employee record
        $existing = $this->employeeRepository->getEmployeeById($id);
        if (!$existing) {
            return null;
        }

        // If email is being updated, check for duplicates (excluding self)
        if (isset($data['email']) && $data['email'] !== $existing['email']) {
            if (!$this->employeeRepository->isEmployeeEmailUniqueForUpdate($data['email'], $id)) {
                throw new \Exception("Email address is already in use by another account.");
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
            return null;
        }

        // Update facility relationships if provided
        if (isset($data['facilityIds']) && is_array($data['facilityIds'])) {
            $this->employeeRepository->addEmployeeFacilities($id, $data['facilityIds']);
        }

        return $this->getEmployeeById($id);
    }

    /**
     * Delete an employee.
     *
     * @param int $id
     * @return bool
     */
    public function deleteEmployee(int $id): bool
    {
        return $this->employeeRepository->deleteEmployee($id);
    }

    /**
     * Map employee data to Employee objects.
     *
     * @param array $employeeData
     * @return array
     */
    private function mapToEmployeeObjects(array $employeeData): array
    {
        $employees = [];
        foreach ($employeeData as $data) {
            $facilityIds = $this->employeeRepository->getFacilityIdsByEmployeeId((int) $data['id']);
            
            $employees[] = new Employee(
                (int) $data['id'],
                $data['name'],
                $data['address'],
                $data['phone'],
                $data['email'],
                $data['created_at'],
                $facilityIds
            );
        }
        return $employees;
    }
}
