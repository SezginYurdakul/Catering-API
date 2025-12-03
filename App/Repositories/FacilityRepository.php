<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CustomDb;
use App\Helpers\Logger;
use PDO;

class FacilityRepository
{
    private $db;
    private ?Logger $logger;

    public function __construct(CustomDb $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
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
                GROUP BY f.id, f.name, f.location_id, f.creation_date
                LIMIT $perPage OFFSET $offset
            ";

            $stmt = $this->db->executeSelectQuery($query, $bind);
            $facilityData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $facilityData ?: [];
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getFacilities', $e->getMessage());
            }
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
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getFacilityById', $e->getMessage());
            }
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
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('INSERT', 'createFacility', $e->getMessage());
            }
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
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('UPDATE', 'updateFacility', $e->getMessage());
            }
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
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('DELETE', 'deleteFacility', $e->getMessage());
            }
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
     * Get filtered facilities count based on the same criteria used in getFacilities.
     *
     * @param string $whereClause
     * @param array $bind
     * @return int
     * @throws \Exception
     */
    public function getFilteredFacilitiesCount(string $whereClause, array $bind): int
    {
        try {
            $query = "
                SELECT COUNT(DISTINCT f.id) as total_count
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
            ";

            $stmt = $this->db->executeSelectQuery($query, $bind);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) $result['total_count'];
        } catch (\Exception $e) {
            throw new \Exception("Failed to count filtered facilities: " . $e->getMessage());
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

    /**
     * Remove specific tags from a facility.
     *
     * @param int $facilityId
     * @param array $tagIds
     * @return void
     * @throws \Exception
     */
    public function removeTagsFromFacility(int $facilityId, array $tagIds): void
    {
        try {
            if (empty($tagIds)) {
                return; // Nothing to remove
            }

            // Create placeholders for the IN clause
            $placeholders = implode(',', array_fill(0, count($tagIds), '?'));

            $deleteQuery = "DELETE FROM Facility_Tags WHERE facility_id = ? AND tag_id IN ($placeholders)";

            // Prepare parameters: facility_id first, then all tag_ids
            $params = [$facilityId];
            $params = array_merge($params, $tagIds);

            $this->db->executeQuery($deleteQuery, $params);
        } catch (\Exception $e) {
            throw new \Exception("Failed to remove tags from facility with ID $facilityId: " . $e->getMessage());
        }
    }

    /**
     * Get facilities for a specific location.
     *
     * @param int $locationId
     * @return array
     * @throws \Exception
     */
    public function getFacilitiesByLocation(int $locationId): array
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
                    f.location_id = :location_id
                ORDER BY f.name
            ";

            $stmt = $this->db->executeSelectQuery($query, [':location_id' => $locationId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get facilities by location: " . $e->getMessage());
        }
    }

    /**
     * Get facilities for a specific tag.
     *
     * @param int $tagId
     * @return array
     * @throws \Exception
     */
    public function getFacilitiesByTag(int $tagId): array
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
                INNER JOIN 
                    Facility_Tags ft ON f.id = ft.facility_id
                WHERE 
                    ft.tag_id = :tag_id
                ORDER BY f.name
            ";

            $stmt = $this->db->executeSelectQuery($query, [':tag_id' => $tagId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get facilities by tag: " . $e->getMessage());
        }
    }

    /**
     * Get employees by facility ID with optional pagination.
     *
     * @param int $facilityId
     * @param int|null $perPage
     * @param int|null $offset
     * @return array
     * @throws \Exception
     */
    public function getEmployeesByFacilityId(int $facilityId, ?int $perPage = null, ?int $offset = null): array
    {
        try {
            $query = "
                SELECT
                    e.id,
                    e.name,
                    e.address,
                    e.phone,
                    e.email,
                    e.created_at
                FROM
                    Employees e
                INNER JOIN
                    Employee_Facility ef ON e.id = ef.employee_id
                WHERE
                    ef.facility_id = :facility_id
                ORDER BY e.name
            ";

            // Add pagination if provided
            if ($perPage !== null && $offset !== null) {
                $query .= " LIMIT $perPage OFFSET $offset";
            }

            $stmt = $this->db->executeSelectQuery($query, [':facility_id' => $facilityId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getEmployeesByFacilityId', $e->getMessage());
            }
            throw new \Exception("Failed to get employees by facility ID: " . $e->getMessage());
        }
    }

    /**
     * Get count of employees by facility ID.
     *
     * @param int $facilityId
     * @return int
     * @throws \Exception
     */
    public function getEmployeeCountByFacilityId(int $facilityId): int
    {
        try {
            $query = "
                SELECT COUNT(DISTINCT e.id) as count
                FROM Employees e
                INNER JOIN Employee_Facility ef ON e.id = ef.employee_id
                WHERE ef.facility_id = :facility_id
            ";

            $stmt = $this->db->executeSelectQuery($query, [':facility_id' => $facilityId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int) ($result['count'] ?? 0);
        } catch (\Exception $e) {
            if ($this->logger) {
                $this->logger->logDatabaseError('SELECT', 'getEmployeeCountByFacilityId', $e->getMessage());
            }
            throw new \Exception("Failed to fetch employee count for facility: " . $e->getMessage());
        }
    }
}
