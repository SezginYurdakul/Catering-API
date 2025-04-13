<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CustomDb;
use PDO;

class FacilityRepository
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    /**
     * Get facilities with pagination and optional filters.
     *
     * @param string $whereClause
     * @param array $bind
     * @param int $perPage
     * @param int $offset
     * @return array
     * @throws \Exception
     */
    public function getFacilities(string $whereClause, array $bind, int $perPage, int $offset): array
    {
        try {
            $query = "
                SELECT 
                    f.id AS facility_id,
                    f.name AS facility_name,
                    f.location_id,
                    f.creation_date
                FROM 
                    Facilities f
                LEFT JOIN 
                    Locations l ON f.location_id = l.id
                LEFT JOIN 
                    Facility_Tags ft ON f.id = ft.facility_id
                LEFT JOIN 
                    Tags t ON ft.tag_id = t.id
                WHERE 
                    $whereClause
                LIMIT $perPage OFFSET $offset
            ";

            $stmt = $this->db->executeSelectQuery($query, $bind);
            $facilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $facilityData ?: [];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch facilities: " . $e->getMessage());
        }
    }

    /**
     * Get a facility by its ID.
     *
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getFacilityById(int $id): ?array
    {
        try {
            $query = "
                SELECT 
                    f.id AS facility_id, 
                    f.name AS facility_name, 
                    f.location_id, 
                    f.creation_date
                FROM 
                    Facilities f
                WHERE 
                    f.id = :id
            ";

            $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
            $facilityData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $facilityData ?: null;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch facility with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Create a new facility.
     *
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function createFacility(array $data): int
    {
        try {
            $query = "INSERT INTO Facilities (name, location_id) VALUES (:name, :location_id)";
            $this->db->executeQuery($query, $data);

            $facilityId = $this->db->getLastInsertedIdAsInt();
            if (!$facilityId) {
                throw new \Exception("Failed to retrieve the ID of the newly created facility.");
            }

            return $facilityId;
        } catch (\Exception $e) {
            throw new \Exception("Failed to create facility: " . $e->getMessage());
        }
    }

    /**
     * Update an existing facility.
     *
     * @param int $id
     * @param array $fields
     * @return int
     * @throws \Exception
     */
    public function updateFacility(int $id, array $fields): int
    {
        try {
            $setClause = [];
            $bind = [':id' => $id];

            foreach ($fields as $key => $value) {
                $setClause[] = "$key = :$key";
                $bind[":$key"] = $value;
            }

            $query = "UPDATE Facilities SET " . implode(", ", $setClause) . " WHERE id = :id";
            $this->db->executeQuery($query, $bind);

            return $id;
        } catch (\Exception $e) {
            throw new \Exception("Failed to update facility with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Delete a facility by its ID.
     *
     * @param int $id
     * @return bool
     * @throws \Exception
     */
    public function deleteFacility(int $id): bool
    {
        try {
            $query = "DELETE FROM Facilities WHERE id = :id";
            return $this->db->executeQuery($query, [':id' => $id]);
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete facility with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Get the total count of facilities.
     *
     * @return int
     * @throws \Exception
     */
    public function getTotalFacilitiesCount(): int
    {
        try {
            $query = "SELECT COUNT(*) AS total FROM Facilities";
            $stmt = $this->db->executeSelectQuery($query);
            return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch the total count of facilities: " . $e->getMessage());
        }
    }

    /**
     * Add tags to a facility.
     *
     * @param int $facilityId
     * @param array $tagIds
     * @return void
     * @throws \Exception
     */
    public function addTagsToFacility(int $facilityId, array $tagIds): void
    {
        try {
            // Delete existing tags if tagIds is not empty
            if (!empty($tagIds)) {
                $deleteQuery = "DELETE FROM Facility_Tags WHERE facility_id = :facility_id";
                $this->db->executeQuery($deleteQuery, [':facility_id' => $facilityId]);
            }

            // Insert new tag associations
            $insertQuery = "INSERT INTO Facility_Tags (facility_id, tag_id) VALUES (:facility_id, :tag_id)";
            foreach ($tagIds as $tagId) {
                $this->db->executeQuery($insertQuery, [
                    ':facility_id' => $facilityId,
                    ':tag_id' => $tagId
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception("Failed to update tags for facility with ID $facilityId: " . $e->getMessage());
        }
    }
}