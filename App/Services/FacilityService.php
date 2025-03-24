<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Location;
use App\Models\Tag;
use App\Services\CustomDb;
use PDO;

class FacilityService implements IFacilityService
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    // Get all facilities
    public function getAllFacilities(): array
    {
        $query = "SELECT * FROM Facilities";
        $stmt = $this->db->executeSelectQuery($query);
        $facilitiesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map each facility to the Facility model
        $facilities = array_map(function ($facilityData) {
            return new Facility(
                $facilityData['id'],
                $facilityData['name'],
                $facilityData['location_id'],
                $facilityData['creation_date']
            );
        }, $facilitiesData);

        return $facilities;
    }

    // Get a single facility by ID
    public function getFacilityById(int $id): Facility
    {
        $query = "SELECT * FROM Facilities WHERE id = :id";
        $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
        $facilityData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$facilityData) {
            throw new \Exception("Facility with ID $id does not exist.");
        }

        return new Facility(
            $facilityData['id'],
            $facilityData['name'],
            $facilityData['location_id'],
            $facilityData['creation_date']
        );
    }

    // Create a new facility
    public function createFacility(Facility $facility): string
    {
        // Check if the location exists
        $locationQuery = "SELECT COUNT(*) FROM Locations WHERE id = :location_id";
        $locationStmt = $this->db->executeSelectQuery($locationQuery, [':location_id' => $facility->location_id]);
        $locationExists = $locationStmt->fetchColumn();
    
        if (!$locationExists) {
            throw new \Exception("The specified location_id does not exist.");
        }
    
        // Proceed to create the facility
        $query = "INSERT INTO Facilities (name, location_id) VALUES (:name, :location_id)";
        $bind = [
            ':name' => $facility->name,
            ':location_id' => $facility->location_id
        ];
        $result = $this->db->executeQuery($query, $bind);
    
        if ($result) {
            return "Facility '{$facility->name}' successfully created.";
        }
    
        throw new \Exception("Failed to create the facility.");
    }

    // Update an existing facility
    public function updateFacility(Facility $facility): string
    {
        // Check if the facility exists
        $facilityQuery = "SELECT COUNT(*) FROM Facilities WHERE id = :id";
        $facilityStmt = $this->db->executeSelectQuery($facilityQuery, [':id' => $facility->id]);
        $facilityExists = $facilityStmt->fetchColumn();
    
        if (!$facilityExists) {
            throw new \Exception("The specified facility does not exist.");
        }
    
        // Check if the location exists (if location_id is provided)
        if ($facility->location_id !== null) {
            $locationQuery = "SELECT COUNT(*) FROM Locations WHERE id = :location_id";
            $locationStmt = $this->db->executeSelectQuery($locationQuery, [':location_id' => $facility->location_id]);
            $locationExists = $locationStmt->fetchColumn();
    
            if (!$locationExists) {
                throw new \Exception("The specified location_id does not exist.");
            }
        }
    
        // Build the dynamic update query
        $fields = [];
        $bind = [':id' => $facility->id];
    
        if (!empty($facility->name)) {
            $fields[] = "name = :name";
            $bind[':name'] = $facility->name;
        }
    
        if (!empty($facility->location_id)) {
            $fields[] = "location_id = :location_id";
            $bind[':location_id'] = $facility->location_id;
        }
    
        if (empty($fields)) {
            throw new \Exception("No valid fields provided for update.");
        }
    
        $query = "UPDATE Facilities SET " . implode(", ", $fields) . " WHERE id = :id";
    
        // Execute the query
        $result = $this->db->executeQuery($query, $bind);
    
        if ($result) {
            return "Facility with ID {$facility->id} successfully updated.";
        }
    
        throw new \Exception("Failed to update the facility with ID {$facility->id}.");
    }

    // Delete a facility by ID
    public function deleteFacility(Facility $facility): string
    {
        // Check if the facility exists before attempting to delete
        $facilityQuery = "SELECT COUNT(*) FROM Facilities WHERE id = :id";
        $facilityStmt = $this->db->executeSelectQuery($facilityQuery, [':id' => $facility->id]);
        $facilityExists = $facilityStmt->fetchColumn();
    
        if (!$facilityExists) {
            throw new \Exception("The facility with ID {$facility->id} does not exist.");
        }
    
        // Proceed to delete the facility
        $query = "DELETE FROM Facilities WHERE id = :id";
        $bind = [':id' => $facility->id];
        $result = $this->db->executeQuery($query, $bind);
    
        if ($result) {
            return "Facility with ID {$facility->id} successfully deleted.";
        }
    
        throw new \Exception("Failed to delete the facility with ID {$facility->id}.");
    }
}
