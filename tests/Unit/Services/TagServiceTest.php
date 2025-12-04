<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\TagService;
use App\Repositories\TagRepository;
use App\Models\Tag;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\ResourceInUseException;
use App\Domain\Exceptions\DatabaseException;

class TagServiceTest extends TestCase
{
    private TagService $tagService;
    private mixed $mockTagRepository;

    protected function setUp(): void
    {
        $this->mockTagRepository = $this->createMock(TagRepository::class);
        $this->tagService = new TagService($this->mockTagRepository);
    }

    public function testGetAllTagsSuccess(): void
    {
        $page = 1;
        $perPage = 10;
        $offset = 0;

        $mockTagsData = [
            ['id' => 1, 'name' => 'Wedding'],
            ['id' => 2, 'name' => 'Conference'],
            ['id' => 3, 'name' => 'Party']
        ];

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->with($perPage, $offset)
            ->willReturn($mockTagsData);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTotalTagsCount')
            ->willReturn(3);

        $result = $this->tagService->getAllTags($page, $perPage);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('tags', $result);
        $this->assertArrayHasKey('pagination', $result);
        
        $this->assertCount(3, $result['tags']);
        $this->assertInstanceOf(Tag::class, $result['tags'][0]);
        $this->assertEquals('Wedding', $result['tags'][0]->name);
        
        // Test pagination structure
        $this->assertEquals(3, $result['pagination']['total_items']);
        $this->assertEquals(1, $result['pagination']['current_page']);
        $this->assertEquals(10, $result['pagination']['per_page']);
    }

    public function testGetAllTagsWithPagination(): void
    {
        $page = 2;
        $perPage = 5;
        $offset = 5;

        $mockTagsData = [
            ['id' => 6, 'name' => 'Corporate'],
            ['id' => 7, 'name' => 'Outdoor']
        ];

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->with($perPage, $offset)
            ->willReturn($mockTagsData);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTotalTagsCount')
            ->willReturn(12);

        $result = $this->tagService->getAllTags($page, $perPage);

        $this->assertCount(2, $result['tags']);
        $this->assertEquals(12, $result['pagination']['total_items']);
        $this->assertEquals(2, $result['pagination']['current_page']);
        $this->assertEquals(5, $result['pagination']['per_page']);
        $this->assertEquals(3, $result['pagination']['total_pages']);
    }

    public function testGetAllTagsEmptyResult(): void
    {
        $page = 1;
        $perPage = 10;
        $offset = 0;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->with($perPage, $offset)
            ->willReturn([]);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTotalTagsCount')
            ->willReturn(0);

        $result = $this->tagService->getAllTags($page, $perPage);

        $this->assertIsArray($result);
        $this->assertEmpty($result['tags']);
        $this->assertEquals(0, $result['pagination']['total_items']);
    }

    public function testGetTagByIdSuccess(): void
    {
        $tagId = 1;
        $mockTagData = ['id' => 1, 'name' => 'Wedding'];

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn($mockTagData);

        $result = $this->tagService->getTagById($tagId);

        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Wedding', $result->name);
    }

    public function testGetTagByIdNotFound(): void
    {
        $tagId = 999;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn(null);

        $result = $this->tagService->getTagById($tagId);

        $this->assertNull($result);
    }

    public function testGetTagsByFacilityIdSuccess(): void
    {
        $facilityId = 1;
        $mockTagsData = [
            ['id' => 1, 'name' => 'Wedding'],
            ['id' => 2, 'name' => 'Conference']
        ];

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->with($facilityId)
            ->willReturn($mockTagsData);

        $result = $this->tagService->getTagsByFacilityId($facilityId);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(Tag::class, $result[0]);
        $this->assertEquals('Wedding', $result[0]->name);
        $this->assertInstanceOf(Tag::class, $result[1]);
        $this->assertEquals('Conference', $result[1]->name);
    }

