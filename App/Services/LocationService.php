<?php

namespace App\Services;

use App\Models\Location;
use App\Services\CustomDb;
use PDO;
use App\Helpers\InputSanitizer;
use App\Helpers\PaginationHelper;

class LocationService implements ILocationService
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    /**
     * Get all locations
     * @param int $page
     * @param int $perPage
     * @return array{locations: Location[], pagination: array}
     */
    public function getAllLocations(int $page = 1, int $perPage = 10): array
    {
        $offset = ($page - 1) * $perPage;
        $query = "
        SELECT * 
        FROM Locations
        LIMIT $perPage OFFSET $offset
    ";

        $stmt = $this->db->executeSelectQuery($query);
        $locationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $countQuery = "SELECT COUNT(*) AS total FROM Locations";
        $countStmt = $this->db->executeSelectQuery($countQuery);
        $totalItems = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        $locations = array_map(function ($locationData) {
            return new Location(
                $locationData['id'],
                $locationData['city'],
                $locationData['address'],
                $locationData['zip_code'],
                $locationData['country_code'],
                $locationData['phone_number']
            );
        }, $locationsData);

        $pagination = PaginationHelper::paginate($totalItems, $page, $perPage);

        return [
            'locations' => $locations,
            'pagination' => $pagination
        ];
    }

    /**
     * Get one location by its ID
     * @param int $id
     * @throws \Exception
     * @return Location
     */
    public function getLocationById(int $id): Location
    {
        $query = "SELECT * FROM Locations WHERE id = :id";
        $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
        $locationData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$locationData) {
            throw new \Exception("Location with ID $id does not exist.");
        }

        return new Location(
            $locationData['id'],
            $locationData['city'],
            $locationData['address'],
            $locationData['zip_code'],
            $locationData['country_code'],
            $locationData['phone_number']
        );
    }

    /**
     * Create a new location
     * @param Location $location
     * @throws \Exception
     * @return string
     */
    public function createLocation(Location $location): string
    {
        // Sanitize client data
        $sanitizedData = InputSanitizer::sanitize([
            'city' => $location->city,
            'address' => $location->address,
            'zip_code' => $location->zip_code,
            'country_code' => $location->country_code,
            'phone_number' => $location->phone_number
        ]);

        $query = "INSERT INTO Locations (city, address, zip_code, country_code, phone_number) 
                  VALUES (:city, :address, :zip_code, :country_code, :phone_number)";
        $bind = [
            ':city' => $location->city,
            ':address' => $location->address,
            ':zip_code' => $location->zip_code,
            ':country_code' => $location->country_code,
            ':phone_number' => $location->phone_number
        ];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Location '{$location->city}' successfully created.";
        }

        throw new \Exception("Failed to create the location.");
    }

    /**
     * Update an existing location
     * @param Location $location
     * @throws \Exception
     * @return string
     */
    public function updateLocation(Location $location): string
    {
        // Sanitize client data
        $sanitizedData = InputSanitizer::sanitize([
            'city' => $location->city,
            'address' => $location->address,
            'zip_code' => $location->zip_code,
            'country_code' => $location->country_code,
            'phone_number' => $location->phone_number
        ]);

        // Map the sanitized data to an array of fields and bindings
        $locationObject = new Location(
            $location->id,
            $sanitizedData['city'] ?? null,
            $sanitizedData['address'] ?? null,
            $sanitizedData['zip_code'] ?? null,
            $sanitizedData['country_code'] ?? null,
            $sanitizedData['phone_number'] ?? null
        );

        $mappedData = $this->mapLocationToUpdateFields($locationObject);

        if (empty($mappedData['fields'])) {
            throw new \Exception("No valid fields provided for update.");
        }

        $query = "UPDATE Locations SET " . implode(", ", $mappedData['fields']) . " WHERE id = :id";
        $mappedData['bind'][':id'] = $location->id;

        // Execute the query
        $result = $this->db->executeQuery($query, $mappedData['bind']);

        if ($result) {
            return "Location with ID {$location->id} successfully updated.";
        }

        throw new \Exception("Failed to update the location with ID {$location->id}.");
    }

    /**
     * Delete an existing location
     * @param Location $location
     * @throws \Exception
     * @return string
     */
    public function deleteLocation(Location $location): string
    {
        $query = "DELETE FROM Locations WHERE id = :id";
        $bind = [':id' => $location->id];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Location with ID {$location->id} successfully deleted.";
        }

        throw new \Exception("Failed to delete the location with ID {$location->id}.");
    }

    /**
     * Map the location object to an array of fields and bindings for update
     * @param Location $location
     * @return array
     */
    private function mapLocationToUpdateFields(Location $location): array
    {
        $fields = [];
        $bind = [];

        // Get all properties of the Location model
        foreach (get_object_vars($location) as $property => $value) {
            // Skip null or empty values
            if (!empty($value) && $property !== 'id') {
                $fields[] = "$property = :$property";
                $bind[":$property"] = $value;
            }
        }

        return ['fields' => $fields, 'bind' => $bind];
    }


    /**
     * Check if a location is used by any facilities
     * @param int $locationId
     * @return bool
     */
    public function isLocationUsedByFacilities(int $locationId): bool
    {
        $query = "SELECT COUNT(*) FROM Facilities WHERE location_id = :location_id";
        $stmt = $this->db->executeSelectQuery($query, [':location_id' => $locationId]);
        $count = $stmt->fetchColumn();

        return $count > 0;
    }
}
