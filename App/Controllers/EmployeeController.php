<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\IEmployeeService;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;

class EmployeeController extends BaseController
{
    private IEmployeeService $employeeService;

    public function __construct(
        ?IEmployeeService $employeeService = null,
        bool $initializeBase = true
    ) {
        if ($initializeBase) {
            parent::__construct();
        }
        $this->employeeService = $employeeService ?? \App\Plugins\Di\Factory::getDi()->getShared('employeeService');
        if ($initializeBase) {
            $this->requireAuth();
        }
    }

    /**
     * Get all employees with pagination and search filters.
     */
    public function getEmployees(): void
    {
        $errors = [];

        // Pagination validation using new validate* method
        $errors = array_merge($errors, Validator::validatePagination($_GET));

        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Query parameter
        $query = isset($_GET['query']) ? InputSanitizer::sanitize(['value' => $_GET['query']])['value'] : null;
        if ($query !== null && $query === '') {
            $query = null;
        }

        // Filters validation
        $filters = isset($_GET['filter']) 
            ? array_filter(array_map('trim', explode(',', (string) $_GET['filter']))) 
            : [];
        
        $allowedFilters = ['employee_name', 'email', 'phone', 'address', 'facility_name', 'city'];
        if (!empty($filters)) {
            $error = Validator::validateAllowedValues($filters, $allowedFilters, 'filter');
            if ($error) {
                $errors['filter'] = $error;
            }
        }

        // Operator validation
        $operator = isset($_GET['operator']) ? strtoupper(trim((string) $_GET['operator'])) : 'AND';
        $error = Validator::validateOperator($operator);
        if ($error) {
            $errors['operator'] = $error;
        }

        // Throw all validation errors at once
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize search parameters
        $employeeName = isset($_GET['employee_name']) 
            ? InputSanitizer::sanitize(['value' => $_GET['employee_name']])['value'] 
            : null;
        $email = isset($_GET['email']) 
            ? InputSanitizer::sanitize(['value' => $_GET['email']])['value'] 
            : null;
        $phone = isset($_GET['phone']) 
            ? InputSanitizer::sanitize(['value' => $_GET['phone']])['value'] 
            : null;
        $address = isset($_GET['address']) 
            ? InputSanitizer::sanitize(['value' => $_GET['address']])['value'] 
            : null;
        $facilityName = isset($_GET['facility_name']) 
            ? InputSanitizer::sanitize(['value' => $_GET['facility_name']])['value'] 
            : null;
        $city = isset($_GET['city']) 
            ? InputSanitizer::sanitize(['value' => $_GET['city']])['value'] 
            : null;

        // Convert empty strings to null
        foreach (['employeeName', 'email', 'phone', 'address', 'facilityName', 'city'] as $paramName) {
            if ($$paramName === '') {
                $$paramName = null;
            }
        }

        // Build search parameters
        $searchParams = [
            'query' => $query,
            'filters' => $filters,
            'operator' => $operator,
            'employee_name' => $employeeName,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'facility_name' => $facilityName,
            'city' => $city,
        ];

        // Get employees
        $employees = $this->employeeService->getEmployees($page, $perPage, $searchParams);

        // Validate page number against total pages
        $totalItems = $employees['pagination']['total_items'];
        $totalPages = (int) ceil($totalItems / $perPage);
        
        if ($totalPages > 0 && $page > $totalPages) {
            throw new ValidationException([
                'page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."
            ]);
        }

        $response = new Ok($employees);
        $response->send();
    }

    /**
     * Get a specific employee by ID.
     */
    public function getEmployeeById(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $employee = $this->employeeService->getEmployeeById($id);

        if (!$employee) {
            throw new NotFound('', 'Employee', (string)$id);
        }

        $response = new Ok(["data" => $employee]);
        $response->send();
    }

