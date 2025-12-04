<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\EmployeeService;
use App\Services\EmailService;
use App\Repositories\EmployeeRepository;
use App\Repositories\FacilityRepository;
use App\Models\Employee;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\DatabaseException;

class EmployeeServiceTest extends TestCase
{
    private $mockEmployeeRepository;
    private $mockFacilityRepository;
    private $mockEmailService;
    private EmployeeService $employeeService;

    protected function setUp(): void
    {
        $this->mockEmployeeRepository = $this->createMock(EmployeeRepository::class);
        $this->mockFacilityRepository = $this->createMock(FacilityRepository::class);
        $this->mockEmailService = $this->createMock(EmailService::class);

        $this->employeeService = new EmployeeService(
            $this->mockEmployeeRepository,
            $this->mockFacilityRepository,
            $this->mockEmailService
        );
    }

    public function testGetEmployeesWithDefaultParameters(): void
    {
        $employeeData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+31612345678', 'address' => 'Amsterdam', 'created_at' => '2024-01-01 10:00:00']
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployees')
            ->with('1', [], 10, 0)
            ->willReturn($employeeData);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getTotalEmployeesCount')
            ->willReturn(1);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([1, 2]);

        $result = $this->employeeService->getEmployees(1, 10);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['data']);
        $this->assertInstanceOf(Employee::class, $result['data'][0]);
    }

    public function testGetEmployeesWithSearchParams(): void
    {
        $searchParams = [
            'employee_name' => 'John',
            'email' => 'john@',
            'operator' => 'AND'
        ];

        $employeeData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+31612345678', 'address' => 'Amsterdam', 'created_at' => '2024-01-01 10:00:00']
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployees')
            ->with(
                $this->stringContains('e.name LIKE'),
                $this->arrayHasKey(':employee_name'),
                10,
                0
            )
            ->willReturn($employeeData);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFilteredEmployeesCount')
            ->willReturn(1);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([]);

        $result = $this->employeeService->getEmployees(1, 10, $searchParams);

        $this->assertCount(1, $result['data']);
    }

    public function testGetEmployeesWithQuerySearch(): void
    {
        $searchParams = [
            'query' => 'john',
            'filters' => ['employee_name', 'email']
        ];

        $employeeData = [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'phone' => '+31612345678', 'address' => 'Amsterdam', 'created_at' => '2024-01-01 10:00:00']
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployees')
            ->with(
                $this->stringContains('e.name LIKE :query OR e.email LIKE :query'),
                $this->arrayHasKey(':query'),
                10,
                0
            )
            ->willReturn($employeeData);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFilteredEmployeesCount')
            ->willReturn(1);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([]);

        $result = $this->employeeService->getEmployees(1, 10, $searchParams);

        $this->assertCount(1, $result['data']);
    }

    public function testGetEmployeesWithOROperator(): void
    {
        $searchParams = [
            'employee_name' => 'John',
            'email' => 'jane@',
            'operator' => 'OR'
        ];

        $employeeData = [];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployees')
            ->with(
                $this->stringContains(' OR '),
                $this->anything(),
                10,
                0
            )
            ->willReturn($employeeData);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFilteredEmployeesCount')
            ->willReturn(0);

        $result = $this->employeeService->getEmployees(1, 10, $searchParams);

        $this->assertCount(0, $result['data']);
    }

    public function testGetEmployeesThrowsDatabaseException(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployees')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('SELECT');

        $this->employeeService->getEmployees(1, 10);
    }

    public function testGetEmployeeByIdSuccess(): void
    {
        $employeeData = [
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
            'address' => 'Amsterdam',
            'created_at' => '2024-01-01 10:00:00'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(1)
            ->willReturn($employeeData);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([1, 2]);

        $employee = $this->employeeService->getEmployeeById(1);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals(1, $employee->id);
        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals([1, 2], $employee->facilityIds);
    }

    public function testGetEmployeeByIdNotFound(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(999)
            ->willReturn(null);

        $employee = $this->employeeService->getEmployeeById(999);

        $this->assertNull($employee);
    }

    public function testGetEmployeeByIdThrowsDatabaseException(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->getEmployeeById(1);
    }

    public function testCreateEmployeeSuccess(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
            'address' => 'Amsterdam',
            'facilityIds' => [1, 2]
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->with('john@example.com')
            ->willReturn(true);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('createEmployee')
            ->with([
                'name' => 'John Doe',
                'address' => 'Amsterdam',
                'phone' => '+31612345678',
                'email' => 'john@example.com'
            ])
            ->willReturn(1);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('addEmployeeFacilities')
            ->with(1, [1, 2]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+31612345678',
                'address' => 'Amsterdam',
                'created_at' => '2024-01-01 10:00:00'
            ]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([1, 2]);

        $this->mockFacilityRepository->expects($this->exactly(2))
            ->method('getFacilityById')
            ->willReturnOnConsecutiveCalls(
                ['facility_name' => 'Facility 1'],
                ['facility_name' => 'Facility 2']
            );

        $this->mockEmailService->expects($this->once())
            ->method('sendEmployeeWelcomeEmail')
            ->with($this->callback(function ($emailData) {
                return $emailData['name'] === 'John Doe' &&
                       $emailData['email'] === 'john@example.com' &&
                       $emailData['facility_names'] === 'Facility 1, Facility 2';
            }))
            ->willReturn(true);

        $employee = $this->employeeService->createEmployee($data);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals(1, $employee->id);
        $this->assertEquals('John Doe', $employee->name);
    }

    public function testCreateEmployeeWithoutFacilities(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
            'address' => 'Amsterdam'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->willReturn(true);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('createEmployee')
            ->willReturn(1);

        $this->mockEmployeeRepository->expects($this->never())
            ->method('addEmployeeFacilities');

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->willReturn([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+31612345678',
                'address' => 'Amsterdam',
                'created_at' => '2024-01-01 10:00:00'
            ]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->willReturn([]);

        $this->mockEmailService->expects($this->once())
            ->method('sendEmployeeWelcomeEmail')
            ->with($this->callback(function ($emailData) {
                return $emailData['facility_names'] === 'Not assigned';
            }))
            ->willReturn(true);

        $employee = $this->employeeService->createEmployee($data);

        $this->assertInstanceOf(Employee::class, $employee);
    }

    public function testCreateEmployeeDuplicateEmail(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'phone' => '+31612345678',
            'address' => 'Amsterdam'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->with('existing@example.com')
            ->willReturn(false);

        $this->expectException(DuplicateResourceException::class);

        $this->employeeService->createEmployee($data);
    }

    public function testCreateEmployeeThrowsPDOException(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+31612345678',
            'address' => 'Amsterdam'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->willReturn(true);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('createEmployee')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->createEmployee($data);
    }

    public function testUpdateEmployeeSuccess(): void
    {
        $data = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+31698765432',
            'address' => 'Rotterdam',
            'facilityIds' => [2, 3]
        ];

        $this->mockEmployeeRepository->expects($this->exactly(2))
            ->method('getEmployeeById')
            ->with(1)
            ->willReturnOnConsecutiveCalls(
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'phone' => '+31612345678',
                    'address' => 'Amsterdam'
                ],
                [
                    'id' => 1,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'phone' => '+31698765432',
                    'address' => 'Rotterdam',
                    'created_at' => '2024-01-01 10:00:00'
                ]
            );

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUniqueForUpdate')
            ->with('jane@example.com', 1)
            ->willReturn(true);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('updateEmployee')
            ->with(1, [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone' => '+31698765432',
                'address' => 'Rotterdam'
            ])
            ->willReturn(true);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('addEmployeeFacilities')
            ->with(1, [2, 3]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getFacilityIdsByEmployeeId')
            ->with(1)
            ->willReturn([2, 3]);

        $employee = $this->employeeService->updateEmployee(1, $data);

        $this->assertInstanceOf(Employee::class, $employee);
        $this->assertEquals('Jane Doe', $employee->name);
    }

    public function testUpdateEmployeeNotFound(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(999)
            ->willReturn(null);

        $employee = $this->employeeService->updateEmployee(999, ['name' => 'Test']);

        $this->assertNull($employee);
    }

    public function testUpdateEmployeeDuplicateEmail(): void
    {
        $data = [
            'email' => 'existing@example.com'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+31612345678',
                'address' => 'Amsterdam'
            ]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUniqueForUpdate')
            ->with('existing@example.com', 1)
            ->willReturn(false);

        $this->expectException(DuplicateResourceException::class);

        $this->employeeService->updateEmployee(1, $data);
    }

    public function testUpdateEmployeeThrowsPDOException(): void
    {
        $data = [
            'name' => 'Jane Doe'
        ];

        $this->mockEmployeeRepository->expects($this->once())
            ->method('getEmployeeById')
            ->with(1)
            ->willReturn([
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone' => '+31612345678',
                'address' => 'Amsterdam'
            ]);

        $this->mockEmployeeRepository->expects($this->once())
            ->method('updateEmployee')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->updateEmployee(1, $data);
    }

    public function testDeleteEmployeeSuccess(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('deleteEmployee')
            ->with(1)
            ->willReturn(true);

        $result = $this->employeeService->deleteEmployee(1);

        $this->assertTrue($result);
    }

    public function testDeleteEmployeeThrowsPDOException(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('deleteEmployee')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->deleteEmployee(1);
    }

    public function testIsEmailUnique(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->with('test@example.com')
            ->willReturn(true);

        $result = $this->employeeService->isEmailUnique('test@example.com');

        $this->assertTrue($result);
    }

    public function testIsEmailUniqueThrowsPDOException(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUnique')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->isEmailUnique('test@example.com');
    }

    public function testIsEmailUniqueForUpdate(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUniqueForUpdate')
            ->with('test@example.com', 1)
            ->willReturn(true);

        $result = $this->employeeService->isEmailUniqueForUpdate('test@example.com', 1);

        $this->assertTrue($result);
    }

    public function testIsEmailUniqueForUpdateThrowsPDOException(): void
    {
        $this->mockEmployeeRepository->expects($this->once())
            ->method('isEmployeeEmailUniqueForUpdate')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->employeeService->isEmailUniqueForUpdate('test@example.com', 1);
    }
}
