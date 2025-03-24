<?php

namespace App\Services;

use App\Models\Location;
use App\Services\CustomDb;
use PDO;

class LocationService implements ILocationService
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    public function getAllLocations(): array
    {
        $query = "SELECT * FROM Locations";
        $stmt = $this->db->executeSelectQuery($query);
        $locationsData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function ($locationData) {
            return new Location(
                $locationData['id'],
                $locationData['city'],
                $locationData['address'],
                $locationData['zip_code'],
                $locationData['country_code'],
                $locationData['phone_number']
            );
        }, $locationsData);
    }

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

    public function createLocation(Location $location): string
    {
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

    public function updateLocation(Location $location): string
    {
        // Map the Location object to an array of fields and bindings
        $mappedData = $this->mapLocationToUpdateFields($location);
    
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
}