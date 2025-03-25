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

class TagController
{
    private ITagService $tagService;

    /**
     * Constructor to initialize the TagService from the DI container.
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
            $tags = $this->tagService->getAllTags();
            $response = new Ok($tags); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            $tag = $this->tagService->getTagById($id);

            if (!$tag) {
                $errorResponse = new NotFound("Tag with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($tag); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name'])) {
                $errorResponse = new BadRequest("Tag name is required."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $tag = new Tag(0, $data['name']);
            $result = $this->tagService->createTag($tag);
            $response = new Created($result); // 201 Created response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            $data = json_decode(file_get_contents('php://input'), true);

            if (empty($data['name'])) {
                $errorResponse = new BadRequest("Tag name is required."); // 400 Bad Request
                $errorResponse->send();
                return;
            }

            $tag = new Tag($id, $data['name']);
            $result = $this->tagService->updateTag($tag);

            if (!$result) {
                $errorResponse = new NotFound("Tag with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $response = new Ok($result); // 200 OK response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
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
            $tag = $this->tagService->getTagById($id);

            // Check if the tag is used by any facilities
            $this->tagService->isTagUsedByFacilities($id);
            if (!$tag) {
                $errorResponse = new NotFound("Tag with ID $id not found."); // 404 Not Found
                $errorResponse->send();
                return;
            }

            $result = $this->tagService->deleteTag($tag);

            $response = new NoContent(); // 204 No Content response
            $response->send();
        } catch (\Exception $e) {
            $errorResponse = new InternalServerError($e->getMessage()); // 500 Internal Server Error
            $errorResponse->send();
        }
    }
}
