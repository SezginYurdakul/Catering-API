<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CustomDb;
use App\Helpers\Logger;
use PDO;

class TagRepository
{
    private $db;
    private ?Logger $logger;

    public function __construct(CustomDb $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get all tags with pagination.
     *
     * @param int $perPage 
     * @param int $offset 
     * @return array 
     * @throws \Exception
     */
    public function getAllTags(int $perPage, int $offset): array
    {
        try {
            $query = "
                SELECT * 
                FROM Tags
                LIMIT $perPage OFFSET $offset
            ";

            $stmt = $this->db->executeSelectQuery($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch tags: " . $e->getMessage());
        }
    }

    /**
     * Get the total count of tags.
     *
     * @return int 
     * @throws \Exception
     */
    public function getTotalTagsCount(): int
    {
        try {
            $query = "SELECT COUNT(*) AS total FROM Tags";
            $stmt = $this->db->executeSelectQuery($query);
            return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch the total count of tags: " . $e->getMessage());
        }
    }

    /**
     * Get a tag by its ID.
     *
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getTagById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM Tags WHERE id = :id";
            $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
            $tagData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $tagData ?: null;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch tag with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Get tags associated with a specific facility.
     *
     * @param int $facilityId
     * @return array
     * @throws \Exception
     */
    public function getTagsByFacilityId(int $facilityId): array
    {
        try {
            $query = "
                SELECT t.id, t.name 
                FROM Facility_Tags ft
                LEFT JOIN Tags t ON ft.tag_id = t.id
                WHERE ft.facility_id = :facility_id
            ";

            $stmt = $this->db->executeSelectQuery($query, [':facility_id' => $facilityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch tags for facility with ID $facilityId: " . $e->getMessage());
        }
    }

    /**
     * Create a new tag.
     *
     * @param string $name
     * @return int
     * @throws \Exception
     */
    public function createTag(string $name): int
    {
        try {
            $query = "INSERT INTO Tags (name) VALUES (:name)";
            $this->db->executeQuery($query, [':name' => $name]);

            $tagId = $this->db->getLastInsertedIdAsInt();
            if (!$tagId) {
                throw new \Exception("Failed to retrieve the ID of the newly created tag.");
            }

            return $tagId;
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('INSERT', 'createTag', $e->getMessage());
            }
            throw new \Exception("Failed to create tag: " . $e->getMessage());
        }
    }

    /**
     * Update an existing tag.
     *
     * @param int $id
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    public function updateTag(int $id, string $name): bool
    {
        try {
            $query = "UPDATE Tags SET name = :name WHERE id = :id";
            return $this->db->executeQuery($query, [
                ':name' => $name,
                ':id' => $id
            ]);
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('UPDATE', 'updateTag', $e->getMessage());
            }
            throw new \Exception("Failed to update tag with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Delete a tag by its ID.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteTag(int $id): bool
    {
        try {
            $query = "DELETE FROM Tags WHERE id = :id";
            return $this->db->executeQuery($query, [':id' => $id]);
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('DELETE', 'deleteTag', $e->getMessage());
            }
            throw new \Exception("Failed to delete tag with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Check if a tag is used by any facilities.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function isTagUsedByFacilities(int $id): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM Facility_Tags WHERE tag_id = :id";
            $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
            return (bool) $stmt->fetchColumn();
        } catch (\Exception $e) {
            throw new \Exception("Failed to check if tag with ID $id is used by facilities: " . $e->getMessage());
        }
    }

    /**
     * Check if a tag name is unique.
     *
     * @param string $name
     * @return bool
     * @throws \Exception
     */
    public function isTagNameUnique(string $name): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM Tags WHERE name = :name";
            $stmt = $this->db->executeSelectQuery($query, [':name' => $name]);
            return (int) $stmt->fetchColumn() === 0;
        } catch (\Exception $e) {
            throw new \Exception("Failed to check if tag name '$name' is unique: " . $e->getMessage());
        }
    }
}