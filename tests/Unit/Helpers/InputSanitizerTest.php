<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use App\Helpers\InputSanitizer;

class InputSanitizerTest extends TestCase
{
    public function testSanitizeGeneralData(): void
    {
        $input = [
            'name' => '  Test Name  ',
            'description' => '<script>alert("xss")</script>Clean text',
            'number' => 123,
            'float' => 45.67,
            'bool' => true
        ];

        $result = InputSanitizer::sanitize($input);

        $this->assertEquals('Test Name', $result['name']);
        $this->assertStringNotContainsString('<script>', $result['description']);
        $this->assertEquals(123, $result['number']);
        $this->assertEquals(45.67, $result['float']);
        $this->assertTrue($result['bool']);
    }

    public function testSanitizeId(): void
    {
        // Valid positive integers
        $this->assertEquals(1, InputSanitizer::sanitizeId(1));
        $this->assertEquals(100, InputSanitizer::sanitizeId('100'));
        $this->assertEquals(42, InputSanitizer::sanitizeId(42.0));

        // Invalid inputs should return null
        $this->assertNull(InputSanitizer::sanitizeId(0));
        $this->assertNull(InputSanitizer::sanitizeId(-1));
        $this->assertNull(InputSanitizer::sanitizeId('abc'));
        $this->assertNull(InputSanitizer::sanitizeId(''));
        $this->assertNull(InputSanitizer::sanitizeId(null));
    }

    public function testSanitizeEmail(): void
    {
        // Valid emails
        $this->assertEquals('test@example.com', InputSanitizer::sanitizeEmail('test@example.com'));
        $this->assertEquals('user.name@domain.co.uk', InputSanitizer::sanitizeEmail(' user.name@domain.co.uk '));

        // Invalid emails should return null
        $this->assertNull(InputSanitizer::sanitizeEmail('invalid-email'));
        $this->assertNull(InputSanitizer::sanitizeEmail('@domain.com'));
        $this->assertNull(InputSanitizer::sanitizeEmail('user@'));
        $this->assertNull(InputSanitizer::sanitizeEmail(''));
    }

    public function testSanitizePhone(): void
    {
        // Valid phone numbers
        $this->assertEquals('+31612345678', InputSanitizer::sanitizePhone('+31 6 12345678'));
        $this->assertEquals('+12125551234', InputSanitizer::sanitizePhone('+1 (212) 555-1234'));
        $this->assertEquals('1234567890', InputSanitizer::sanitizePhone('123-456-7890'));

        // Invalid phone numbers should return null
        $this->assertNull(InputSanitizer::sanitizePhone('123'));
        $this->assertNull(InputSanitizer::sanitizePhone('abc'));
        $this->assertNull(InputSanitizer::sanitizePhone(''));
        $this->assertNull(InputSanitizer::sanitizePhone('12345678901234567890')); // Too long
    }

    public function testSanitizeFloat(): void
    {
        // Valid floats
        $this->assertEquals(123.45, InputSanitizer::sanitizeFloat(123.45));
        $this->assertEquals(100.0, InputSanitizer::sanitizeFloat('100'));
        $this->assertEquals(42.7, InputSanitizer::sanitizeFloat('42.7'));

        // Invalid inputs should return null
        $this->assertNull(InputSanitizer::sanitizeFloat('abc'));
        $this->assertNull(InputSanitizer::sanitizeFloat(''));
        $this->assertNull(InputSanitizer::sanitizeFloat([]));
    }

    public function testSanitizeBool(): void
    {
        // Truthy values
        $this->assertTrue(InputSanitizer::sanitizeBool(true));
        $this->assertTrue(InputSanitizer::sanitizeBool('true'));
        $this->assertTrue(InputSanitizer::sanitizeBool(1));
        $this->assertTrue(InputSanitizer::sanitizeBool('1'));

        // Falsy values
        $this->assertFalse(InputSanitizer::sanitizeBool(false));
        $this->assertFalse(InputSanitizer::sanitizeBool('false'));
        $this->assertFalse(InputSanitizer::sanitizeBool(0));
        $this->assertFalse(InputSanitizer::sanitizeBool('0'));
        $this->assertFalse(InputSanitizer::sanitizeBool(''));
        $this->assertFalse(InputSanitizer::sanitizeBool(null));
    }

