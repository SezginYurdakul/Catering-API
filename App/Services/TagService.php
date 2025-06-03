<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use App\Repositories\TagRepository;
use App\Helpers\PaginationHelper;

class TagService implements ITagService
{
    private $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get all tags with pagination.
     *
     * @param int $page The current page number.
     * @param int $perPage The number of items per page.
     * @return array An array containing the tags and pagination information.
     * @throws \Exception
     */
    public function getAllTags(int $page, int $perPage): array
    {
        try {
            $offset = ($page - 1) * $perPage;
            $tagsData = $this->tagRepository->getAllTags($perPage, $offset);
            $totalItems = $this->tagRepository->getTotalTagsCount();

            $tags = array_map(function ($tagData) {
                return new Tag($tagData['id'], $tagData['name']);
            }, $tagsData);

            $pagination = PaginationHelper::paginate($totalItems, $page, $perPage);

            return [
                'tags' => $tags,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve tags: " . $e->getMessage());
        }
    }

    /**
     * Get a tag by its ID.
     *
     * @param int $id The ID of the tag.
     * @return Tag The tag object.
     * @throws \Exception
     */
    public function getTagById(int $id): Tag
    {
        try {
            $tagData = $this->tagRepository->getTagById($id);

            if (!$tagData) {
                throw new \Exception("Tag with ID $id does not exist.");
            }

            return new Tag($tagData['id'], $tagData['name']);
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve tag with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Get tags by facility ID.
     *
     * @param int $facilityId The ID of the facility.
     * @return array
     * @throws \Exception
     */
    public function getTagsByFacilityId(int $facilityId): array
    {
        try {
            $tagsData = $this->tagRepository->getTagsByFacilityId($facilityId);

            return array_map(function ($tagData) {
                return new Tag($tagData['id'], $tagData['name']);
            }, $tagsData);
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve tags for facility with ID $facilityId: " . $e->getMessage());
        }
    }

    /**
     * Create a new tag.
     *
     * @param \App\Models\Tag $tag
     * @return array{message: string, tag: Tag}
     * @throws \Exception
     */
    public function createTag(Tag $tag): array
    {
        try {
            if (!$this->tagRepository->isTagNameUnique($tag->name)) {
                throw new \Exception("Tag name '{$tag->name}' already exists. It should be unique.");
            }

            $tagId = $this->tagRepository->createTag($tag->name);
            $tagObject = new Tag($tagId, $tag->name);

            return [
                "message" => "Tag '{$tag->name}' successfully created.",
                "tag" => $tagObject
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create tag '{$tag->name}': " . $e->getMessage());
        }
    }

    /**
     * Update an existing tag.
     *
     * @param \App\Models\Tag $tag
     * @return array{message: string, tag: Tag}
     * @throws \Exception
     */
    public function updateTag(Tag $tag): array
    {
        try {
            if (!$this->tagRepository->isTagNameUnique($tag->name)) {
                throw new \Exception("Tag name '{$tag->name}' already exists. It should be unique.");
            }

            $this->tagRepository->updateTag($tag->id, $tag->name);

            return [
                "message" => "Tag '{$tag->name}' successfully updated.",
                "tag" => $tag
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to update tag with ID {$tag->id}: " . $e->getMessage());
        }
    }

    /**
     * Delete a tag.
     *
     * @param \App\Models\Tag $tag
     * @return string
     * @throws \Exception
     */
    public function deleteTag(Tag $tag): string
    {
        try {
            if ($this->tagRepository->isTagUsedByFacilities($tag->id)) {
                throw new \Exception("Tag with ID {$tag->id} is used by one or more facilities.");
            }

            $this->tagRepository->deleteTag($tag->id);

            return "Tag with ID {$tag->id} successfully deleted.";
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete tag with ID {$tag->id}: " . $e->getMessage());
        }
    }

    /**
     * Create tag object
     * @param int $id
     * @param string $name
     * @return Tag
     */
    public function createTagObject(int $id, string $name): Tag
    {
        return new Tag($id, $name);
    }
}