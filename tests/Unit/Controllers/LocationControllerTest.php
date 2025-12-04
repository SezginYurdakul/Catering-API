<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\LocationController;
use App\Services\ILocationService;
use App\Models\Location;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;

class LocationControllerTest extends TestCase
{
    private $mockLocationService;

    protected function setUp(): void
    {
        $this->mockLocationService = $this->createMock(ILocationService::class);
    }

    protected function tearDown(): void
    {
        $_GET = [];

        if (ob_get_level()) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
        }

        if (in_array('php', stream_get_wrappers())) {
            stream_wrapper_restore('php');
        }
    }

    private function createController(): LocationController
    {
        return new LocationController(
            $this->mockLocationService,
            false // Skip parent::__construct() and requireAuth()
        );
    }

    private function mockJsonInput(array $data): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestLocationInputStreamWrapper::class);
        TestLocationInputStreamWrapper::$data = json_encode($data);
    }

    /**
     * Test getAllLocations with default pagination
     */
    public function testGetAllLocationsWithDefaultPagination(): void
    {
        $_GET = [];

        $mockLocationsData = [
            'locations' => [
                new Location(1, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31201234567'),
                new Location(2, 'Rotterdam', 'Coolsingel 10', '3012 AD', 'NL', '+31107654321')
            ],
            'pagination' => [
                'total_items' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('getAllLocations')
            ->with(1, 10)
            ->willReturn($mockLocationsData);

        $controller = $this->createController();

        ob_start();
        $controller->getAllLocations();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        $this->assertArrayHasKey('locations', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(2, $data['locations']);
    }

    /**
     * Test getAllLocations with custom pagination
     */
    public function testGetAllLocationsWithCustomPagination(): void
    {
        $_GET = ['page' => '2', 'per_page' => '5'];

        $mockLocationsData = [
            'locations' => [
                new Location(6, 'Utrecht', 'Domtoren 1', '3512 JE', 'NL', '+31301234567')
            ],
            'pagination' => [
                'total_items' => 10,
                'total_pages' => 2,
                'current_page' => 2,
                'per_page' => 5
            ]
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('getAllLocations')
            ->with(2, 5)
            ->willReturn($mockLocationsData);

        $controller = $this->createController();

        ob_start();
        $controller->getAllLocations();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(5, $data['pagination']['per_page']);
    }

    /**
     * Test getAllLocations with invalid page number
     */
    public function testGetAllLocationsWithInvalidPageNumber(): void
    {
        $_GET = ['page' => '-1'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getAllLocations();
    }

    /**
     * Test getAllLocations with page exceeding total pages
     */
    public function testGetAllLocationsWithPageExceedingTotalPages(): void
    {
        $_GET = ['page' => '10'];

        $mockLocationsData = [
            'locations' => [],
            'pagination' => [
                'total_items' => 5,
                'total_pages' => 1,
                'current_page' => 10,
                'per_page' => 10
            ]
        ];

        $this->mockLocationService
            ->method('getAllLocations')
            ->willReturn($mockLocationsData);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getAllLocations();
    }

    /**
     * Test getLocationById with valid ID
     */
    public function testGetLocationByIdWithValidId(): void
    {
        $mockLocation = new Location(
            1,
            'Amsterdam',
            'Damrak 1',
            '1012 JS',
            'NL',
            '+31201234567'
        );

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($mockLocation);

        $controller = $this->createController();

        ob_start();
        $controller->getLocationById(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('location', $data);
        $this->assertEquals(1, $data['location']['id']);
        $this->assertEquals('Amsterdam', $data['location']['city']);
    }

    /**
     * Test getLocationById with non-existent ID
     */
    public function testGetLocationByIdWithNonExistentId(): void
    {
        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->getLocationById(999);
    }

    /**
     * Test getLocationById with invalid ID
     */
    public function testGetLocationByIdWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getLocationById(-1);
    }

    /**
     * Test createLocation with valid data
     */
    public function testCreateLocationWithValidData(): void
    {
        $this->mockJsonInput([
            'city' => 'Amsterdam',
            'address' => 'Damrak 1',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31201234567'
        ]);

        $mockResult = [
            'message' => 'Location created successfully',
            'location' => new Location(1, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31201234567')
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('createLocation')
            ->with($this->isInstanceOf(Location::class))
            ->willReturn($mockResult);

        $controller = $this->createController();

        ob_start();
        $controller->createLocation();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('created successfully', $data['message']);
    }

    /**
     * Test createLocation with missing required fields
     */
    public function testCreateLocationWithMissingRequiredFields(): void
    {
        $this->mockJsonInput([
            'city' => 'Amsterdam'
            // Missing other required fields
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->createLocation();
    }

    /**
     * Test createLocation with empty fields
     */
    public function testCreateLocationWithEmptyFields(): void
    {
        $this->mockJsonInput([
            'city' => '',
            'address' => 'Damrak 1',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31201234567'
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->createLocation();
    }

    /**
     * Test updateLocation with valid data
     */
    public function testUpdateLocationWithValidData(): void
    {
        $existingLocation = new Location(1, 'Amsterdam', 'Old Address', '1012 JS', 'NL', '+31201234567');

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($existingLocation);

        $this->mockJsonInput([
            'city' => 'Amsterdam',
            'address' => 'New Address',
            'zip_code' => '1012 JS',
            'country_code' => 'NL',
            'phone_number' => '+31209999999'
        ]);

        $mockResult = [
            'message' => 'Location updated successfully',
            'location' => new Location(1, 'Amsterdam', 'New Address', '1012 JS', 'NL', '+31209999999')
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('updateLocation')
            ->with($this->isInstanceOf(Location::class))
            ->willReturn($mockResult);

        $controller = $this->createController();

        ob_start();
        $controller->updateLocation(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('updated successfully', $data['message']);
    }

    /**
     * Test updateLocation with partial data
     */
    public function testUpdateLocationWithPartialData(): void
    {
        $existingLocation = new Location(1, 'Amsterdam', 'Old Address', '1012 JS', 'NL', '+31201234567');

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($existingLocation);

        $this->mockJsonInput([
            'city' => 'Rotterdam'
        ]);

        $mockResult = [
            'message' => 'Location updated successfully',
            'location' => new Location(1, 'Rotterdam', 'Old Address', '1012 JS', 'NL', '+31201234567')
        ];

        $this->mockLocationService
            ->expects($this->once())
            ->method('updateLocation')
            ->willReturn($mockResult);

        $controller = $this->createController();

        ob_start();
        $controller->updateLocation(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Test updateLocation with non-existent ID
     */
    public function testUpdateLocationWithNonExistentId(): void
    {
        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(999)
            ->willReturn(null);

        $this->mockJsonInput([
            'city' => 'Amsterdam'
        ]);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->updateLocation(999);
    }

    /**
     * Test updateLocation with no fields provided
     */
    public function testUpdateLocationWithNoFieldsProvided(): void
    {
        $existingLocation = new Location(1, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31201234567');

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($existingLocation);

        $this->mockJsonInput([]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->updateLocation(1);
    }

    /**
     * Test deleteLocation with valid ID
     */
    public function testDeleteLocationWithValidId(): void
    {
        $existingLocation = new Location(1, 'Amsterdam', 'Damrak 1', '1012 JS', 'NL', '+31201234567');

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($existingLocation);

        $this->mockLocationService
            ->expects($this->once())
            ->method('deleteLocation')
            ->with(1);

        $controller = $this->createController();

        ob_start();
        $controller->deleteLocation(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Location deleted successfully', $data['message']);
    }

    /**
     * Test deleteLocation with non-existent ID
     */
    public function testDeleteLocationWithNonExistentId(): void
    {
        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->deleteLocation(999);
    }

    /**
     * Test deleteLocation with invalid ID
     */
    public function testDeleteLocationWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->deleteLocation(-1);
    }
}

/**
 * Mock stream wrapper for testing file_get_contents('php://input')
 */
class TestLocationInputStreamWrapper
{
    public static $data = '';
    private $position = 0;
    public $context;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $result = substr(self::$data, $this->position, $count);
        $this->position += strlen($result);
        return $result;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(self::$data);
    }

    public function stream_stat()
    {
        return [];
    }

    public function url_stat($path, $flags)
    {
        return [];
    }
}
