<?php

declare(strict_types=1);

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
        $query = "
            SELECT 
                f.id AS facility_id, 
                f.name AS facility_name, 
                f.location_id, 
                f.creation_date, 
                GROUP_CONCAT(t.name) AS tags
            FROM 
                Facilities f
            LEFT JOIN 
                Facility_Tags ft ON f.id = ft.facility_id
            LEFT JOIN 
                Tags t ON ft.tag_id = t.id
            GROUP BY 
                f.id
        ";

        $stmt = $this->db->executeSelectQuery($query);
        $facilitiesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Map each facility to the Facility model
        $facilities = array_map(function ($facilityData) {
            $tags = $facilityData['tags'] ? explode(',', $facilityData['tags']) : [];
            return new Facility(
                $facilityData['facility_id'],
                $facilityData['facility_name'],
                $facilityData['location_id'],
                $facilityData['creation_date'],
                $tags // Pass the tags as an array
            );
        }, $facilitiesData);

        return $facilities;
    }

    // Get a single facility by ID
    public function getFacilityById(int $id): Facility
    {
        $query = "
            SELECT 
                f.id AS facility_id, 
                f.name AS facility_name, 
                f.location_id, 
                f.creation_date, 
                GROUP_CONCAT(t.name) AS tags
            FROM 
                Facilities f
            LEFT JOIN 
                Facility_Tags ft ON f.id = ft.facility_id
            LEFT JOIN 
                Tags t ON ft.tag_id = t.id
            WHERE 
                f.id = :id
            GROUP BY 
                f.id
        ";

        $stmt = $this->db->executeSelectQuery($query, [':id' => $id]);
        $facilityData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$facilityData) {
            throw new \Exception("Facility with ID $id does not exist.");
        }

        $tags = $facilityData['tags'] ? explode(',', $facilityData['tags']) : [];

        return new Facility(
            $facilityData['facility_id'],
            $facilityData['facility_name'],
            $facilityData['location_id'],
            $facilityData['creation_date'],
            $tags // Pass the tags as an array
        );
    }

    // Create a new facility
    public function createFacility(Facility $facility): string
    {
        $this->checkIfLocationExists($facility->location_id);

        // Insert the facility
        $query = "INSERT INTO Facilities (name, location_id) VALUES (:name, :location_id)";
        $bind = [
            ':name' => $facility->name,
            ':location_id' => $facility->location_id
        ];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            $facilityId = $this->db->getLastInsertedIdAsInt();

            // Process tags
            if (!empty($facility->tags)) {
                $this->processTags($facilityId, $facility->tags);
            }

            return "Facility '{$facility->name}' successfully created with ID $facilityId.";
        }

        throw new \Exception("Failed to create the facility.");
    }

    // Update an existing facility
    public function updateFacility(Facility $facility): string
    {
        $this->checkIfFacilityExists($facility->id);

        if (!empty($facility->location_id)) {
            $this->checkIfLocationExists($facility->location_id);
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
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            // Process tags
            if (!empty($facility->tags)) {
                $this->processTags($facility->id, $facility->tags);
            }

            return "Facility with ID {$facility->id} successfully updated.";
        }

        throw new \Exception("Failed to update the facility with ID {$facility->id}.");
    }

    // Delete a facility by ID
    public function deleteFacility(Facility $facility): string
    {
        $this->checkIfFacilityExists($facility->id);

        // Delete the facility
        $query = "DELETE FROM Facilities WHERE id = :id";
        $bind = [':id' => $facility->id];
        $result = $this->db->executeQuery($query, $bind);

        if ($result) {
            return "Facility with ID {$facility->id} successfully deleted.";
        }

        throw new \Exception("Failed to delete the facility with ID {$facility->id}.");
    }

    // Helper method to check if a location exists
    private function checkIfLocationExists(int $locationId): void
    {
        $query = "SELECT COUNT(*) FROM Locations WHERE id = :location_id";
        $stmt = $this->db->executeSelectQuery($query, [':location_id' => $locationId]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            throw new \Exception("The specified location_id does not exist.");
        }
    }

    // Helper method to check if a facility exists
    private function checkIfFacilityExists(int $facilityId): void
    {
        $query = "SELECT COUNT(*) FROM Facilities WHERE id = :id";
        $stmt = $this->db->executeSelectQuery($query, [':id' => $facilityId]);
        $exists = $stmt->fetchColumn();

        if (!$exists) {
            throw new \Exception("The specified facility does not exist.");
        }
    }

    // Helper method to process tags
    private function processTags(int $facilityId, array $tags): void
    {
        // Delete existing tags for the facility
        $deleteTagsQuery = "DELETE FROM Facility_Tags WHERE facility_id = :facility_id";
        $this->db->executeQuery($deleteTagsQuery, [':facility_id' => $facilityId]);

        // Insert new tags
        foreach ($tags as $tagId) {
            $this->linkTagToFacility($facilityId, $tagId);
        }
    }

    // Helper method to link a tag to a facility
    private function linkTagToFacility(int $facilityId, int $tagId): void
    {
        // Check if the tag exists
        $tagQuery = "SELECT COUNT(*) FROM Tags WHERE id = :id";
        $tagStmt = $this->db->executeSelectQuery($tagQuery, [':id' => $tagId]);
        $tagExists = $tagStmt->fetchColumn();

        if (!$tagExists) {
            throw new \Exception("Tag with ID $tagId does not exist.");
        }

        // Insert into facility_tags
        $facilityTagQuery = "INSERT INTO Facility_Tags (facility_id, tag_id) VALUES (:facility_id, :tag_id)";
        $this->db->executeQuery($facilityTagQuery, [
            ':facility_id' => $facilityId,
            ':tag_id' => $tagId
        ]);
    }
}
