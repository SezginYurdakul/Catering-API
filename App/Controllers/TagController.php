<?php

namespace App\Controllers;

use App\Services\ITagService;
use App\Models\Tag;
use App\Plugins\Di\Factory;

class TagController
{
    private $tagService;

    public function __construct()
    {
        $this->tagService = Factory::getDi()->getShared('tagService');
    }

    private function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode($data);
        exit;
    }

    private function jsonErrorResponse($message, $statusCode)
    {
        header('Content-Type: application/json', true, $statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }

    public function getAllTags()
    {
        try {
            $tags = $this->tagService->getAllTags();
            return $this->jsonResponse($tags);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 500);
        }
    }

    public function getTagById($id)
    {
        try {
            $tag = $this->tagService->getTagById((int)$id);
            return $this->jsonResponse($tag);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 404);
        }
    }

    public function createTag()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tag = new Tag(0, $data['name']);
            $result = $this->tagService->createTag($tag);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }

    public function updateTag($id)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $tag = new Tag((int)$id, $data['name']);
            $result = $this->tagService->updateTag($tag);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }

    public function deleteTag($id)
    {
        try {
            $tag = new Tag((int)$id, '');
            $result = $this->tagService->deleteTag($tag);
            return $this->jsonResponse(['message' => $result]);
        } catch (\Exception $e) {
            return $this->jsonErrorResponse($e->getMessage(), 400);
        }
    }
}