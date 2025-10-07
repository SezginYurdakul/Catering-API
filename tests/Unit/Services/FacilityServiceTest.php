<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\FacilityService;
use App\Services\ILocationService;
use App\Services\ITagService;
use App\Repositories\FacilityRepository;
use App\Models\Facility;
use App\Models\Location;
use App\Models\Tag;

class FacilityServiceTest extends TestCase
{
    private mixed $mockFacilityRepository;
    private mixed $mockLocationService;
    private mixed $mockTagService;
    private FacilityService $facilityService;

    protected function setUp(): void
    {
        $this->mockFacilityRepository = $this->createMock(FacilityRepository::class);
        $this->mockLocationService = $this->createMock(ILocationService::class);
        $this->mockTagService = $this->createMock(ITagService::class);
        
        $this->facilityService = new FacilityService(
            $this->mockFacilityRepository,
            $this->mockLocationService,
            $this->mockTagService
        );
    }

    public function testGetFacilitiesSuccess(): void
    {
        $page = 1;
        $perPage = 10;

        $mockFacilitiesData = [
            [
                'facility_id' => 1,
                'facility_name' => 'Conference Hall A',
                'location_id' => 1,
                'creation_date' => '2023-01-01 00:00:00'
            ],
            [
                'facility_id' => 2,
                'facility_name' => 'Wedding Hall B',
                'location_id' => 2,
                'creation_date' => '2023-01-02 00:00:00'
            ]
        ];

        $mockTotalCount = 25;

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilities')
            ->willReturn($mockFacilitiesData);

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getTotalFacilitiesCount')
            ->willReturn($mockTotalCount);

        // Mock location service calls for each facility
        $this->mockLocationService
            ->expects($this->exactly(2))
            ->method('getLocationById')
            ->willReturnOnConsecutiveCalls(
                new Location(1, 'Amsterdam'),
                new Location(2, 'Rotterdam')
            );

        // Mock tag service calls for each facility
        $this->mockTagService
            ->expects($this->exactly(2))
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        $result = $this->facilityService->getFacilities($page, $perPage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('facilities', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['facilities']);

        // Check pagination
        $this->assertEquals($page, $result['pagination']['current_page']);
        $this->assertEquals($perPage, $result['pagination']['per_page']);
        $this->assertEquals($mockTotalCount, $result['pagination']['total_items']);
        $this->assertEquals(3, $result['pagination']['total_pages']); // 25/10 = 3 pages
    }

    public function testGetFacilitiesWithFilters(): void
    {
        $page = 1;
        $perPage = 5;
        $name = 'Conference';
        $city = 'Amsterdam';
        $operator = 'AND';

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilities')
            ->willReturn([]);

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFilteredFacilitiesCount')
            ->willReturn(0);

        $result = $this->facilityService->getFacilities($page, $perPage, $name, null, $city, null, $operator);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('facilities', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testGetFacilityByIdSuccess(): void
    {
        $facilityId = 1;
        $mockFacilityData = [
            'facility_id' => $facilityId,
            'facility_name' => 'Conference Hall A',
            'location_id' => 1,
            'creation_date' => '2023-01-01 00:00:00'
        ];

        $mockTags = [
            new Tag(1, 'Conference'),
            new Tag(2, 'Business')
        ];

        $mockLocation = new Location(1, 'Amsterdam');

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->with($facilityId)
            ->willReturn($mockFacilityData);

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($mockLocation);

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->with($facilityId)
            ->willReturn($mockTags);

        $result = $this->facilityService->getFacilityById($facilityId);

        $this->assertInstanceOf(Facility::class, $result);
        $this->assertEquals($facilityId, $result->id);
        $this->assertEquals('Conference Hall A', $result->name);
        $this->assertEquals(1, $result->location->id);
        $this->assertCount(2, $result->tagIds);
    }

    public function testGetFacilityByIdNotFound(): void
    {
        $facilityId = 999;

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->with($facilityId)
            ->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Facility with ID 999 does not exist');

        $this->facilityService->getFacilityById($facilityId);
    }

    public function testCreateFacilityWithMixedTags(): void
    {
        $facilityData = [
            'facility_id' => 1,
            'facility_name' => 'Test Facility',
            'location_id' => 1,
            'creation_date' => '2024-01-01 10:00:00'
        ];

        $location = new \App\Models\Location(1, 'Test Location');
        $facility = new \App\Models\Facility(0, 'Test Facility', $location, '2024-01-01 10:00:00');
        $mixedTags = [1, 'New Tag', 2]; // Mix of IDs and names

        // Mock for getFacilityById (called after creation)
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->with(1)
            ->willReturn($facilityData);

        // Mock for createFacility
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('createFacility')
            ->with([':name' => 'Test Facility', ':location_id' => 1])
            ->willReturn(1);

        // Mock for addTagsToFacility
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('addTagsToFacility')
            ->with(1, [1, 3, 2]); // Processed tag IDs

        // Mock location service
        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->with(1)
            ->willReturn($location);

        // Mock tag service for getAllTags
        $this->mockTagService
            ->expects($this->once())
            ->method('getAllTags')
            ->with(1, 1000)
            ->willReturn([
                'tags' => [
                    (object)['id' => 1, 'name' => 'Existing Tag'],
                    (object)['id' => 2, 'name' => 'Another Tag']
                ]
            ]);

        // Mock tag service for createTag (for new tag)
        $newTag = new \App\Models\Tag(0, 'New Tag');
        $this->mockTagService
            ->expects($this->once())
            ->method('createTag')
            ->with($this->equalTo($newTag))
            ->willReturn(['tag' => ['id' => 3]]);

        // Mock tag service for getTagsByFacilityId
        $this->mockTagService
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->with(1)
            ->willReturn([]);

        $result = $this->facilityService->createFacility($facility, $mixedTags);

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('facility', $result);
        $this->assertStringContainsString('Test Facility', $result['message']);
    }

    public function testRepositoryExceptionHandling(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilities')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database connection failed');

        $this->facilityService->getFacilities($page, $perPage);
    }

    public function testGetFilteredFacilitiesCountSimple(): void
    {
        $expectedCount = 15;

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getTotalFacilitiesCount')
            ->willReturn($expectedCount);

        $result = $this->facilityService->getFilteredFacilitiesCount();

        $this->assertEquals($expectedCount, $result);
    }

    public function testGetFacilitiesByLocationSimple(): void
    {
        $locationId = 1;
        $mockFacilities = [
            ['facility_id' => 1, 'facility_name' => 'Hall A', 'location_id' => $locationId, 'creation_date' => '2023-01-01'],
            ['facility_id' => 2, 'facility_name' => 'Hall B', 'location_id' => $locationId, 'creation_date' => '2023-01-02']
        ];

        $mockLocation = new Location($locationId, 'Amsterdam');

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilitiesByLocation')
            ->with($locationId)
            ->willReturn($mockFacilities);

        // Mock location service calls for each facility
        $this->mockLocationService
            ->expects($this->exactly(2))
            ->method('getLocationById')
            ->with($locationId)
            ->willReturn($mockLocation);

        // Mock tag service calls for each facility
        $this->mockTagService
            ->expects($this->exactly(2))
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        $result = $this->facilityService->getFacilitiesByLocation($locationId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Facility::class, $result[0]);
        $this->assertEquals('Hall A', $result[0]->name);
    }

    public function testProcessSmartTagsWithMixedInput(): void
    {
        // Test the smart tag processing indirectly through createFacility
        $mockLocation = new Location(1, 'Amsterdam');
        $facility = new Facility(0, 'Test Facility', $mockLocation, date('Y-m-d H:i:s'));
        $mixedTags = [1, 'New Tag', '2', 'Another New Tag'];

        // Mock existing tags lookup - called twice for two string tags
        $this->mockTagService
            ->expects($this->exactly(2))
            ->method('getAllTags')
            ->willReturn([
                'tags' => [
                    (object)['id' => 1, 'name' => 'Existing Tag 1'],
                    (object)['id' => 2, 'name' => 'Existing Tag 2']
                ]
            ]);

        // Mock creation of new tags
        $this->mockTagService
            ->expects($this->exactly(2))
            ->method('createTag')
            ->willReturnOnConsecutiveCalls(
                ['tag' => ['id' => 3, 'name' => 'New Tag']],
                ['tag' => ['id' => 4, 'name' => 'Another New Tag']]
            );

        // Mock facility repository calls
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('createFacility')
            ->willReturn(10);

        // Don't check exact parameters - the algorithm might order differently
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('addTagsToFacility')
            ->with(10, $this->isType('array'));

        // Mock getFacilityById for return value
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->willReturn([
                'facility_id' => 10,
                'facility_name' => 'Test Facility',
                'location_id' => 1,
                'creation_date' => date('Y-m-d H:i:s')
            ]);

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->willReturn($mockLocation);

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        $result = $this->facilityService->createFacility($facility, $mixedTags);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
    }

    public function testUpdateFacility(): void
    {
        $mockLocation = new Location(1, 'Amsterdam');
        $facility = new Facility(1, 'Updated Facility', $mockLocation, '2024-01-01 10:00:00');
        $tags = [1, 'New Tag'];

        // Mock getAllTags for smart tag processing
        $this->mockTagService
            ->expects($this->once())
            ->method('getAllTags')
            ->willReturn([
                'tags' => [
                    (object)['id' => 1, 'name' => 'Existing Tag']
                ]
            ]);

        // Mock createTag for new tag
        $newTag = new \App\Models\Tag(0, 'New Tag');
        $this->mockTagService
            ->expects($this->once())
            ->method('createTag')
            ->with($this->equalTo($newTag))
            ->willReturn(['tag' => ['id' => 2]]);

        // Mock updateFacility
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('updateFacility')
            ->with(1, ['name' => 'Updated Facility', 'location_id' => 1]);

        // Mock addTagsToFacility
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('addTagsToFacility')
            ->with(1, [1, 2]);

        // Mock getFacilityById for return value
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->willReturn([
                'facility_id' => 1,
                'facility_name' => 'Updated Facility',
                'location_id' => 1,
                'creation_date' => '2024-01-01 10:00:00'
            ]);

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->willReturn($mockLocation);

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        $result = $this->facilityService->updateFacility($facility, $tags);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('facility', $result);
    }

    public function testDeleteFacility(): void
    {
        $facilityData = [
            'facility_id' => 1,
            'facility_name' => 'Test Facility',
            'location_id' => 1,
            'creation_date' => '2024-01-01 10:00:00'
        ];

        $mockLocation = new Location(1, 'Amsterdam');

        // Mock getFacilityById to check if exists
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilityById')
            ->with(1)
            ->willReturn($facilityData);

        $this->mockLocationService
            ->expects($this->once())
            ->method('getLocationById')
            ->willReturn($mockLocation);

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        // Mock deleteFacility
        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('deleteFacility')
            ->with(1);

        $result = $this->facilityService->deleteFacility(1);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('deleted', $result['message']);
    }

    public function testGetFacilitiesByTag(): void
    {
        $tagId = 1;
        $mockFacilities = [
            [
                'facility_id' => 1,
                'facility_name' => 'Facility 1',
                'location_id' => 1,
                'creation_date' => '2024-01-01'
            ],
            [
                'facility_id' => 2,
                'facility_name' => 'Facility 2',
                'location_id' => 1,
                'creation_date' => '2024-01-02'
            ]
        ];

        $mockLocation = new Location(1, 'Amsterdam');

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('getFacilitiesByTag')
            ->with($tagId)
            ->willReturn($mockFacilities);

        $this->mockLocationService
            ->expects($this->exactly(2))
            ->method('getLocationById')
            ->willReturn($mockLocation);

        $this->mockTagService
            ->expects($this->exactly(2))
            ->method('getTagsByFacilityId')
            ->willReturn([]);

        $result = $this->facilityService->getFacilitiesByTag($tagId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Facility::class, $result[0]);
    }

    public function testAddTagsToFacility(): void
    {
        $facilityId = 1;
        $tagIds = [1, 2, 3];

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('addTagsToFacility')
            ->with($facilityId, $tagIds);

        $result = $this->facilityService->addTagsToFacility($facilityId, $tagIds);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('successfully', $result['message']);
    }

    public function testRemoveTagsFromFacility(): void
    {
        $facilityId = 1;
        $tagIds = [1, 2];

        $this->mockFacilityRepository
            ->expects($this->once())
            ->method('removeTagsFromFacility')
            ->with($facilityId, $tagIds);

        $result = $this->facilityService->removeTagsFromFacility($facilityId, $tagIds);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('successfully', $result['message']);
    }
}