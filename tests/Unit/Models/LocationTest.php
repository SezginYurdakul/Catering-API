<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Location;

class LocationTest extends TestCase
{
    public function testLocationCreationWithAllFields(): void
    {
        $location = new Location(
            1,
            'Amsterdam',
            'Damrak 1',
            '1012AB',
            'NL',
            '+31-20-1234567'
        );

        $this->assertEquals(1, $location->id);
        $this->assertEquals('Amsterdam', $location->city);
        $this->assertEquals('Damrak 1', $location->address);
        $this->assertEquals('1012AB', $location->zip_code);
        $this->assertEquals('NL', $location->country_code);
        $this->assertEquals('+31-20-1234567', $location->phone_number);
    }

    public function testLocationCreationWithOnlyId(): void
    {
        $location = new Location(1);

        $this->assertEquals(1, $location->id);
        $this->assertNull($location->city);
        $this->assertNull($location->address);
        $this->assertNull($location->zip_code);
        $this->assertNull($location->country_code);
        $this->assertNull($location->phone_number);
    }

    public function testLocationCreationWithPartialFields(): void
    {
        $location = new Location(
            2,
            'Rotterdam',
            'Coolsingel 10'
        );

        $this->assertEquals(2, $location->id);
        $this->assertEquals('Rotterdam', $location->city);
        $this->assertEquals('Coolsingel 10', $location->address);
        $this->assertNull($location->zip_code);
        $this->assertNull($location->country_code);
        $this->assertNull($location->phone_number);
    }

    public function testLocationCreationWithEmptyStrings(): void
    {
        $location = new Location(
            3,
            '',
            '',
            '',
            '',
            ''
        );

        $this->assertEquals(3, $location->id);
        $this->assertEquals('', $location->city);
        $this->assertEquals('', $location->address);
        $this->assertEquals('', $location->zip_code);
        $this->assertEquals('', $location->country_code);
        $this->assertEquals('', $location->phone_number);
    }

    public function testLocationPropertiesArePublic(): void
    {
        $location = new Location(1);
        
        // Test that we can modify properties directly
        $location->city = 'Utrecht';
        $location->address = 'Neude 1';
        $location->zip_code = '3512AD';
        $location->country_code = 'NL';
        $location->phone_number = '+31-30-1234567';

        $this->assertEquals('Utrecht', $location->city);
        $this->assertEquals('Neude 1', $location->address);
        $this->assertEquals('3512AD', $location->zip_code);
        $this->assertEquals('NL', $location->country_code);
        $this->assertEquals('+31-30-1234567', $location->phone_number);
    }
}