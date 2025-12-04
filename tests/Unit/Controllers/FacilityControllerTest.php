<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\FacilityController;
use App\Services\IFacilityService;
use App\Services\ILocationService;
use App\Models\Facility;
use App\Models\Location;
use App\Plugins\Http\Exceptions\ValidationException;

class FacilityControllerTest extends TestCase
{
    private $mockFacilityService;
    private $mockLocationService;

    protected function setUp(): void
    {
        $this->mockFacilityService = $this->createMock(IFacilityService::class);
        $this->mockLocationService = $this->createMock(ILocationService::class);
    }

    /**
     * Helper method to create a controller with mocked dependencies
     */
    private function createController(): FacilityController
    {
        // Pass false to skip BaseController initialization and auth
        return new FacilityController(
            $this->mockFacilityService,
            $this->mockLocationService,
            false // Skip parent::__construct() and requireAuth()
        );
    }

    /**
     * Test getFacilities with default pagination
     */
    public function testGetFacilitiesWithDefaultPagination(): void
    {
        // Clear any existing $_GET
        $_GET = [];

        // Mock location
        $mockLocation = new Location(1, 'Test City', 'Test Address', '12345', 'TR', null);

        // Mock facilities data
        $mockFacilitiesData = [
            'facilities' => [
                new Facility(1, 'Test Facility 1', $mockLocation, '2024-01-01', []),
                new Facility(2, 'Test Facility 2', $mockLocation, '2024-01-02', [])
            ],
            'pagination' => [
                'total_items' => 2,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        // Mock the service call
        $this->mockFacilityService
            ->expects($this->once())
            ->method('getFacilities')
            ->with(
                1,      // page
                10,     // perPage
                null,   // facilityName
                null,   // tag
                null,   // city
                null,   // country
                'AND',  // operator
                [],     // filters
                null    // query
            )
            ->willReturn($mockFacilitiesData);

        $controller = $this->createController();

        // Capture output
        ob_start();
        $controller->getFacilities();
        $output = ob_get_clean();

        // Verify response
        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('facilities', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(2, $data['facilities']);
        $this->assertEquals(2, $data['pagination']['total_items']);
    }

    /**
     * Test getFacilities with custom pagination
     */
    public function testGetFacilitiesWithCustomPagination(): void
    {
        $_GET = ['page' => '2', 'per_page' => '5'];

        $mockLocation = new Location(1, 'Test City', 'Test Address', '12345', 'TR', null);

        $mockFacilitiesData = [
            'facilities' => [
                new Facility(6, 'Test Facility 6', $mockLocation, '2024-01-06', [])
            ],
            'pagination' => [
                'total_items' => 12,
                'total_pages' => 3,
                'current_page' => 2,
                'per_page' => 5
            ]
        ];

        $this->mockFacilityService
            ->expects($this->once())
            ->method('getFacilities')
            ->with(2, 5, null, null, null, null, 'AND', [], null)
            ->willReturn($mockFacilitiesData);

        $controller = $this->createController();

        ob_start();
        $controller->getFacilities();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertEquals(12, $data['pagination']['total_items']);
        $this->assertEquals(2, $data['pagination']['current_page']);
    }

    /**
     * Test getFacilities with query parameter
     */
    public function testGetFacilitiesWithQuery(): void
    {
        $_GET = ['query' => 'test search'];

        $mockLocation = new Location(1, 'Test City', 'Test Address', '12345', 'TR', null);

        $mockFacilitiesData = [
            'facilities' => [
                new Facility(1, 'Test Facility', $mockLocation, '2024-01-01', [])
            ],
            'pagination' => [
                'total_items' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockFacilityService
            ->expects($this->once())
            ->method('getFacilities')
            ->with(1, 10, null, null, null, null, 'AND', [], 'test search')
            ->willReturn($mockFacilitiesData);

        $controller = $this->createController();

        ob_start();
        $controller->getFacilities();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertCount(1, $data['facilities']);
    }

    /**
     * Test getFacilities with filters
     */
    public function testGetFacilitiesWithFilters(): void
    {
        $_GET = [
            'facility_name' => 'Test',
            'city' => 'Istanbul',
            'filter' => 'facility_name,city',
            'operator' => 'OR'
        ];

        $mockLocation = new Location(1, 'Istanbul', 'Kadikoy', '34000', 'TR', null);

        $mockFacilitiesData = [
            'facilities' => [
                new Facility(1, 'Test Facility', $mockLocation, '2024-01-01', [])
            ],
            'pagination' => [
                'total_items' => 1,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockFacilityService
            ->expects($this->once())
            ->method('getFacilities')
            ->with(1, 10, 'Test', null, 'Istanbul', null, 'OR', ['facility_name', 'city'], null)
            ->willReturn($mockFacilitiesData);

        $controller = $this->createController();

        ob_start();
        $controller->getFacilities();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertCount(1, $data['facilities']);
    }

    /**
     * Test getFacilities with invalid page number
     */
    public function testGetFacilitiesWithInvalidPageNumber(): void
    {
        $_GET = ['page' => '-1'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getFacilities();
    }

    /**
     * Test getFacilities with invalid filter
     */
    public function testGetFacilitiesWithInvalidFilter(): void
    {
        $_GET = ['filter' => 'invalid_filter'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getFacilities();
    }

    /**
     * Test getFacilities with page exceeding total pages
     */
    public function testGetFacilitiesWithPageExceedingTotalPages(): void
    {
        $_GET = ['page' => '5'];

        $mockLocation = new Location(1, 'Test City', 'Test Address', '12345', 'TR', null);

        $mockFacilitiesData = [
            'facilities' => [],
            'pagination' => [
                'total_items' => 15,
                'total_pages' => 2,
                'current_page' => 5,
                'per_page' => 10
            ]
        ];

        $this->mockFacilityService
            ->method('getFacilities')
            ->willReturn($mockFacilitiesData);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getFacilities();
    }
}
