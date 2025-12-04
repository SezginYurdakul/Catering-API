<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Employee;

class EmployeeTest extends TestCase
{
    /**
     * Test employee creation with all fields
     */
    public function testEmployeeCreationWithAllFields(): void
    {
        $employee = new Employee(
            1,
            'John Doe',
            '123 Main Street',
            '+31612345678',
            'john@example.com',
            '2024-01-15 10:30:00',
            [1, 2, 3]
        );

        $this->assertEquals(1, $employee->id);
        $this->assertEquals('John Doe', $employee->name);
        $this->assertEquals('123 Main Street', $employee->address);
        $this->assertEquals('+31612345678', $employee->phone);
        $this->assertEquals('john@example.com', $employee->email);
        $this->assertEquals('2024-01-15 10:30:00', $employee->created_at);
        $this->assertEquals([1, 2, 3], $employee->facilityIds);
    }

    /**
     * Test employee creation without facility IDs
     */
    public function testEmployeeCreationWithoutFacilityIds(): void
    {
        $employee = new Employee(
            2,
            'Jane Smith',
            '456 Oak Avenue',
            '+31687654321',
            'jane@example.com',
            '2024-02-20 14:45:00'
        );

        $this->assertEquals(2, $employee->id);
        $this->assertEquals('Jane Smith', $employee->name);
        $this->assertEquals('456 Oak Avenue', $employee->address);
        $this->assertEquals('+31687654321', $employee->phone);
        $this->assertEquals('jane@example.com', $employee->email);
        $this->assertEquals('2024-02-20 14:45:00', $employee->created_at);
        $this->assertEquals([], $employee->facilityIds); // Should default to empty array
    }

    /**
     * Test employee creation with null facility IDs
     */
    public function testEmployeeCreationWithNullFacilityIds(): void
    {
        $employee = new Employee(
            3,
            'Bob Johnson',
            '789 Pine Road',
            '+31623456789',
            'bob@example.com',
            '2024-03-10 09:15:00',
            null
        );

        $this->assertEquals([], $employee->facilityIds); // Should default to empty array
    }

    /**
     * Test employee creation with empty facility IDs array
     */
    public function testEmployeeCreationWithEmptyFacilityIds(): void
    {
        $employee = new Employee(
            4,
            'Alice Williams',
            '321 Elm Street',
            '+31698765432',
            'alice@example.com',
            '2024-04-05 16:20:00',
            []
        );

        $this->assertEquals([], $employee->facilityIds);
    }

    /**
     * Test employee properties are public and can be modified
     */
    public function testEmployeePropertiesArePublic(): void
    {
        $employee = new Employee(
            5,
            'Original Name',
            'Original Address',
            '+31600000000',
            'original@example.com',
            '2024-01-01 00:00:00'
        );

        // Modify properties
        $employee->id = 999;
        $employee->name = 'Modified Name';
        $employee->address = 'Modified Address';
        $employee->phone = '+31699999999';
        $employee->email = 'modified@example.com';
        $employee->created_at = '2024-12-31 23:59:59';
        $employee->facilityIds = [10, 20, 30];

        $this->assertEquals(999, $employee->id);
        $this->assertEquals('Modified Name', $employee->name);
        $this->assertEquals('Modified Address', $employee->address);
        $this->assertEquals('+31699999999', $employee->phone);
        $this->assertEquals('modified@example.com', $employee->email);
        $this->assertEquals('2024-12-31 23:59:59', $employee->created_at);
        $this->assertEquals([10, 20, 30], $employee->facilityIds);
    }

    /**
     * Test employee with special characters in fields
     */
    public function testEmployeeWithSpecialCharacters(): void
    {
        $employee = new Employee(
            6,
            "O'Connor & Sons",
            "123 Café Street",
            '+31-6-12-34-56-78',
            'test+tag@example.com',
            '2024-05-15 12:00:00'
        );

        $this->assertEquals("O'Connor & Sons", $employee->name);
        $this->assertEquals("123 Café Street", $employee->address);
        $this->assertEquals('+31-6-12-34-56-78', $employee->phone);
        $this->assertEquals('test+tag@example.com', $employee->email);
    }

    /**
     * Test employee with empty string fields
     */
    public function testEmployeeWithEmptyStrings(): void
    {
        $employee = new Employee(
            7,
            '',
            '',
            '',
            '',
            '2024-06-01 00:00:00',
            []
        );

        $this->assertEquals('', $employee->name);
        $this->assertEquals('', $employee->address);
        $this->assertEquals('', $employee->phone);
        $this->assertEquals('', $employee->email);
    }

    /**
     * Test employee with single facility
     */
    public function testEmployeeWithSingleFacility(): void
    {
        $employee = new Employee(
            8,
            'Single Facility Employee',
            '999 Main St',
            '+31611111111',
            'single@example.com',
            '2024-07-01 10:00:00',
            [42]
        );

        $this->assertEquals([42], $employee->facilityIds);
        $this->assertCount(1, $employee->facilityIds);
    }

    /**
     * Test employee with multiple facilities
     */
    public function testEmployeeWithMultipleFacilities(): void
    {
        $employee = new Employee(
            9,
            'Multi Facility Employee',
            '888 Multi St',
            '+31622222222',
            'multi@example.com',
            '2024-08-01 11:00:00',
            [1, 5, 10, 15, 20]
        );

        $this->assertEquals([1, 5, 10, 15, 20], $employee->facilityIds);
        $this->assertCount(5, $employee->facilityIds);
    }

    /**
     * Test employee created_at format
     */
    public function testEmployeeCreatedAtFormat(): void
    {
        $timestamp = '2024-09-15 14:30:45';
        $employee = new Employee(
            10,
            'Timestamp Test',
            'Time St',
            '+31633333333',
            'time@example.com',
            $timestamp
        );

        $this->assertEquals($timestamp, $employee->created_at);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $employee->created_at
        );
    }
}
