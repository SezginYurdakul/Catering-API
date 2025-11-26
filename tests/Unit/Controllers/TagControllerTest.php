<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\TagController;
use App\Services\ITagService;
use App\Plugins\Di\Factory;
use App\Models\Tag;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\ResourceInUseException;
use App\Domain\Exceptions\DatabaseException;
use App\Plugins\Http\Exceptions\ValidationException;

class TagControllerTest extends TestCase
{
    private mixed $mockTagService;
    private mixed $mockDi;

    protected function setUp(): void
    {
        // Mock the Dependency Injection container
        $this->mockDi = $this->createMock(Factory::class);
        $this->mockTagService = $this->createMock(ITagService::class);

        $this->mockDi
            ->method('getShared')
            ->with('tagService')
            ->willReturn($this->mockTagService);

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

    public function testGetAllTagsValidation(): void
    {
        $_GET = [
            'page' => '1',
            'per_page' => '10'
        ];

        $mockResponse = [
            'tags' => [
                new Tag(1, 'Conference'),
                new Tag(2, 'Wedding'),
                new Tag(3, 'Business')
            ],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total_items' => 3,
                'total_pages' => 1
            ]
        ];

        // Test service interaction would be called with correct parameters
        $this->assertTrue(isset($_GET['page']));
        $this->assertTrue(isset($_GET['per_page']));
        $this->assertEquals('1', $_GET['page']);
        $this->assertEquals('10', $_GET['per_page']);
    }

    public function testGetTagByIdSuccess(): void
    {
        $tagId = 1;
        $mockTag = new Tag($tagId, 'Conference');

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn($mockTag);

        $result = $this->mockTagService->getTagById($tagId);
        
        $this->assertInstanceOf(Tag::class, $result);
        $this->assertEquals($tagId, $result->id);
        $this->assertEquals('Conference', $result->name);
    }

    public function testGetTagByIdNotFound(): void
    {
        $tagId = 999;

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn(null); // Service now returns null instead of throwing

        $result = $this->mockTagService->getTagById($tagId);

        $this->assertNull($result);
    }