    public function testSanitizeAddress(): void
    {
        $address = '<script>alert("xss")</script>123 Main St, Amsterdam';
        $result = InputSanitizer::sanitizeAddress($address);
        
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('123 Main St, Amsterdam', $result);
    }

    public function testSanitizeNestedArray(): void
    {
        $input = [
            'user' => [
                'name' => '  John Doe  ',
                'id' => '123',
                'tags' => [
                    'tag1' => '  Important  ',
                    'tag2' => '<script>alert("xss")</script>Clean'
                ]
            ]
        ];

        $result = InputSanitizer::sanitize($input);

        $this->assertEquals('John Doe', $result['user']['name']);
        $this->assertEquals(123, $result['user']['id']);
        $this->assertEquals('Important', $result['user']['tags']['tag1']);
        $this->assertStringNotContainsString('<script>', $result['user']['tags']['tag2']);
    }

    public function testSanitizeInternationalNames(): void
    {
        // Test that international characters are preserved
        $input = [
            'name1' => "O'Brien",
            'name2' => "São Paulo",
            'name3' => "München",
            'name4' => "İstanbul",
            'name5' => "Café (Downtown)",
            'name6' => "François Müller"
        ];

        $result = InputSanitizer::sanitize($input);

        // Apostrophes should be encoded but preserved
        $this->assertStringContainsString('Brien', $result['name1']);
        // International characters should be encoded but preserved
        $this->assertStringContainsString('Paulo', $result['name2']);
        $this->assertStringContainsString('nchen', $result['name3']);
        $this->assertStringContainsString('stanbul', $result['name4']);
        // Parentheses should be encoded but preserved
        $this->assertStringContainsString('Downtown', $result['name5']);
        $this->assertStringContainsString('ller', $result['name6']);
    }

    public function testSanitizeSpecialCharacters(): void
    {
        // Test that special characters are encoded, not removed
        $input = [
            'text1' => "Hello! How are you?",
            'text2' => "Price: $99.99",
            'text3' => "Email: user@example.com",
            'text4' => "Math: 2 + 2 = 4"
        ];

        $result = InputSanitizer::sanitize($input);

        // Special characters should be encoded
        $this->assertStringContainsString('Hello', $result['text1']);
        $this->assertStringContainsString('How are you', $result['text1']);
        $this->assertStringContainsString('99.99', $result['text2']);
        $this->assertStringContainsString('example.com', $result['text3']);
        $this->assertStringContainsString('2', $result['text4']);
    }

    public function testSanitizeJsonPreservesStructure(): void
    {
        $json = json_encode([
            'name' => "O'Brien's Café",
            'description' => '<script>alert("xss")</script>Text',
            'price' => 99.99,
            'available' => true,
            'tags' => ['important!', 'urgent?', 'new*']
        ]);

        $result = InputSanitizer::sanitizeJson($json);

        $this->assertIsArray($result);
        // Special characters in strings should be encoded but preserved
        $this->assertArrayHasKey('name', $result);
        $this->assertStringContainsString('Brien', $result['name']);
        $this->assertStringContainsString('Caf', $result['name']);
        
        // XSS should be encoded
        $this->assertStringNotContainsString('<script>', $result['description']);
        
        // Numeric and boolean values should pass through
        $this->assertEquals(99.99, $result['price']);
        $this->assertTrue($result['available']);
        
        // Array should be preserved
        $this->assertIsArray($result['tags']);
        $this->assertCount(3, $result['tags']);
    }

    public function testSanitizeEmptyAndWhitespace(): void
    {
        $input = [
            'empty' => '',
            'whitespace' => '   ',
            'tabs' => "\t\t",
            'newlines' => "\n\n"
        ];

        $result = InputSanitizer::sanitize($input);

        $this->assertEquals('', $result['empty']);
        $this->assertEquals('', $result['whitespace']);
        $this->assertEquals('', $result['tabs']);
        $this->assertEquals('', $result['newlines']);
    }
}