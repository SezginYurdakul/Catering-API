<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;
use App\Repositories\TagRepository;
use App\Helpers\PaginationHelper;
use App\Domain\Exceptions\DuplicateResourceException;
use App\Domain\Exceptions\ResourceInUseException;
use App\Domain\Exceptions\DatabaseException;

class TagService implements ITagService
{
    private TagRepository $tagRepository;

    public function __construct(TagRepository $tagRepository)
    {
        $this->tagRepository = $tagRepository;
    }

    /**
     * Get all tags with pagination.
     * 
     * @throws DatabaseException If database operation fails
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
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Tags', $e->getMessage());
        }
    }

    /**
     * Get a tag by its ID.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getTagById(int $id): ?Tag
    {
        try {
            $tagData = $this->tagRepository->getTagById($id);

            if (!$tagData) {
                return null;
            }

            return new Tag($tagData['id'], $tagData['name']);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Tags', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Get tags by facility ID.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getTagsByFacilityId(int $facilityId): array
    {
        try {
            $tagsData = $this->tagRepository->getTagsByFacilityId($facilityId);

            return array_map(function ($tagData) {
                return new Tag($tagData['id'], $tagData['name']);
            }, $tagsData);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Tags', $e->getMessage(), [
                'facility_id' => $facilityId
            ]);
        }
    }

    /**
     * Create a new tag.
     * 
     * @throws DuplicateResourceException If tag name already exists
     * @throws DatabaseException If database operation fails
     */
    public function createTag(Tag $tag): array
    {
        try {
            // Business rule: Tag name must be unique
            if (!$this->tagRepository->isTagNameUnique($tag->name)) {
                throw new DuplicateResourceException('Tag', 'name', $tag->name);
            }

            $tagId = $this->tagRepository->createTag($tag->name);

            if (!$tagId) {
                throw new DatabaseException('INSERT', 'Tags', 'Failed to retrieve tag ID');
            }

            $tagObject = new Tag($tagId, $tag->name);

            return [
                "message" => "Tag '{$tag->name}' successfully created.",
                "tag" => $tagObject
            ];
        } catch (DuplicateResourceException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\PDOException $e) {
            throw new DatabaseException('INSERT', 'Tags', $e->getMessage(), [
                'name' => $tag->name
            ]);
        }
    }

    /**
     * Update an existing tag.
     * 
     * @throws DuplicateResourceException If tag name already exists
     * @throws DatabaseException If database operation fails
     */
    public function updateTag(Tag $tag): array
    {
        try {
            // Business rule: Tag name must be unique
            if (!$this->tagRepository->isTagNameUnique($tag->name)) {
                throw new DuplicateResourceException('Tag', 'name', $tag->name);
            }

            $result = $this->tagRepository->updateTag($tag->id, $tag->name);

            if (!$result) {
                throw new DatabaseException('UPDATE', 'Tags', 'Update operation returned false', [
                    'id' => $tag->id
                ]);
            }

            return [
                "message" => "Tag '{$tag->name}' successfully updated.",
                "tag" => $tag
            ];
        } catch (DuplicateResourceException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\PDOException $e) {
            throw new DatabaseException('UPDATE', 'Tags', $e->getMessage(), [
                'id' => $tag->id
            ]);
        }
    }

    /**
     * Delete a tag.
     * 
     * @throws ResourceInUseException If tag is used by facilities
     * @throws DatabaseException If database operation fails
     */
    public function deleteTag(Tag $tag): string
    {
        try {
            // Business rule: Cannot delete tag if used by facilities
            if ($this->tagRepository->isTagUsedByFacilities($tag->id)) {
                throw new ResourceInUseException(
                    'Tag',
                    $tag->id,
                    'one or more facilities'
                );
            }

            $result = $this->tagRepository->deleteTag($tag->id);

            if (!$result) {
                throw new DatabaseException('DELETE', 'Tags', 'Delete operation returned false', [
                    'id' => $tag->id
                ]);
            }

            return "Tag with ID {$tag->id} successfully deleted.";
        } catch (ResourceInUseException $e) {
            // Re-throw domain exceptions
            throw $e;
        } catch (\PDOException $e) {
            throw new DatabaseException('DELETE', 'Tags', $e->getMessage(), ['id' => $tag->id]);
        }
    }
}