<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ITagService;
use App\Plugins\Di\Factory;
use App\Helpers\InputSanitizer;

class TagController extends RespondController
{
    private ITagService $tagService;

    /**
     * Constructor to initialize the TagService from the DI container.
     * Authenticates the user with the AuthMiddleware.
     */
    public function __construct()
    {
        $this->tagService = Factory::getDi()->getShared('tagService');
    }

    /**
     * Get all tags.
     * Sends a 200 OK response with the list of tags.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @return void
     */
    public function getAllTags(): void
    {
        try {
            // Get and sanitize pagination parameters
            $page = isset($_GET['page']) ? InputSanitizer::sanitizeId($_GET['page']) : 1;
            $perPage = isset($_GET['per_page']) ? InputSanitizer::sanitizeId($_GET['per_page']) : 10;

            // Validate pagination parameters
            if ($page === null || $perPage === null || $page <= 0 || $perPage <= 0) {
                $this->respondBadRequest([
                    "error" => "Invalid pagination parameters. 'page' and 'per_page' must be positive integers."
                ]);
                return;
            }
            // Fetch all tags with pagination
            $tags = $this->tagService->getAllTags($page, $perPage);

            // Check if the requested page exceeds the total number of pages
            $totalItems = $tags['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages>0 && $page > $totalPages) {
                $this->respondBadRequest([
                    "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
                return;
            }


            $this->respondOk($tags); // 200 OK response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
        }
    }

    /**
     * Get a specific tag by its ID.
     * Sends a 200 OK response if the tag is found.
     * Sends a 404 Not Found response if the tag does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function getTagById(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $this->respondBadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                return;
            }
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                $this->respondNotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                return;
            }

            $this->respondOk(['tag' => $tag]); // 200 OK response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
        }
    }

    /**
     * Create a new tag.
     * Sends a 201 Created response with the created tag.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @return void
     */
    public function createTag(): void
    {
        try {

            $tagData = json_decode(file_get_contents('php://input'), true);

            // Sanitize client data
            $sanitizedData = InputSanitizer::sanitize([
                'name' => $tagData['name']
            ]);

            // Validate tag name
            if (empty(trim($sanitizedData['name']))) {
                throw new \Exception("Tag name cannot be empty or whitespace.");
            }

            if (empty($sanitizedData['name'])) {
                $this->respondBadRequest(["error" => "Tag name is required.Failed to create the tag"]); // 400 Bad Request
                return;
            }

            $tag = $this->tagService->createTagObject(0, $sanitizedData['name']);
            $result = $this->tagService->createTag($tag);

            $this->respondCreated($result); // 201 Created response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage() . "No change was made"]); // 500 Internal Server Error
        }
    }

    /**
     * Update an existing tag by its ID.
     * Sends a 200 OK response with the updated tag.
     * Sends a 400 Bad Request response if required fields are missing.
     * Sends a 404 Not Found response if the tag does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function updateTag(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $this->respondBadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                return;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            // Sanitize client data
            $sanitizedData = InputSanitizer::sanitize([
                'name' => $data['name']
            ]);

            if (empty($sanitizedData['name'])) {
                $this->respondBadRequest(["error" => "Tag name is required.Failed to update the tag"]); // 400 Bad Request
                return;
            }

            $tag = $this->tagService->createTagObject($id, $sanitizedData['name']);
            $result = $this->tagService->updateTag($tag);

            if (!$result) {
                $this->respondNotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                return;
            }

            $this->respondOk($result); // 200 OK response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
        }
    }

    /**
     * Delete a tag by its ID.
     * Sends a 204 No Content response if the tag is successfully deleted.
     * Sends a 404 Not Found response if the tag does not exist.
     * Sends a 500 Internal Server Error response in case of an exception.
     *
     * @param int $id
     * @return void
     */
    public function deleteTag(int $id): void
    {
        try {
            // Sanitize the ID
            $id = InputSanitizer::sanitizeId($id);
            if ($id === null) {
                $this->respondBadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                return;
            }

            // Fetch the tag to check if it exists
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                $this->respondNotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                return;
            }

            $result = $this->tagService->deleteTag($tag);

            $this->respondNoContent(); // 204 No Content response
        } catch (\Exception $e) {
            $this->respondInternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
        }
    }
}