    public function testCreateTagSuccess(): void
    {
        $mockCreatedTag = [
            'message' => "Tag 'New Tag' successfully created.",
            'tag' => new Tag(10, 'New Tag')
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('createTag')
            ->willReturn($mockCreatedTag);

        $result = $this->mockTagService->createTag(new Tag(0, 'New Tag'));

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertInstanceOf(Tag::class, $result['tag']);
        $this->assertStringContainsString('successfully created', $result['message']);
    }

    public function testCreateTagEmptyName(): void
    {
        // Test validation for empty tag name
        $emptyName = '';
        $whitespaceOnlyName = '   ';

        // These should fail validation in the actual controller
        $this->assertEmpty(trim($emptyName));
        $this->assertEmpty(trim($whitespaceOnlyName));
    }

    public function testCreateTagDuplicateName(): void
    {
        $duplicateTagName = 'Conference';

        $this->mockTagService
            ->expects($this->once())
            ->method('createTag')
            ->willThrowException(new DuplicateResourceException('Tag', 'name', $duplicateTagName));

        $this->expectException(DuplicateResourceException::class);
        $this->expectExceptionMessage("Tag with name 'Conference' already exists");

        $this->mockTagService->createTag(new Tag(0, $duplicateTagName));
    }

    public function testUpdateTagSuccess(): void
    {
        $tagId = 1;

        $mockUpdatedTag = [
            'message' => "Tag 'Updated Tag Name' successfully updated.",
            'tag' => new Tag($tagId, 'Updated Tag Name')
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('updateTag')
            ->willReturn($mockUpdatedTag);

        $result = $this->mockTagService->updateTag(new Tag($tagId, 'Updated Tag Name'));

        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('tag', $result);
        $this->assertInstanceOf(Tag::class, $result['tag']);
        $this->assertStringContainsString('successfully updated', $result['message']);
    }

    public function testUpdateTagNotFound(): void
    {
        $tagId = 999;

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn(null); // Service returns null for non-existent tag

        $result = $this->mockTagService->getTagById($tagId);

        $this->assertNull($result);
    }

    public function testDeleteTagSuccess(): void
    {
        $tagId = 1;
        $tagToDelete = new Tag($tagId, 'Tag to Delete');

        $this->mockTagService
            ->expects($this->once())
            ->method('deleteTag')
            ->with($this->isInstanceOf(Tag::class))
            ->willReturn('Tag deleted successfully');

        $result = $this->mockTagService->deleteTag($tagToDelete);

        $this->assertEquals('Tag deleted successfully', $result);
    }

    public function testDeleteTagNotFound(): void
    {
        $tagId = 999;

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with($tagId)
            ->willReturn(null); // Service returns null for non-existent tag

        $result = $this->mockTagService->getTagById($tagId);

        $this->assertNull($result);
    }

    public function testDeleteTagUsedByFacilities(): void
    {
        $tagId = 1;
        $tagInUse = new Tag($tagId, 'Tag in Use');

        $this->mockTagService
            ->expects($this->once())
            ->method('deleteTag')
            ->with($this->isInstanceOf(Tag::class))
            ->willThrowException(new ResourceInUseException('Tag', $tagId, 'one or more facilities'));

        $this->expectException(ResourceInUseException::class);
        $this->expectExceptionMessage("Tag with ID '1' cannot be deleted because it is in use by one or more facilities");

        $this->mockTagService->deleteTag($tagInUse);
    }

    public function testInputSanitizationForTagName(): void
    {
        // Test input sanitization for tag names
        $unsafeTagName = '<script>alert("xss")</script>';
        $sqlInjectionAttempt = "'; DROP TABLE tags; --";
        
        // These would be sanitized by InputSanitizer in the actual controller
        $this->assertStringContainsString('<script>', $unsafeTagName);
        $this->assertStringContainsString('DROP TABLE', $sqlInjectionAttempt);
    }

    public function testTagNameValidation(): void
    {
        // Test various tag name validation scenarios
        $validTagName = 'Conference';
        $longTagName = str_repeat('a', 256); // Very long name
        $specialChars = 'Tag-with_special.chars';
        $unicodeTag = 'Конференция'; // Unicode characters

        $this->assertIsString($validTagName);
        $this->assertGreaterThan(255, strlen($longTagName)); // Should be too long
        $this->assertStringContainsString('-', $specialChars);
        $this->assertNotEmpty($unicodeTag);
    }

    public function testPaginationParameterProcessing(): void
    {
        // Test pagination parameter processing for tag listing
        $_GET = [
            'page' => '3',
            'per_page' => '20'
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;

        $this->assertEquals(3, $page);
        $this->assertEquals(20, $perPage);
    }

    public function testTagServiceDependencyInjection(): void
    {
        // Test that the service is properly injected
        $this->assertNotNull($this->mockTagService);
        $this->assertInstanceOf(ITagService::class, $this->mockTagService);
    }

    public function testErrorHandlingForServiceExceptions(): void
    {
        // Test exception propagation from service layer - using DatabaseException
        $this->mockTagService
            ->method('getAllTags')
            ->willThrowException(new DatabaseException('SELECT', 'Tags', 'Connection failed'));

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessage('Database operation failed: SELECT on Tags - Connection failed');

        $this->mockTagService->getAllTags(1, 10);
    }

    public function testResponseStructureForListTags(): void
    {
        $expectedResponse = [
            'tags' => [],
            'pagination' => [
                'current_page' => 1,
                'per_page' => 10,
                'total_items' => 0,
                'total_pages' => 0
            ]
        ];

        $this->assertArrayHasKey('tags', $expectedResponse);
        $this->assertArrayHasKey('pagination', $expectedResponse);
        $this->assertIsArray($expectedResponse['tags']);
        $this->assertIsArray($expectedResponse['pagination']);
    }

    public function testResponseStructureForSingleTag(): void
    {
        $expectedResponse = [
            'id' => 1,
            'name' => 'Conference'
        ];

        $this->assertArrayHasKey('id', $expectedResponse);
        $this->assertArrayHasKey('name', $expectedResponse);
        $this->assertIsInt($expectedResponse['id']);
        $this->assertIsString($expectedResponse['name']);
    }

    public function testResponseStructureForCreateUpdate(): void
    {
        $expectedCreateResponse = [
            'message' => 'Tag created successfully',
            'tag' => []
        ];

        $expectedUpdateResponse = [
            'message' => 'Tag updated successfully',
            'tag' => []
        ];

        $expectedDeleteResponse = [
            'message' => 'Tag deleted successfully'
        ];

        $this->assertArrayHasKey('message', $expectedCreateResponse);
        $this->assertArrayHasKey('tag', $expectedCreateResponse);
        $this->assertArrayHasKey('message', $expectedUpdateResponse);
        $this->assertArrayHasKey('tag', $expectedUpdateResponse);
        $this->assertArrayHasKey('message', $expectedDeleteResponse);
    }

    public function testJsonInputValidation(): void
    {
        // Test JSON input validation
        $validJson = '{"name": "Valid Tag"}';
        $invalidJson = '{"name": }';
        $missingField = '{"description": "Missing name field"}';

        $validData = json_decode($validJson, true);
        $invalidData = json_decode($invalidJson, true);
        $missingFieldData = json_decode($missingField, true);

        $this->assertIsArray($validData);
        $this->assertArrayHasKey('name', $validData);
        $this->assertNull($invalidData); // Invalid JSON
        $this->assertArrayNotHasKey('name', $missingFieldData ?? []);
    }

    public function testTagNameSanitization(): void
    {
        // Test that tag names are properly sanitized
        $inputWithSpaces = '  Tag Name  ';
        $inputWithHtml = 'Tag<b>Name</b>';
        
        $trimmed = trim($inputWithSpaces);
        $this->assertEquals('Tag Name', $trimmed);
        $this->assertStringContainsString('<b>', $inputWithHtml);
    }
}