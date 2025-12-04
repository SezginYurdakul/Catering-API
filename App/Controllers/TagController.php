<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ITagService;
use App\Models\Tag;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Exceptions\ValidationException;
use App\Plugins\Http\Exceptions\NotFound;
use App\Helpers\InputSanitizer;
use App\Helpers\Validator;

class TagController extends BaseController
{
    private ITagService $tagService;

    public function __construct(
        ITagService $tagService,
        bool $initializeBase = true
    ) {
        if ($initializeBase) {
            parent::__construct();
        }
        $this->tagService = $tagService;
        if ($initializeBase) {
            $this->requireAuth();
        }
    }

    /**
     * Get all tags with pagination.
     */
    public function getAllTags(): void
    {
        $errors = [];

        // Pagination validation
        $errors = array_merge($errors, Validator::validatePagination($_GET));

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

        // Get tags
        $tags = $this->tagService->getAllTags($page, $perPage);

        // Validate page limit
        $totalItems = $tags['pagination']['total_items'];
        $totalPages = (int) ceil($totalItems / $perPage);
        
        if ($totalPages > 0 && $page > $totalPages) {
            throw new ValidationException([
                'page' => "The requested page ($page) exceeds the total number of pages ($totalPages)."
            ]);
        }

        $response = new Ok($tags);
        $response->send();
    }

    /**
     * Get a specific tag by its ID.
     */
    public function getTagById(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        $tag = $this->tagService->getTagById($id);

        if (!$tag) {
            throw new NotFound('', 'Tag', (string)$id);
        }

        $response = new Ok(['tag' => $tag]);
        $response->send();
    }

    /**
     * Create a new tag.
     */
    public function createTag(): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $errors = [];

        // Validate required fields
        $errors = array_merge($errors, Validator::validateRequired($data, ['name']));

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize
        $data = InputSanitizer::sanitize($data);

        // Validate tag name
        if (empty(trim($data['name']))) {
            $errors['name'] = 'Tag name cannot be empty or whitespace';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create tag (service will check for duplicates)
        $tag = new Tag(0, $data['name']);
        $result = $this->tagService->createTag($tag);

        $response = new Created($result);
        $response->send();
    }

    /**
     * Update an existing tag by its ID.
     */
    public function updateTag(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            throw new ValidationException(['id' => $error]);
        }

        // Check if tag exists
        $existingTag = $this->tagService->getTagById($id);
        if (!$existingTag) {
            throw new NotFound('', 'Tag', (string)$id);
        }

        $data = json_decode(file_get_contents('php://input'), true);

        // Validate required fields
        $errors = array_merge($errors, Validator::validateRequired($data, ['name']));

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Sanitize
        $data = InputSanitizer::sanitize($data);

        // Validate tag name
        if (empty(trim($data['name']))) {
            $errors['name'] = 'Tag name cannot be empty or whitespace';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Update tag (service will check for duplicates)
        $tag = new Tag($id, $data['name']);
        $result = $this->tagService->updateTag($tag);

        $response = new Ok($result);
        $response->send();
    }

    /**
     * Delete a tag by its ID.
     */
    public function deleteTag(int $id): void
    {
        $errors = [];

        // ID validation
        $error = Validator::validatePositiveInt($id, 'id');
        if ($error) {
            $errors['id'] = $error;
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Check if tag exists
        $tag = $this->tagService->getTagById($id);
        if (!$tag) {
            throw new NotFound('', 'Tag', (string)$id);
        }

        // Delete tag (service will check if in use)
        $this->tagService->deleteTag($tag);

        $response = new Ok(['message' => 'Tag deleted successfully']);
        $response->send();
    }
}