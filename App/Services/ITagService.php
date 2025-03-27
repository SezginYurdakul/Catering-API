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
     *@param int $page
     *@param int $perPage
     * @return array 
     */
    public function getAllTags(int $page, int $perPage): array;

    /**
     * Get a tag by its ID.
     * Retrieves a specific tag by its unique ID.
     *
     * @param int $id
     * @return Tag T
     */
    public function getTagById(int $id): Tag;

    /**
     * Create a new tag.
     * Adds a new tag to the database.
     *
     * @param Tag $tag 
     * @return string
     */
    public function createTag(Tag $tag): string;

    /**
     * Update an existing tag.
     * Updates the details of an existing tag in the database.
     *
     * @param Tag $tag
     * @return string
     */
    public function updateTag(Tag $tag): string;

    /**
     * Delete a tag.
     * Removes a tag from the database.
     *
     * @param Tag $tag 
     * @return string 
     */
    public function deleteTag(Tag $tag): string;

    /**
     * Check if a tag is used by any facilities.
     * Determines if a tag is associated with any facilities in the database.
     *
     * @param int $id 
     * @return bool 
     */
    public function isTagUsedByFacilities(int $id): void;

    /**
     * Get the total count of tags.
     *
     * @return int
     */
    public function getTotalTagsCount(): int;
}
