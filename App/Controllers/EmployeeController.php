<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\IEmployeeService;
use App\Models\Employee;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NotFound;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;

class EmployeeController extends BaseController
{
    private IEmployeeService $employeeService;

    /**
     * Constructor to initialize the EmployeeService from the DI container.
     * Authenticates the user with the AuthMiddleware.
     */
    public function __construct()
    {
        parent::__construct();
        $this->employeeService = $this->getService('employeeService');
        $this->requireAuth();
    }

    /**
     * Get all employees.
     * Sends a 200 OK response with the list of employees.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @return void
     */
    public function getEmployees(): void
    {
        try {
            Validator::pagination($_GET);

            $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
            $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

            if ($page === null || $perPage === null) {
                throw new ValidationException(['pagination' => "'page' and 'per_page' must be positive integers."]);
            }

            $query = isset($_GET['query']) ? InputSanitizer::sanitize(['value' => $_GET['query']])['value'] : null;

            $filters = isset($_GET['filter']) ? array_filter(array_map('trim', explode(',', (string) $_GET['filter']))) : [];
            if (!empty($filters)) {
                Validator::allowedValues($filters, ['employee_name', 'email', 'phone', 'address', 'facility_name', 'city'], 'filter');
            }

            $employeeName = isset($_GET['employee_name']) ? InputSanitizer::sanitize(['value' => $_GET['employee_name']])['value'] : null;
            $email = isset($_GET['email']) ? InputSanitizer::sanitize(['value' => $_GET['email']])['value'] : null;
            $phone = isset($_GET['phone']) ? InputSanitizer::sanitize(['value' => $_GET['phone']])['value'] : null;
            $address = isset($_GET['address']) ? InputSanitizer::sanitize(['value' => $_GET['address']])['value'] : null;
            $facilityName = isset($_GET['facility_name']) ? InputSanitizer::sanitize(['value' => $_GET['facility_name']])['value'] : null;
            $city = isset($_GET['city']) ? InputSanitizer::sanitize(['value' => $_GET['city']])['value'] : null;

            foreach (['employeeName', 'email', 'phone', 'address', 'facilityName', 'city', 'query'] as $paramName) {
                $value = $$paramName ?? null;
                if ($value !== null && $value === '') {
                    $$paramName = null;
                }
            }

            $operator = isset($_GET['operator']) ? strtoupper(trim((string) $_GET['operator'])) : 'AND';
            if (!in_array($operator, ['AND', 'OR'], true)) {
                throw new ValidationException(['operator' => "Invalid operator. Only 'AND' or 'OR' are allowed."]);
            }

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

            $employees = $this->employeeService->getEmployees($page, $perPage, $searchParams);

            $totalItems = $employees['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages > 0 && $page > $totalPages) {
                throw new ValidationException([
                    'page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
            }

            $response = new Ok($employees);
            $response->send();
        } catch (ValidationException $e) {
            $errorResponse = new BadRequest([
                'error' => 'Validation failed',
                'details' => $e->getErrors()
            ]);
            $errorResponse->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]);
            $errorResponse->send();
        }
    }

    /**
     * Get a specific employee by its ID.
     * Sends a 200 OK response if the employee is found.
     * Sends a 404 Not Found response if the employee does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function getEmployeeById(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid employee ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            $employee = $this->employeeService->getEmployeeById($id);

            if (!$employee) {
                $errorResponse = new NotFound(["error" => "Employee with ID $id not found."]);
                $errorResponse->send();
                return;
            }

            $response = new Ok(["data" => $employee]);
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]);
            $errorResponse->send();
        }
    }

    /**
     * Create a new employee.
     * Sends a 201 Created response if the employee is created successfully.
     * Sends a 400 Bad Request response if validation fails.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @return void
     */
    public function createEmployee(): void
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (!isset($data['name'], $data['address'], $data['phone'], $data['email'])) {
                $errorResponse = new BadRequest(["error" => "Missing required fields: name, address, phone, email"]);
                $errorResponse->send();
                return;
            }

            // Sanitize input data
            $data['name'] = InputSanitizer::sanitizeString($data['name']);
            $data['address'] = InputSanitizer::sanitizeString($data['address']);
            $data['phone'] = InputSanitizer::sanitizeString($data['phone']);
            $data['email'] = InputSanitizer::sanitizeEmail($data['email']);

            // Validate email
            if (!Validator::validateEmail($data['email'])) {
                $errorResponse = new BadRequest(["error" => "Invalid email format."]);
                $errorResponse->send();
                return;
            }

            // Sanitize facility IDs if provided
            if (isset($data['facilityIds'])) {
                if (!is_array($data['facilityIds'])) {
                    $errorResponse = new BadRequest(["error" => "facilityIds must be an array."]);
                    $errorResponse->send();
                    return;
                }
                $data['facilityIds'] = array_map(fn($id) => InputSanitizer::sanitizeId($id), $data['facilityIds']);
                $data['facilityIds'] = array_filter($data['facilityIds'], fn($id) => $id !== null);
            }

            $employee = $this->employeeService->createEmployee($data);

            $response = new Created(["message" => "Employee created successfully", "data" => $employee]);
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]);
            $errorResponse->send();
        }
    }

    /**
     * Update an existing employee.
     * Sends a 200 OK response if the employee is updated successfully.
     * Sends a 400 Bad Request response if validation fails.
     * Sends a 404 Not Found response if the employee does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function updateEmployee(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid employee ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (!isset($data['name'], $data['address'], $data['phone'], $data['email'])) {
                $errorResponse = new BadRequest(["error" => "Missing required fields: name, address, phone, email"]);
                $errorResponse->send();
                return;
            }

            // Sanitize input data
            $data['name'] = InputSanitizer::sanitizeString($data['name']);
            $data['address'] = InputSanitizer::sanitizeString($data['address']);
            $data['phone'] = InputSanitizer::sanitizeString($data['phone']);
            $data['email'] = InputSanitizer::sanitizeEmail($data['email']);

            // Validate email
            if (!Validator::validateEmail($data['email'])) {
                $errorResponse = new BadRequest(["error" => "Invalid email format."]);
                $errorResponse->send();
                return;
            }

            // Sanitize facility IDs if provided
            if (isset($data['facilityIds'])) {
                if (!is_array($data['facilityIds'])) {
                    $errorResponse = new BadRequest(["error" => "facilityIds must be an array."]);
                    $errorResponse->send();
                    return;
                }
                $data['facilityIds'] = array_map(fn($id) => InputSanitizer::sanitizeId($id), $data['facilityIds']);
                $data['facilityIds'] = array_filter($data['facilityIds'], fn($id) => $id !== null);
            }

            $employee = $this->employeeService->updateEmployee($id, $data);

            if (!$employee) {
                $errorResponse = new NotFound(["error" => "Employee with ID $id not found."]);
                $errorResponse->send();
                return;
            }

            $response = new Ok(["message" => "Employee updated successfully", "data" => $employee]);
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]);
            $errorResponse->send();
        }
    }

    /**
     * Delete an employee.
     * Sends a 200 OK response if the employee is deleted successfully.
     * Sends a 404 Not Found response if the employee does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function deleteEmployee(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $errorResponse = new BadRequest(["error" => "Invalid employee ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            $success = $this->employeeService->deleteEmployee($id);

            if (!$success) {
                $errorResponse = new NotFound(["error" => "Employee with ID $id not found."]);
                $errorResponse->send();
                return;
            }

            $response = new Ok(["message" => "Employee deleted successfully"]);
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]);
            $errorResponse->send();
        }
    }
}
