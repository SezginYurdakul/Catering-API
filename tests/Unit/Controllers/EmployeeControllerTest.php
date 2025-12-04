<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\EmployeeController;
use App\Services\IEmployeeService;
use App\Models\Employee;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;

class EmployeeControllerTest extends TestCase
{
    private $mockEmployeeService;

    protected function setUp(): void
    {
        $this->mockEmployeeService = $this->createMock(IEmployeeService::class);
    }

    /**
     * Helper method to create a controller with mocked dependencies
     */
    private function createController(): EmployeeController
    {
        return new EmployeeController(
            $this->mockEmployeeService,
            false // Skip parent::__construct() and requireAuth()
        );
    }

    /**
     * Test getEmployees with default pagination
     */
    public function testGetEmployeesWithDefaultPagination(): void
    {
        $_GET = [];

        $mockEmployeesData = [
            'employees' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
            ],
            'pagination' => [
                'total_items' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockEmployeeService
            ->expects($this->once())
            ->method('getEmployees')
            ->with(
                1,  // page
                10, // perPage
                $this->callback(function ($params) {
                    return $params['query'] === null &&
                           $params['operator'] === 'AND' &&
                           empty($params['filters']);
                })
            )
            ->willReturn($mockEmployeesData);

        $controller = $this->createController();

        ob_start();
        $controller->getEmployees();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        $this->assertArrayHasKey('employees', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(2, $data['employees']);
    }

    /**
     * Test getEmployees with query parameter
     */
    public function testGetEmployeesWithQuery(): void
    {
        $_GET = ['query' => 'john'];

        $mockEmployeesData = [
            'employees' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
            ],
            'pagination' => [
                'total_items' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockEmployeeService
            ->expects($this->once())
            ->method('getEmployees')
            ->with(
                1,
                10,
                $this->callback(function ($params) {
                    return $params['query'] === 'john';
                })
            )
            ->willReturn($mockEmployeesData);

        $controller = $this->createController();

        ob_start();
        $controller->getEmployees();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertCount(1, $data['employees']);
    }

    /**
     * Test getEmployees with invalid page number
     */
    public function testGetEmployeesWithInvalidPageNumber(): void
    {
        $_GET = ['page' => '-1'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getEmployees();
    }

    /**
     * Test getEmployees with invalid filter
     */
    public function testGetEmployeesWithInvalidFilter(): void
    {
        $_GET = ['filter' => 'invalid_filter'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getEmployees();
    }

    /**
     * Test getEmployees with page exceeding total pages
     */
    public function testGetEmployeesWithPageExceedingTotalPages(): void
    {
        $_GET = ['page' => '10'];

        $mockEmployeesData = [
            'employees' => [],
            'pagination' => [
                'total_items' => 5,
                'total_pages' => 1,
                'current_page' => 10,
                'per_page' => 10
            ]
        ];

        $this->mockEmployeeService
            ->method('getEmployees')
            ->willReturn($mockEmployeesData);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getEmployees();
    }

    /**
     * Test getEmployeeById with valid ID
     */
    public function testGetEmployeeByIdWithValidId(): void
    {
        $mockEmployee = new Employee(
            1,
            'John Doe',
            '123 Main St',
            '123456789',
            'john@example.com',
            '2024-01-01 00:00:00',
            []
        );

        $this->mockEmployeeService
            ->expects($this->once())
            ->method('getEmployeeById')
            ->with(1)
            ->willReturn($mockEmployee);

        $controller = $this->createController();

        ob_start();
        $controller->getEmployeeById(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals(1, $data['data']['id']);
        $this->assertEquals('John Doe', $data['data']['name']);
    }

    /**
     * Test getEmployeeById with non-existent ID
     */
    public function testGetEmployeeByIdWithNonExistentId(): void
    {
        $this->mockEmployeeService
            ->expects($this->once())
            ->method('getEmployeeById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->getEmployeeById(999);
    }

    /**
     * Test getEmployeeById with invalid ID
     */
    public function testGetEmployeeByIdWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getEmployeeById(-1);
    }

    /**
     * Test deleteEmployee with valid ID
     */
    public function testDeleteEmployeeWithValidId(): void
    {
        $this->mockEmployeeService
            ->expects($this->once())
            ->method('deleteEmployee')
            ->with(1)
            ->willReturn(true);

        $controller = $this->createController();

        ob_start();
        $controller->deleteEmployee(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Employee deleted successfully', $data['message']);
    }

    /**
     * Test deleteEmployee with non-existent ID
     */
    public function testDeleteEmployeeWithNonExistentId(): void
    {
        $this->mockEmployeeService
            ->expects($this->once())
            ->method('deleteEmployee')
            ->with(999)
            ->willReturn(false);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->deleteEmployee(999);
    }

    /**
     * Test deleteEmployee with invalid ID
     */
    public function testDeleteEmployeeWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->deleteEmployee(-1);
    }
}
