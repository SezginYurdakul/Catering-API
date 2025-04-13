<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tag;

interface ITagService
{
    /**
     * Get all tags.
     * Retrieves a list of all tags.
     *
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getAllTags(int $page, int $perPage): array;

    /**
     * Get a tag by its ID.
     * Retrieves a specific tag by its unique ID.
     *
     * @param int $id
     * @return Tag
     */
    public function getTagById(int $id): Tag;

    /**
     * Get tags by facility ID.
     * Retrieves all tags associated with a specific facility.
     *
     * @param int $facilityId
     * @return array
     */
    public function getTagsByFacilityId(int $facilityId): array;

    /**
     * Create a new tag.
     * Adds a new tag to the database.
     *
     * @param Tag $tag
     * @return array
     */
    public function createTag(Tag $tag): array;

    /**
     * Update an existing tag.
     * Updates the details of an existing tag in the database.
     *
     * @param Tag $tag
     * @return array
     */
    public function updateTag(Tag $tag): array;

    /**
     * Delete a tag.
     * Removes a tag from the database.
     *
     * @param Tag $tag
     * @return string
     */
    public function deleteTag(Tag $tag): string;
}