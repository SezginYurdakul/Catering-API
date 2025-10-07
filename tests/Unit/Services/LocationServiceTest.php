<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\LocationService;
use App\Repositories\LocationRepository;
use App\Models\Location;

class LocationServiceTest extends TestCase
{
    private mixed $mockLocationRepository;
    private LocationService $locationService;

    protected function setUp(): void
    {
        $this->mockLocationRepository = $this->createMock(LocationRepository::class);
        $this->locationService = new LocationService($this->mockLocationRepository);
    }

    public function testGetAllLocationsSuccess(): void
    {
        $page = 1;
        $perPage = 10;
        $offset = 0;

        $mockLocationsData = [
            [
                'id' => 1,
                'city' => 'Amsterdam',
                'address' => 'Damrak 1',
                'zip_code' => '1012 JS',
                'country_code' => 'NL',
                'phone_number' => '+31-20-1234567'
            ],
            [
                'id' => 2,
                'city' => 'Rotterdam',
                'address' => 'Coolsingel 10',
                'zip_code' => '3012 AD',
                'country_code' => 'NL',
                'phone_number' => '+31-10-7654321'
            ]
        ];

        $mockTotalCount = 25;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getAllLocations')
            ->with($perPage, $offset)
            ->willReturn($mockLocationsData);

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getTotalLocationsCount')
            ->willReturn($mockTotalCount);

        $result = $this->locationService->getAllLocations($page, $perPage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('locations', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['locations']);

        // Check first location
        $firstLocation = $result['locations'][0];
        $this->assertInstanceOf(Location::class, $firstLocation);
        $this->assertEquals(1, $firstLocation->id);
        $this->assertEquals('Amsterdam', $firstLocation->city);

        // Check pagination
        $this->assertEquals($page, $result['pagination']['current_page']);
        $this->assertEquals($perPage, $result['pagination']['per_page']);
        $this->assertEquals($mockTotalCount, $result['pagination']['total_items']);
        $this->assertEquals(3, $result['pagination']['total_pages']); // 25/10 = 3 pages
    }

    public function testGetAllLocationsWithPagination(): void
    {
        $page = 2;
        $perPage = 5;
        $offset = 5;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getAllLocations')
            ->with($perPage, $offset)
            ->willReturn([]);

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getTotalLocationsCount')
            ->willReturn(0);

        $result = $this->locationService->getAllLocations($page, $perPage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('locations', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(0, $result['locations']);
    }

    public function testGetAllLocationsEmptyResult(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getAllLocations')
            ->willReturn([]);

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getTotalLocationsCount')
            ->willReturn(0);

        $result = $this->locationService->getAllLocations($page, $perPage);

        $this->assertIsArray($result);
        $this->assertEquals([], $result['locations']);
        $this->assertEquals(0, $result['pagination']['total_items']);
    }

    public function testGetLocationByIdSuccess(): void
    {
        $locationId = 1;
        $mockLocationData = [
            'id' => $locationId,
            'city' => 'Amsterdam',
            'address' => 'Damrak 1',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31-20-1234567'
        ];

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn($mockLocationData);

        $result = $this->locationService->getLocationById($locationId);

        $this->assertInstanceOf(Location::class, $result);
        $this->assertEquals($locationId, $result->id);
        $this->assertEquals('Amsterdam', $result->city);
        $this->assertEquals('Damrak 1', $result->address);
        $this->assertEquals('1012 JS', $result->zip_code);
        $this->assertEquals('NL', $result->country_code);
        $this->assertEquals('+31-20-1234567', $result->phone_number);
    }

    public function testGetLocationByIdNotFound(): void
    {
        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getLocationById')
            ->with(999)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve location with ID 999: Location with ID 999 does not exist.');

        $this->locationService->getLocationById(999);
    }

    public function testCreateLocationSuccess(): void
    {
        $location = new Location(0, 'Utrecht', 'Domtoren 1', '3512 JE', 'NL', '+31-30-1234567');
        $mockCreatedLocationId = 10;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('createLocation')
            ->with([
                ':city' => 'Utrecht',
                ':address' => 'Domtoren 1',
                ':zip_code' => '3512 JE',
                ':country_code' => 'NL',
                ':phone_number' => '+31-30-1234567'
            ])
            ->willReturn($mockCreatedLocationId);

        $result = $this->locationService->createLocation($location);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertEquals("Location with ID: '10' successfully created.", $result['message']);
        $this->assertInstanceOf(Location::class, $result['location']);
        $this->assertEquals(10, $result['location']->id);
    }

    public function testUpdateLocationSuccess(): void
    {
        $location = new Location(1, 'Amsterdam Updated', 'New Address 1', '1012 JS', 'NL', '+31-20-9999999');

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('updateLocation')
            ->with(1, [
                'city' => 'Amsterdam Updated',
                'address' => 'New Address 1',
                'zip_code' => '1012 JS',
                'country_code' => 'NL',
                'phone_number' => '+31-20-9999999'
            ])
            ->willReturn(1);

        $result = $this->locationService->updateLocation($location);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertEquals('Location with ID 1 successfully updated.', $result['message']);
    }

    public function testUpdateLocationNotFound(): void
    {
        $location = new Location(999, 'Non-existent City', 'Some Address', '0000 XX', 'XX', '+00-00-0000000');

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('updateLocation')
            ->with(999, [
                'city' => 'Non-existent City',
                'address' => 'Some Address',
                'zip_code' => '0000 XX',
                'country_code' => 'XX',
                'phone_number' => '+00-00-0000000'
            ])
            ->willThrowException(new \Exception('Location with ID 999 not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update location with ID 999: Location with ID 999 not found');

        $this->locationService->updateLocation($location);
    }

    public function testDeleteLocationSuccess(): void
    {
        $locationId = 1;

        // Mock location not used by facilities
        $this->mockLocationRepository
            ->expects($this->once())
            ->method('isLocationUsedByFacilities')
            ->with($locationId)
            ->willReturn(false);

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('deleteLocation')
            ->with($locationId)
            ->willReturn(true);

        $result = $this->locationService->deleteLocation($locationId);

        $this->assertEquals('Location with ID 1 successfully deleted.', $result);
    }

    public function testDeleteLocationNotFound(): void
    {
        $locationId = 999;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('isLocationUsedByFacilities')
            ->with($locationId)
            ->willReturn(false);

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('deleteLocation')
            ->with($locationId)
            ->willThrowException(new \Exception('Location with ID 999 not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to delete location with ID 999: Location with ID 999 not found');

        $this->locationService->deleteLocation($locationId);
    }

    public function testDeleteLocationUsedByFacilities(): void
    {
        $locationId = 1;

        // Mock location is used by facilities
        $this->mockLocationRepository
            ->expects($this->once())
            ->method('isLocationUsedByFacilities')
            ->with($locationId)
            ->willReturn(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Location with ID 1 cannot be deleted because it is associated with one or more facilities.');

        $this->locationService->deleteLocation($locationId);
    }

    public function testRepositoryExceptionHandling(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getAllLocations')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve locations: Database connection failed');

        $this->locationService->getAllLocations($page, $perPage);
    }

    public function testGetTotalLocationsCount(): void
    {
        $expectedCount = 42;

        $this->mockLocationRepository
            ->expects($this->once())
            ->method('getTotalLocationsCount')
            ->willReturn($expectedCount);

        $result = $this->locationService->getTotalLocationsCount();

        $this->assertEquals($expectedCount, $result);
    }
}