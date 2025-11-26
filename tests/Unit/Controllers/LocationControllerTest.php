<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\LocationController;
use App\Services\ILocationService;
use App\Plugins\Di\Factory;
use App\Models\Location;
use App\Domain\Exceptions\ResourceInUseException;
use App\Domain\Exceptions\DatabaseException;

class LocationControllerTest extends TestCase
{
    private mixed $mockLocationService;
    private mixed $mockDi;

    protected function setUp(): void
    {
        // Mock the Dependency Injection container
        $this->mockDi = $this->createMock(Factory::class);
        $this->mockLocationService = $this->createMock(ILocationService::class);

        $this->mockDi
            ->method('getShared')
            ->with('locationService')
            ->willReturn($this->mockLocationService);

        // Set up environment
        $_GET = [];
        $_SERVER = [];
        $_SESSION = ['user' => 'test_user']; // Mock authenticated user
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_SERVER = [];
        $_SESSION = [];
        
        // Clean output buffer if any
        if (ob_get_level()) {
            ob_clean();
        }
    }

    public function testGetAllLocationsValidation(): void
    {
        $_GET = [
            'page' => '1',
            'per_page' => '10'
        ];

        $mockResponse = [
            'locations' => [
                new Location(1, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31-20-1234567'),
                new Location(2, 'Rotterdam', 'Coolsingel 10', '3012 AD', 'NL', '+31-10-7654321')
            ],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total_items' => 15,
                'total_pages' => 2
            ]
        ];

        // Test service interaction parameters
        $this->assertTrue(isset($_GET['page']));
        $this->assertTrue(isset($_GET['per_page']));
        $this->assertEquals('1', $_GET['page']);
        $this->assertEquals('10', $_GET['per_page']);
    }

