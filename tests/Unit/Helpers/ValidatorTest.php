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
}