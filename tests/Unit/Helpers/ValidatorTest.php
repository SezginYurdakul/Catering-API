<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\Validator;
use App\Plugins\Http\Exceptions\ValidationException;

class ValidatorTest extends TestCase
{
    public function testRequiredFieldsSuccess(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'age' => 25
        ];
        
        $requiredFields = ['name', 'email'];
        
        // Should not throw exception
        $this->expectNotToPerformAssertions();
        Validator::required($data, $requiredFields);
    }

    public function testRequiredFieldsFailure(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => '', // Empty value
            // 'age' is missing
        ];
        
        $requiredFields = ['name', 'email', 'age'];
        
        $this->expectException(ValidationException::class);
        Validator::required($data, $requiredFields);
    }

    public function testPositiveIntSuccess(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validator::positiveInt(1, 'id');
        Validator::positiveInt(100, 'count');
        Validator::positiveInt('42', 'number');
    }

    public function testPositiveIntFailure(): void
    {
        $this->expectException(ValidationException::class);
        Validator::positiveInt(0, 'id');
    }

    public function testPositiveIntNegativeFailure(): void
    {
        $this->expectException(ValidationException::class);
        Validator::positiveInt(-1, 'id');
    }

    public function testPositiveIntStringFailure(): void
    {
        $this->expectException(ValidationException::class);
        Validator::positiveInt('abc', 'id');
    }

    public function testPaginationSuccess(): void
    {
        $validParams = [
            'page' => 1,
            'per_page' => 10
        ];
        
        $this->expectNotToPerformAssertions();
        Validator::pagination($validParams);
    }

    public function testPaginationStringParams(): void
    {
        $validParams = [
            'page' => '2',
            'per_page' => '20'
        ];
        
        $this->expectNotToPerformAssertions();
        Validator::pagination($validParams);
    }

    public function testPaginationInvalidPage(): void
    {
        $invalidParams = [
            'page' => 0,
            'per_page' => 10
        ];
        
        $this->expectException(ValidationException::class);
        Validator::pagination($invalidParams);
    }

    public function testPaginationInvalidPerPage(): void
    {
        $invalidParams = [
            'page' => 1,
            'per_page' => 101 // Too high
        ];
        
        $this->expectException(ValidationException::class);
        Validator::pagination($invalidParams);
    }

    public function testPaginationNegativePerPage(): void
    {
        $invalidParams = [
            'page' => 1,
            'per_page' => -1
        ];
        
        $this->expectException(ValidationException::class);
        Validator::pagination($invalidParams);
    }

    public function testAllowedValuesSuccess(): void
    {
        $values = ['red', 'blue'];
        $allowed = ['red', 'blue', 'green', 'yellow'];
        
        $this->expectNotToPerformAssertions();
        Validator::allowedValues($values, $allowed, 'colors');
    }

    public function testAllowedValuesFailure(): void
    {
        $values = ['red', 'purple']; // purple is not allowed
        $allowed = ['red', 'blue', 'green', 'yellow'];
        
        $this->expectException(ValidationException::class);
        Validator::allowedValues($values, $allowed, 'colors');
    }

    public function testStringLengthSuccess(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validator::stringLength('John', 'name', 1, 10);
        Validator::stringLength('A', 'letter', 1, 1);
        Validator::stringLength('1234567890', 'code', 10, 10);
    }

    public function testStringLengthTooShort(): void
    {
        $this->expectException(ValidationException::class);
        Validator::stringLength('', 'name', 1, 10);
    }

    public function testStringLengthTooLong(): void
    {
        $this->expectException(ValidationException::class);
        Validator::stringLength('This is a very long string that exceeds the maximum length', 'description', 1, 20);
    }

    public function testEmailSuccess(): void
    {
        $this->expectNotToPerformAssertions();
        
        Validator::email('john@example.com');
        Validator::email('user.name@domain.co.uk');
        Validator::email('test+tag@example.org');
    }

    public function testEmailFailure(): void
    {
        $this->expectException(ValidationException::class);
        Validator::email('invalid-email');
    }

    public function testEmailFailureNoAt(): void
    {
        $this->expectException(ValidationException::class);
        Validator::email('userexample.com');
    }

    public function testEmailFailureNoDomain(): void
    {
        $this->expectException(ValidationException::class);
        Validator::email('user@');
    }

    // ========================================
    // NEW VALIDATION METHODS (return errors array)
    // ========================================

    public function testValidatePaginationSuccess(): void
    {
        $params = ['page' => '1', 'per_page' => '10'];
        $errors = Validator::validatePagination($params);

        $this->assertEmpty($errors);
    }

    public function testValidatePaginationInvalidPage(): void
    {
        $params = ['page' => '0', 'per_page' => '10'];
        $errors = Validator::validatePagination($params);

        $this->assertArrayHasKey('page', $errors);
        $this->assertEquals('Page must be a positive integer', $errors['page']);
    }

    public function testValidatePaginationInvalidPerPage(): void
    {
        $params = ['page' => '1', 'per_page' => '150'];
        $errors = Validator::validatePagination($params);

        $this->assertArrayHasKey('per_page', $errors);
        $this->assertEquals('Per page must be between 1 and 100', $errors['per_page']);
    }

    public function testValidatePaginationMultipleErrors(): void
    {
        $params = ['page' => '-1', 'per_page' => '0'];
        $errors = Validator::validatePagination($params);

        $this->assertArrayHasKey('page', $errors);
        $this->assertArrayHasKey('per_page', $errors);
        $this->assertCount(2, $errors);
    }

    public function testValidateRequiredSuccess(): void
    {
        $data = ['name' => 'John', 'email' => 'john@example.com'];
        $fields = ['name', 'email'];
        $errors = Validator::validateRequired($data, $fields);

        $this->assertEmpty($errors);
    }

    public function testValidateRequiredMissingField(): void
    {
        $data = ['name' => 'John'];
        $fields = ['name', 'email'];
        $errors = Validator::validateRequired($data, $fields);

        $this->assertArrayHasKey('email', $errors);
        $this->assertEquals('Email is required', $errors['email']);
    }

    public function testValidateRequiredEmptyValue(): void
    {
        $data = ['name' => '', 'email' => '   '];
        $fields = ['name', 'email'];
        $errors = Validator::validateRequired($data, $fields);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertCount(2, $errors);
    }

    public function testValidatePositiveIntSuccess(): void
    {
        $error = Validator::validatePositiveInt(5, 'id');
        $this->assertNull($error);

        $error = Validator::validatePositiveInt('100', 'count');
        $this->assertNull($error);
    }

    public function testValidatePositiveIntFailure(): void
    {
        $error = Validator::validatePositiveInt(0, 'id');
        $this->assertIsString($error);
        $this->assertEquals('Id must be a positive integer', $error);

        $error = Validator::validatePositiveInt(-5, 'count');
        $this->assertIsString($error);

        $error = Validator::validatePositiveInt('abc', 'number');
        $this->assertIsString($error);
    }

    public function testValidateAllowedValuesSuccess(): void
    {
        $values = ['red', 'blue'];
        $allowed = ['red', 'blue', 'green'];
        $error = Validator::validateAllowedValues($values, $allowed, 'colors');

        $this->assertNull($error);
    }

    public function testValidateAllowedValuesFailure(): void
    {
        $values = ['red', 'purple'];
        $allowed = ['red', 'blue', 'green'];
        $error = Validator::validateAllowedValues($values, $allowed, 'colors');

        $this->assertIsString($error);
        $this->assertStringContainsString('purple', $error);
        $this->assertStringContainsString('Invalid colors', $error);
    }

    public function testValidateEmailSuccess(): void
    {
        $error = Validator::validateEmail('test@example.com');
        $this->assertNull($error);

        $error = Validator::validateEmail('user.name@domain.co.uk');
        $this->assertNull($error);
    }

    public function testValidateEmailFailure(): void
    {
        $error = Validator::validateEmail('invalid-email');
        $this->assertIsString($error);
        $this->assertEquals('Invalid email format', $error);

        $error = Validator::validateEmail('user@');
        $this->assertIsString($error);
    }

    public function testValidateStringLengthSuccess(): void
    {
        $error = Validator::validateStringLength('John', 'name', 1, 10);
        $this->assertNull($error);

        $error = Validator::validateStringLength('Test', 'field', 4, 4);
        $this->assertNull($error);
    }

    public function testValidateStringLengthTooShort(): void
    {
        $error = Validator::validateStringLength('AB', 'code', 5, 10);
        $this->assertIsString($error);
        $this->assertStringContainsString('must be between 5 and 10', $error);
    }

    public function testValidateStringLengthTooLong(): void
    {
        $error = Validator::validateStringLength('This is too long', 'field', 1, 5);
        $this->assertIsString($error);
        $this->assertStringContainsString('must be between 1 and 5', $error);
    }

    public function testValidateOperatorSuccess(): void
    {
        $error = Validator::validateOperator('AND');
        $this->assertNull($error);

        $error = Validator::validateOperator('OR');
        $this->assertNull($error);
    }

    public function testValidateOperatorFailure(): void
    {
        $error = Validator::validateOperator('NOT');
        $this->assertIsString($error);
        $this->assertStringContainsString("Only 'AND' or 'OR' are allowed", $error);

        $error = Validator::validateOperator('XOR');
        $this->assertIsString($error);
    }
}