    /**
     * Create a new employee.
     */
    public function createEmployee(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = [];

        // Validate required fields using new validate* method
        $errors = array_merge($errors, Validator::validateRequired($data, ['name', 'address', 'phone', 'email']));

        // If required fields missing, throw immediately
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize input data
        $data['name'] = InputSanitizer::sanitizeText($data['name']);
        $data['address'] = InputSanitizer::sanitizeAddress($data['address']);
        $data['phone'] = InputSanitizer::sanitizePhone($data['phone']);
        $data['email'] = InputSanitizer::sanitizeEmail($data['email']);

        // Validate email
        if ($data['email'] === null) {
            $errors['email'] = 'Email must be a valid email address';
        } else {
            $error = Validator::validateEmail($data['email']);
            if ($error) {
                $errors['email'] = $error;
            }
        }

        // Sanitize and validate facility IDs
        if (isset($data['facilityIds'])) {
            if (!is_array($data['facilityIds'])) {
                $errors['facilityIds'] = 'facilityIds must be an array';
            } else {
                $data['facilityIds'] = array_map(fn($id) => InputSanitizer::sanitizeId($id), $data['facilityIds']);
                $data['facilityIds'] = array_filter($data['facilityIds'], fn($id) => $id !== null);
                
                if (empty($data['facilityIds'])) {
                    $errors['facilityIds'] = 'facilityIds must contain at least one valid ID';
                }
            }
        }

        // Throw all validation errors
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create employee
        $employee = $this->employeeService->createEmployee($data);
        
        $response = new Created([
            "message" => "Employee created successfully", 
            "data" => $employee
        ]);
        $response->send();
    }

    /**
     * Update an existing employee.
     */
    public function updateEmployee(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            throw new ValidationException(['id' => $error]);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Check if at least one updatable field is provided
        $updatableFields = ['name', 'address', 'phone', 'email', 'facilityIds'];
        $providedFields = array_intersect($updatableFields, array_keys($data ?? []));
        
        if (empty($providedFields)) {
            throw new ValidationException([
                'fields' => 'At least one updatable field (name, address, phone, email, facilityIds) must be provided'
            ]);
        }

        // Sanitize and validate provided fields
        if (isset($data['name'])) {
            $data['name'] = InputSanitizer::sanitizeText($data['name']);
            if (empty($data['name'])) {
                $errors['name'] = 'Name cannot be empty';
            }
        }

        if (isset($data['address'])) {
            $data['address'] = InputSanitizer::sanitizeAddress($data['address']);
            if (empty($data['address'])) {
                $errors['address'] = 'Address cannot be empty';
            }
        }

        if (isset($data['phone'])) {
            $data['phone'] = InputSanitizer::sanitizePhone($data['phone']);
            if (empty($data['phone'])) {
                $errors['phone'] = 'Phone cannot be empty';
            }
        }

        if (isset($data['email'])) {
            $data['email'] = InputSanitizer::sanitizeEmail($data['email']);
            if ($data['email'] === null) {
                $errors['email'] = 'Email must be a valid email address';
            } else {
                $error = Validator::validateEmail($data['email']);
                if ($error) {
                    $errors['email'] = $error;
                }
                
            }
        }

        if (isset($data['facilityIds'])) {
            if (!is_array($data['facilityIds'])) {
                $errors['facilityIds'] = 'facilityIds must be an array';
            } else {
                $data['facilityIds'] = array_map(fn($id) => InputSanitizer::sanitizeId($id), $data['facilityIds']);
                $data['facilityIds'] = array_filter($data['facilityIds'], fn($id) => $id !== null);
            }
        }

        // Throw all validation errors
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Update employee
        $employee = $this->employeeService->updateEmployee($id, $data);

        if (!$employee) {
            throw new NotFound('', 'Employee', (string)$id);
        }

        $response = new Ok([
            "message" => "Employee updated successfully", 
            "data" => $employee
        ]);
        $response->send();
    }

    /**
     * Delete an employee.
     */
    public function deleteEmployee(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $success = $this->employeeService->deleteEmployee($id);

        if (!$success) {
            throw new NotFound('', 'Employee', (string)$id);
        }

        $response = new Ok(["message" => "Employee deleted successfully"]);
        $response->send();
    }
}