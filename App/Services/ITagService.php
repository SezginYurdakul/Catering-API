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
     * @return array An array of Tag objects.
     */
    public function getAllTags(int $page, int $perPage): array;

    /**
     * Get a tag by its ID.
     * Retrieves a specific tag by its unique ID.
     *
     * @param int $id The ID of the tag to retrieve.
     * @return Tag The Tag object corresponding to the given ID.
     */
    public function getTagById(int $id): Tag;

    /**
     * Create a new tag.
     * Adds a new tag to the database.
     *
     * @param Tag $tag The Tag object containing the details of the new tag.
     * @return string A success message indicating the tag was created.
     */
    public function createTag(Tag $tag): string;

    /**
     * Update an existing tag.
     * Updates the details of an existing tag in the database.
     *
     * @param Tag $tag The Tag object containing the updated details.
     * @return string A success message indicating the tag was updated.
     */
    public function updateTag(Tag $tag): string;

    /**
     * Delete a tag.
     * Removes a tag from the database.
     *
     * @param Tag $tag The Tag object representing the tag to delete.
     * @return string A success message indicating the tag was deleted.
     */
    public function deleteTag(Tag $tag): string;

    /**
     * Check if a tag is used by any facilities.
     * Determines if a tag is associated with any facilities in the database.
     *
     * @param int $id The ID of the tag to check.
     * @return bool True if the tag is used by any facilities, false otherwise.
     */
    public function isTagUsedByFacilities(int $id): void;
}
