<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Services\CustomDb;
use App\Models\Location;
use App\Helpers\Logger;
use PDO;

class LocationRepository
{
    private $db;
    private ?Logger $logger;

    public function __construct(CustomDb $db, ?Logger $logger = null)
    {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * Get all locations with pagination.
     *
     * @param int $perPage Number of records per page
     * @param int $offset Offset for pagination
     * @return array
     */
    public function getAllLocations(int $perPage, int $offset): array
    {
        try {
            $query = "
                SELECT * 
                FROM Locations
                LIMIT $perPage OFFSET $offset
            ";

            $stmt = $this->db->executeSelectQuery($query);
            $locationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $locationsData ?: [];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch locations: " . $e->getMessage());
        }
    }

    /**
     * Get a location by its ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getLocationById(int $id): ?array
    {
        try {
            $query = "SELECT * FROM Locations WHERE id = :id";
            $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
            $locationData = $stmt->fetch(PDO::FETCH_ASSOC);

            return $locationData ?: null;
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch location with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Create a location
     *
     * @param array $data
     * @return int 
     */
    public function createLocation(array $data): int
    {
        try {
            $query = "INSERT INTO Locations (city, address, zip_code, country_code, phone_number) 
                      VALUES (:city, :address, :zip_code, :country_code, :phone_number)";
            $this->db->executeQuery($query, $data);

            $locationId = $this->db->getLastInsertedIdAsInt();
            if (!$locationId) {
                throw new \Exception("Failed to retrieve the ID of the newly created location.");
            }

            return $locationId;
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('INSERT', 'createLocation', $e->getMessage());
            }
            throw new \Exception("Failed to create location: " . $e->getMessage());
        }
    }

    /**
     * Update a location.
     *
     * @param int $id
     * @param array $fields
     * @return int
     */
    public function updateLocation(int $id, array $fields): int
    {
        try {
            $setClause = [];
            $bind = [':id' => $id];

            foreach ($fields as $key => $value) {
                $setClause[] = "$key = :$key";
                $bind[":$key"] = $value;
            }

            $query = "UPDATE Locations SET " . implode(", ", $setClause) . " WHERE id = :id";
            $result = $this->db->executeQuery($query, $bind);

            if (!$result) {
                throw new \Exception("Failed to update location with ID $id.");
            }

            return $id;
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('UPDATE', 'updateLocation', $e->getMessage());
            }
            throw new \Exception("Failed to update location with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Delete a location by its ID.
     *
     * @param int $id
     * @return bool
     */
    public function deleteLocation(int $id): bool
    {
        try {
            $query = "DELETE FROM Locations WHERE id = :id";
            return $this->db->executeQuery($query, [':id' => $id]);
        } catch (\Exception $e) {
            // Log critical database error
            if ($this->logger) {
                $this->logger->logDatabaseError('DELETE', 'deleteLocation', $e->getMessage());
            }
            throw new \Exception("Failed to delete location with ID $id: " . $e->getMessage());
        }
    }

    /**
     * Check if a location is used by any facilities.
     *
     * @param int $locationId
     * @return bool
     */
    public function isLocationUsedByFacilities(int $locationId): bool
    {
        try {
            $query = "SELECT COUNT(*) FROM Facilities WHERE location_id = :location_id";
            $stmt = $this->db->executeSelectQuery($query, [':location_id' => $locationId]);
            return (bool) $stmt->fetchColumn();
        } catch (\Exception $e) {
            throw new \Exception("Failed to check if location with ID $locationId is used by facilities: " . $e->getMessage());
        }
    }

    /**
     * Get the total count of locations.
     *
     * @return int
     */
    public function getTotalLocationsCount(): int
    {
        try {
            $query = "SELECT COUNT(*) AS total FROM Locations";
            $stmt = $this->db->executeSelectQuery($query);
            return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (\Exception $e) {
            throw new \Exception("Failed to fetch the total count of locations: " . $e->getMessage());
        }
    }
}
