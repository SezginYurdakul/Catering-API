<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Services\TagService;
use App\Repositories\TagRepository;
use App\Models\Tag;

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

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve tag with ID 999');

        $this->tagService->getTagById($tagId);
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
        $this->assertEquals($createdTagId, $result['tag']->id);
        $this->assertEquals('New Event Type', $result['tag']->name);
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
            ->willThrowException(new \Exception('Tag not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to update tag with ID 999');

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
            ->willThrowException(new \Exception('Tag not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to delete tag with ID 999');

        $this->tagService->deleteTag($tag);
    }

    public function testRepositoryExceptionHandling(): void
    {
        $page = 1;
        $perPage = 10;

        $this->mockTagRepository
            ->expects($this->once())
            ->method('getAllTags')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to retrieve tags: Database connection failed');

        $this->tagService->getAllTags($page, $perPage);
    }
}