<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use PHPUnit\Framework\TestCase;
use App\Controllers\TagController;
use App\Services\ITagService;
use App\Models\Tag;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;

class TagControllerTest extends TestCase
{
    private $mockTagService;

    protected function setUp(): void
    {
        $this->mockTagService = $this->createMock(ITagService::class);
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

    private function createController(): TagController
    {
        return new TagController(
            $this->mockTagService,
            false // Skip parent::__construct() and requireAuth()
        );
    }

    private function mockJsonInput(array $data): void
    {
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestTagInputStreamWrapper::class);
        TestTagInputStreamWrapper::$data = json_encode($data);
    }

    /**
     * Test getAllTags with default pagination
     */
    public function testGetAllTagsWithDefaultPagination(): void
    {
        $_GET = [];

        $mockTagsData = [
            'tags' => [
                new Tag(1, 'Conference'),
                new Tag(2, 'Wedding'),
                new Tag(3, 'Business')
            ],
            'pagination' => [
                'total_items' => 3,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => 10
            ]
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('getAllTags')
            ->with(1, 10)
            ->willReturn($mockTagsData);

        $controller = $this->createController();

        ob_start();
        $controller->getAllTags();
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $data = json_decode($output, true);
        $this->assertArrayHasKey('tags', $data);
        $this->assertArrayHasKey('pagination', $data);
        $this->assertCount(3, $data['tags']);
    }

    /**
     * Test getAllTags with custom pagination
     */
    public function testGetAllTagsWithCustomPagination(): void
    {
        $_GET = ['page' => '2', 'per_page' => '5'];

        $mockTagsData = [
            'tags' => [
                new Tag(6, 'Corporate')
            ],
            'pagination' => [
                'total_items' => 10,
                'total_pages' => 2,
                'current_page' => 2,
                'per_page' => 5
            ]
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('getAllTags')
            ->with(2, 5)
            ->willReturn($mockTagsData);

        $controller = $this->createController();

        ob_start();
        $controller->getAllTags();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertEquals(2, $data['pagination']['current_page']);
        $this->assertEquals(5, $data['pagination']['per_page']);
    }

    /**
     * Test getAllTags with invalid page number
     */
    public function testGetAllTagsWithInvalidPageNumber(): void
    {
        $_GET = ['page' => '-1'];

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getAllTags();
    }

    /**
     * Test getAllTags with page exceeding total pages
     */
    public function testGetAllTagsWithPageExceedingTotalPages(): void
    {
        $_GET = ['page' => '10'];

        $mockTagsData = [
            'tags' => [],
            'pagination' => [
                'total_items' => 5,
                'total_pages' => 1,
                'current_page' => 10,
                'per_page' => 10
            ]
        ];

        $this->mockTagService
            ->method('getAllTags')
            ->willReturn($mockTagsData);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getAllTags();
    }

    /**
     * Test getTagById with valid ID
     */
    public function testGetTagByIdWithValidId(): void
    {
        $mockTag = new Tag(1, 'Conference');

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(1)
            ->willReturn($mockTag);

        $controller = $this->createController();

        ob_start();
        $controller->getTagById(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('tag', $data);
        $this->assertEquals(1, $data['tag']['id']);
        $this->assertEquals('Conference', $data['tag']['name']);
    }

    /**
     * Test getTagById with non-existent ID
     */
    public function testGetTagByIdWithNonExistentId(): void
    {
        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->getTagById(999);
    }

    /**
     * Test getTagById with invalid ID
     */
    public function testGetTagByIdWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->getTagById(-1);
    }

    /**
     * Test createTag with valid data
     */
    public function testCreateTagWithValidData(): void
    {
        $this->mockJsonInput([
            'name' => 'Conference'
        ]);

        $mockResult = [
            'message' => "Tag 'Conference' successfully created.",
            'tag' => new Tag(1, 'Conference')
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('createTag')
            ->with($this->isInstanceOf(Tag::class))
            ->willReturn($mockResult);

        $controller = $this->createController();

        ob_start();
        $controller->createTag();
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully created', $data['message']);
    }

    /**
     * Test createTag with missing name field
     */
    public function testCreateTagWithMissingNameField(): void
    {
        $this->mockJsonInput([]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->createTag();
    }

    /**
     * Test createTag with empty name
     */
    public function testCreateTagWithEmptyName(): void
    {
        $this->mockJsonInput([
            'name' => ''
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->createTag();
    }

    /**
     * Test createTag with whitespace only name
     */
    public function testCreateTagWithWhitespaceOnlyName(): void
    {
        $this->mockJsonInput([
            'name' => '   '
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->createTag();
    }

    /**
     * Test updateTag with valid data
     */
    public function testUpdateTagWithValidData(): void
    {
        $existingTag = new Tag(1, 'Old Name');

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(1)
            ->willReturn($existingTag);

        $this->mockJsonInput([
            'name' => 'New Name'
        ]);

        $mockResult = [
            'message' => "Tag 'New Name' successfully updated.",
            'tag' => new Tag(1, 'New Name')
        ];

        $this->mockTagService
            ->expects($this->once())
            ->method('updateTag')
            ->with($this->isInstanceOf(Tag::class))
            ->willReturn($mockResult);

        $controller = $this->createController();

        ob_start();
        $controller->updateTag(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertStringContainsString('successfully updated', $data['message']);
    }

    /**
     * Test updateTag with non-existent ID
     */
    public function testUpdateTagWithNonExistentId(): void
    {
        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(999)
            ->willReturn(null);

        $this->mockJsonInput([
            'name' => 'New Name'
        ]);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->updateTag(999);
    }

    /**
     * Test updateTag with invalid ID
     */
    public function testUpdateTagWithInvalidId(): void
    {
        $this->mockJsonInput([
            'name' => 'New Name'
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->updateTag(-1);
    }

    /**
     * Test updateTag with empty name
     */
    public function testUpdateTagWithEmptyName(): void
    {
        $existingTag = new Tag(1, 'Old Name');

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(1)
            ->willReturn($existingTag);

        $this->mockJsonInput([
            'name' => ''
        ]);

        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->updateTag(1);
    }

    /**
     * Test deleteTag with valid ID
     */
    public function testDeleteTagWithValidId(): void
    {
        $existingTag = new Tag(1, 'Conference');

        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(1)
            ->willReturn($existingTag);

        $this->mockTagService
            ->expects($this->once())
            ->method('deleteTag')
            ->with($this->isInstanceOf(Tag::class));

        $controller = $this->createController();

        ob_start();
        $controller->deleteTag(1);
        $output = ob_get_clean();

        $data = json_decode($output, true);
        $this->assertArrayHasKey('message', $data);
        $this->assertEquals('Tag deleted successfully', $data['message']);
    }

    /**
     * Test deleteTag with non-existent ID
     */
    public function testDeleteTagWithNonExistentId(): void
    {
        $this->mockTagService
            ->expects($this->once())
            ->method('getTagById')
            ->with(999)
            ->willReturn(null);

        $controller = $this->createController();

        $this->expectException(NotFound::class);
        $controller->deleteTag(999);
    }

    /**
     * Test deleteTag with invalid ID
     */
    public function testDeleteTagWithInvalidId(): void
    {
        $controller = $this->createController();

        $this->expectException(ValidationException::class);
        $controller->deleteTag(-1);
    }
}

/**
 * Mock stream wrapper for testing file_get_contents('php://input')
 */
class TestTagInputStreamWrapper
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