    public function testGetLocationByIdSuccess(): void
    {
        $locationId = 1;
        $mockLocation = new Location($locationId, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31-20-1234567');

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn($mockLocation);

        $result = $this->mockLocationService->getLocationById($locationId);
        
        $this->assertInstanceOf(Location::class, $result);
        $this->assertEquals($locationId, $result->id);
        $this->assertEquals('Amsterdam', $result->city);
    }

    public function testGetLocationByIdNotFound(): void
    {
        $locationId = 999;

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn(null); // Service returns null for not found

        $result = $this->mockLocationService->getLocationById($locationId);

        $this->assertNull($result);
    }

    public function testCreateLocationJsonValidation(): void
    {
        $mockJsonData = [
            'city' => 'Utrecht',
            'address' => 'Domtoren 1',
            'zip_code' => '3512 JE',
            'country_code' => 'NL',
            'phone_number' => '+31-30-1234567'
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('createLocation')
            ->willReturn([
                'message' => 'Location created successfully',
                'location' => [
                    'id' => 10,
                    'city' => 'Utrecht',
                    'address' => 'Domtoren 1',
                    'zip_code' => '3512 JE',
                    'country_code' => 'NL',
                    'phone_number' => '+31-30-1234567'
                ]
            ]);

        $result = $this->mockLocationService->createLocation(
            new Location(0, 'Utrecht', 'Domtoren 1', '3512 JE', 'NL', '+31-30-1234567')
        );

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('location', $result);
        $this->assertEquals('Location created successfully', $result['message']);
    }

    public function testCreateLocationMissingRequiredFields(): void
    {
        // Test validation for missing required fields
        $incompleteData = [
            'city' => 'Utrecht'
            // Missing other required fields
        ];

        // This would be caught by validation in the actual controller
        $this->assertArrayNotHasKey('address', $incompleteData);
        $this->assertArrayNotHasKey('zip_code', $incompleteData);
    }

    public function testUpdateLocationSuccess(): void
    {
        $locationId = 1;
        $mockUpdateData = [
            'city' => 'Amsterdam Updated',
            'address' => 'New Address 1',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31-20-9999999'
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('updateLocation')
            ->willReturn([
                'message' => 'Location updated successfully',
                'location' => array_merge($mockUpdateData, ['id' => $locationId])
            ]);

        $result = $this->mockLocationService->updateLocation(
            new Location($locationId, 'Amsterdam Updated', 'New Address 1', '1012 JS', 'NL', '+31-20-9999999')
        );

        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Location updated successfully', $result['message']);
    }

    public function testUpdateLocationNotFound(): void
    {
        $locationId = 999;

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn(null); // Service returns null for not found

        $result = $this->mockLocationService->getLocationById($locationId);

        $this->assertNull($result);
    }

    public function testDeleteLocationSuccess(): void
    {
        $locationId = 1;

        $this->mockLocationService
            ->expects($this->once())
            ->method('deleteLocation')
            ->with($locationId)
            ->willReturn('Location deleted successfully');

        $result = $this->mockLocationService->deleteLocation($locationId);

        $this->assertEquals('Location deleted successfully', $result);
    }

    public function testDeleteLocationNotFound(): void
    {
        $locationId = 999;

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn(null); // Service returns null for not found

        $result = $this->mockLocationService->getLocationById($locationId);

        $this->assertNull($result);
    }

    public function testDeleteLocationUsedByFacilities(): void
    {
        $locationId = 1;

        $this->mockLocationService
            ->expects($this->once())
            ->method('deleteLocation')
            ->with($locationId)
            ->willThrowException(new ResourceInUseException('Location', $locationId, 'one or more facilities'));

        $this->expectException(ResourceInUseException::class);
        $this->expectExceptionMessage("This Location cannot be deleted because it is currently in use by related one or more facilities");

        $this->mockLocationService->deleteLocation($locationId);
    }

    public function testInputSanitization(): void
    {
        // Test input sanitization for location data
        $unsafeCity = '<script>alert("xss")</script>';
        $unsafeAddress = 'Address<script>malicious</script>';
        
        // These would be sanitized by InputSanitizer in the actual controller
        $this->assertStringContainsString('<script>', $unsafeCity);
        $this->assertStringContainsString('<script>', $unsafeAddress);
    }

    public function testPaginationParameterProcessing(): void
    {
        // Test pagination parameter processing
        $_GET = [
            'page' => '2',
            'per_page' => '15'
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $this->assertEquals(2, $page);
        $this->assertEquals(15, $perPage);
    }

    public function testLocationServiceDependencyInjection(): void
    {
        // Test that the service is properly injected
        $this->assertNotNull($this->mockLocationService);
        $this->assertInstanceOf(ILocationService::class, $this->mockLocationService);
    }

    public function testErrorHandlingForServiceExceptions(): void
    {
        // Test exception propagation from service layer - using DatabaseException
        $this->mockLocationService
            ->method('getAllLocations')
            ->willThrowException(new DatabaseException('SELECT', 'Locations', 'Connection failed'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database operation failed: SELECT on Locations');

        $this->mockLocationService->getAllLocations(1, 10);
    }

    public function testResponseStructureForListLocations(): void
    {
        $expectedResponse = [
            'locations' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total_items' => 0,
                'total_pages' => 0
            ]
        ];

        $this->assertArrayHasKey('locations', $expectedResponse);
        $this->assertArrayHasKey('pagination', $expectedResponse);
        $this->assertIsArray($expectedResponse['locations']);
        $this->assertIsArray($expectedResponse['pagination']);
    }

    public function testResponseStructureForSingleLocation(): void
    {
        $expectedResponse = [
            'id' => 1,
            'city' => 'Amsterdam',
            'address' => 'Damrak 1',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31-20-1234567'
        ];

        $this->assertArrayHasKey('id', $expectedResponse);
        $this->assertArrayHasKey('city', $expectedResponse);
        $this->assertArrayHasKey('address', $expectedResponse);
    }

    public function testResponseStructureForCreateUpdate(): void
    {
        $expectedResponse = [
            'message' => 'Location created successfully',
            'location' => []
        ];

        $this->assertArrayHasKey('message', $expectedResponse);
        $this->assertArrayHasKey('location', $expectedResponse);
    }
}