    public function testGetTagsByFacilityIdEmpty(): void
    {
        $facilityId = 999;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->with($facilityId)
            ->willReturn([]);

        $result = $this->tagService->getTagsByFacilityId($facilityId);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetTagsByFacilityIdDatabaseException(): void
    {
        $facilityId = 1;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagsByFacilityId')
            ->with($facilityId)
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->tagService->getTagsByFacilityId($facilityId);
    }

    public function testCreateTagSuccess(): void
    {
        $tag = new Tag(0, 'New Event Type');
        $createdTagId = 5;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('New Event Type')
            ->willReturn(true);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('createTag')
            ->with('New Event Type')
            ->willReturn($createdTagId);

        $result = $this->tagService->createTag($tag);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertEquals("Tag 'New Event Type' successfully created.", $result['message']);
        $this->assertInstanceOf(Tag::class, $result['tag']);
        $this->assertEquals($createdTagId, $result['tag']->id);
        $this->assertEquals('New Event Type', $result['tag']->name);
    }

    public function testCreateTagDuplicateName(): void
    {
        $tag = new Tag(0, 'Duplicate Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('Duplicate Tag')
            ->willReturn(false);

        $this->expectException(DuplicateResourceException::class);
        $this->expectExceptionMessage("Tag with name 'Duplicate Tag' already exists");

        $this->tagService->createTag($tag);
    }

    public function testUpdateTagSuccess(): void
    {
        $tag = new Tag(1, 'Updated Wedding');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('Updated Wedding')
            ->willReturn(true);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('updateTag')
            ->with(1, 'Updated Wedding')
            ->willReturn(true);

        $result = $this->tagService->updateTag($tag);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertEquals("Tag 'Updated Wedding' successfully updated.", $result['message']);
        $this->assertEquals(1, $result['tag']->id);
        $this->assertEquals('Updated Wedding', $result['tag']->name);
    }

    public function testUpdateTagDuplicateName(): void
    {
        $tag = new Tag(1, 'Duplicate Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('Duplicate Tag')
            ->willReturn(false);

        $this->expectException(DuplicateResourceException::class);
        $this->expectExceptionMessage("Tag with name 'Duplicate Tag' already exists");

        $this->tagService->updateTag($tag);
    }

    public function testUpdateTagFailure(): void
    {
        $tag = new Tag(999, 'Non-existent Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('Non-existent Tag')
            ->willReturn(true);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('updateTag')
            ->with(999, 'Non-existent Tag')
            ->willReturn(false);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database operation failed: UPDATE on Tags');

        $this->tagService->updateTag($tag);
    }

    public function testDeleteTagSuccess(): void
    {
        $tag = new Tag(1, 'Wedding');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagUsedByFacilities')
            ->with(1)
            ->willReturn(false);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('deleteTag')
            ->with(1)
            ->willReturn(true);

        $result = $this->tagService->deleteTag($tag);

        $this->assertEquals('Tag with ID 1 successfully deleted.', $result);
    }

    public function testDeleteTagUsedByFacilities(): void
    {
        $tag = new Tag(1, 'Wedding');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagUsedByFacilities')
            ->with(1)
            ->willReturn(true);

        $this->expectException(ResourceInUseException::class);
        $this->expectExceptionMessage("This Tag cannot be deleted because it is currently in use by related one or more facilities");

        $this->tagService->deleteTag($tag);
    }

    public function testDeleteTagFailure(): void
    {
        $tag = new Tag(999, 'Non-existent Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagUsedByFacilities')
            ->with(999)
            ->willReturn(false);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('deleteTag')
            ->with(999)
            ->willReturn(false);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database operation failed: DELETE on Tags');

        $this->tagService->deleteTag($tag);
    }

    public function testRepositoryExceptionHandling(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->willThrowException(new \PDOException('Connection refused'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database operation failed: SELECT on Tags');

        $this->tagService->getAllTags($page, $perPage);
    }

    public function testGetAllTagsDatabaseException(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->tagService->getAllTags($page, $perPage);
    }

    public function testGetTagByIdDatabaseException(): void
    {
        $tagId = 1;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->tagService->getTagById($tagId);
    }

    public function testCreateTagDatabaseException(): void
    {
        $tag = new Tag(0, 'New Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('New Tag')
            ->willReturn(true);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('createTag')
            ->with('New Tag')
            ->willThrowException(new \PDOException('Database error'));

        $this->expectException(DatabaseException::class);

        $this->tagService->createTag($tag);
    }

    public function testCreateTagReturnsFalse(): void
    {
        $tag = new Tag(0, 'New Tag');

        $this->mockTagRepository
            ->expects($this->once())
            ->method('isTagNameUnique')
            ->with('New Tag')
            ->willReturn(true);

        $this->mockTagRepository
            ->expects($this->once())
            ->method('createTag')
            ->with('New Tag')
            ->willReturn(0);

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Failed to retrieve tag ID');

        $this->tagService->createTag($tag);
    }
}