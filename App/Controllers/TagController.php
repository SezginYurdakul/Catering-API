<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ITagService;
use App\Models\Tag;
use App\Plugins\Di\Factory;
use App\Plugins\Http\Response\Ok;
use App\Plugins\Http\Response\Created;
use App\Plugins\Http\Response\NoContent;
use App\Plugins\Http\Response\NotFound;
use App\Plugins\Http\Response\BadRequest;
use App\Plugins\Http\Response\InternalServerError;
use App\Middleware\AuthMiddleware;
use App\Helpers\InputSanitizer;

class TagController
{
    private ITagService $tagService;

    /**
     * Constructor to initialize the TagService from the DI container.
     * Authenticates the user with the AuthMiddleware.
     */
    public function __construct()
    {
        $this->tagService = Factory::getDi()->getShared('tagService');
        $authMiddleware = new AuthMiddleware();
        $authMiddleware->handle();
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
                $errorResponse = new BadRequest([
                    "error" => "Invalid pagination parameters. 'page' and 'per_page' must be positive integers."
                ]);
                $errorResponse->send();
                return;
            }
            // Fetch all tags with pagination
            $tags = $this->tagService->getAllTags($page, $perPage);

            // Check if the requested page exceeds the total number of pages
            $totalItems = $tags['pagination']['total_items'];
            $totalPages = (int) ceil($totalItems / $perPage);
            if ($totalPages>0 && $page > $totalPages) {
                $errorResponse = new BadRequest([
                    "error" => "The requested page ($page) exceeds the total number of pages ($totalPages)."
                ]);
                $errorResponse->send();
                return;
            }


            $response = new Ok($tags); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
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
                $errorResponse = new BadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                $errorResponse = new NotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok(['tag' => $tag]); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
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
                $errorResponse = new BadRequest(["error" => "Tag name is required.Failed to create the tag"]); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $tag = new Tag(0, $sanitizedData['name']);
            $result = $this->tagService->createTag($tag);

            $response = new Created($result); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage() . "No change was made"]); // 500 Internal Server Error
            $errorResponse->send();
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
                $errorResponse = new BadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }
            $data = json_decode(file_get_contents('php://input'), true);
            // Sanitize client data
            $sanitizedData = InputSanitizer::sanitize([
                'name' => $data['name']
            ]);

            if (empty($sanitizedData['name'])) {
                $errorResponse = new BadRequest(["error" => "Tag name is required.Failed to update the tag"]); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $tag = new Tag($id, $sanitizedData['name']);
            $result = $this->tagService->updateTag($tag);

            if (!$result) {
                $errorResponse = new NotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($result); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
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
                $errorResponse = new BadRequest(["error" => "Invalid tag ID. It must be a positive integer."]);
                $errorResponse->send();
                return;
            }

            // Fetch the tag to check if it exists
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                $errorResponse = new NotFound(["error" => "Tag with ID $id not found."]); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $result = $this->tagService->deleteTag($tag);

            $response = new NoContent(); // 204 No Content response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError(["error" => $e->getMessage()]); // 500 Internal Server Error
            $errorResponse->send();
        }
    }
}
