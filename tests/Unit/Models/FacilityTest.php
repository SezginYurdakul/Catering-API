<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Facility;
use App\Models\Location;

class FacilityTest extends TestCase
{
    private function createSampleLocation(): Location
    {
        return new Location(
            1,
            'Amsterdam',
            'Damrak 1',
            '1012AB',
            'NL',
            '+31-20-1234567'
        );
    }

    public function testFacilityCreationWithAllFields(): void
    {
        $location = $this->createSampleLocation();
        $tagIds = [
            ['id' => 1, 'name' => 'Wedding'],
            ['id' => 2, 'name' => 'Conference']
        ];

        $facility = new Facility(
            1,
            'Grand Conference Center',
            $location,
            '2024-01-15 10:30:00',
            $tagIds
        );

        $this->assertEquals(1, $facility->id);
        $this->assertEquals('Grand Conference Center', $facility->name);
        $this->assertEquals($location, $facility->location);
        $this->assertEquals('2024-01-15 10:30:00', $facility->creation_date);
        $this->assertEquals($tagIds, $facility->tagIds);
    }

    public function testFacilityCreationWithoutTags(): void
    {
        $location = $this->createSampleLocation();

        $facility = new Facility(
            2,
            'Simple Venue',
            $location,
            '2024-01-15 10:30:00'
        );

        $this->assertEquals(2, $facility->id);
        $this->assertEquals('Simple Venue', $facility->name);
        $this->assertEquals($location, $facility->location);
        $this->assertEquals('2024-01-15 10:30:00', $facility->creation_date);
        $this->assertEquals([], $facility->tagIds); // Should default to empty array
    }

    public function testFacilityCreationWithNullTags(): void
    {
        $location = $this->createSampleLocation();

        $facility = new Facility(
            3,
            'Another Venue',
            $location,
            '2024-01-15 10:30:00',
            null
        );

        $this->assertEquals(3, $facility->id);
        $this->assertEquals('Another Venue', $facility->name);
        $this->assertEquals($location, $facility->location);
        $this->assertEquals('2024-01-15 10:30:00', $facility->creation_date);
        $this->assertEquals([], $facility->tagIds); // Should default to empty array
    }

    public function testFacilityCreationWithEmptyTagsArray(): void
    {
        $location = $this->createSampleLocation();

        $facility = new Facility(
            4,
            'Empty Tags Venue',
            $location,
            '2024-01-15 10:30:00',
            []
        );

        $this->assertEquals(4, $facility->id);
        $this->assertEquals('Empty Tags Venue', $facility->name);
        $this->assertEquals($location, $facility->location);
        $this->assertEquals('2024-01-15 10:30:00', $facility->creation_date);
        $this->assertEquals([], $facility->tagIds);
    }

    public function testFacilityLocationRelationship(): void
    {
        $location = $this->createSampleLocation();
        $facility = new Facility(
            5,
            'Location Test Venue',
            $location,
            '2024-01-15 10:30:00'
        );

        // Test that the facility holds the correct location object
        $this->assertInstanceOf(Location::class, $facility->location);
        $this->assertEquals(1, $facility->location->id);
        $this->assertEquals('Amsterdam', $facility->location->city);
        $this->assertEquals('Damrak 1', $facility->location->address);
    }

    public function testFacilityPropertiesArePublic(): void
    {
        $location = $this->createSampleLocation();
        $facility = new Facility(
            6,
            'Original Name',
            $location,
            '2024-01-15 10:30:00'
        );
        
        // Test that we can modify properties directly
        $newLocation = new Location(2, 'Rotterdam');
        $facility->id = 999;
        $facility->name = 'Modified Name';
        $facility->location = $newLocation;
        $facility->creation_date = '2024-12-25 15:45:00';
        $facility->tagIds = [['id' => 3, 'name' => 'Party']];

        $this->assertEquals(999, $facility->id);
        $this->assertEquals('Modified Name', $facility->name);
        $this->assertEquals($newLocation, $facility->location);
        $this->assertEquals('2024-12-25 15:45:00', $facility->creation_date);
        $this->assertEquals([['id' => 3, 'name' => 'Party']], $facility->tagIds);
    }

    public function testFacilityWithComplexTagStructure(): void
    {
        $location = $this->createSampleLocation();
        $complexTags = [
            ['id' => 1, 'name' => 'Wedding', 'category' => 'Social'],
            ['id' => 2, 'name' => 'Corporate Event', 'category' => 'Business'],
            ['id' => 3, 'name' => 'Birthday Party', 'category' => 'Social']
        ];

        $facility = new Facility(
            7,
            'Multi-Purpose Venue',
            $location,
            '2024-01-15 10:30:00',
            $complexTags
        );

        $this->assertEquals($complexTags, $facility->tagIds);
        $this->assertCount(3, $facility->tagIds);
    }
}