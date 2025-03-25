<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;
use App\Models\Location;
use App\Models\Tag;
use App\Services\CustomDb;
use PDO;
use App\Helpers\InputSanitizer;
use App\Helpers\PaginationHelper;

class FacilityService implements IFacilityService
{
    private $db;

    public function __construct(CustomDb $db)
    {
        $this->db = $db;
    }

    // Get all facilities
    public function getAllFacilities(int $page = 1, int $perPage = 10): array
    {
        // Calculate offset and limit
        $offset = ($page - 1) * $perPage;
    
        // Query to get facilities with pagination
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
            LIMIT $perPage OFFSET $offset
        ";
    
        // Execute the query
        $stmt = $this->db->executeSelectQuery($query);
        $facilitiesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Query to get the total number of facilities
        $countQuery = "SELECT COUNT(*) AS total FROM Facilities";
        $countStmt = $this->db->executeSelectQuery($countQuery);
        $totalItems = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
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
    
        // Generate pagination metadata
        $pagination = PaginationHelper::paginate($totalItems, $page, $perPage);
    
        return [
            'facilities' => $facilities,
            'pagination' => $pagination
        ];
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
        // Start a transaction
        $this->db->beginTransaction();

        try {
            // Sanitize facility data
            $sanitizedData = InputSanitizer::sanitize([
                'name' => $facility->name,
                'location_id' => $facility->location_id,
            ]);

            $facility->name = $sanitizedData['name'];
            $facility->location_id = (int) $sanitizedData['location_id'];

            // Check if the location exists
            $this->checkIfLocationExists($facility->location_id);

            // Insert the facility
            $query = "INSERT INTO Facilities (name, location_id) VALUES (:name, :location_id)";
            $bind = [
                ':name' => $facility->name,
                ':location_id' => $facility->location_id
            ];
            $result = $this->db->executeQuery($query, $bind);

            if (!$result) {
                throw new \Exception("Failed to create the facility.");
            }

            // Get the last inserted facility ID
            $facilityId = $this->db->getLastInsertedIdAsInt();

            // Update facility tags relation
            if (!empty($facility->tags)) {
                $this->manageFacilityTags($facilityId, $facility->tags);
            }

            // Commit the transaction
            $this->db->commit();

            return "Facility '{$facility->name}' successfully created with ID $facilityId.";
        } catch (\Exception $e) {
            // Rollback the transaction if any error occurs
            $this->db->rollBack();
            throw $e;
        }
    }

    // Update an existing facility
    public function updateFacility(Facility $facility): string
    {
        // Start a transaction
        $this->db->beginTransaction();

        try {
            // Check if the facility exists
            $this->checkIfFacilityExists($facility->id);

            // Sanitize facility data
            $sanitizedData = InputSanitizer::sanitize([
                'name' => $facility->name,
                'location_id' => $facility->location_id,
            ]);

            $facility->name = $sanitizedData['name'];
            $facility->location_id = $sanitizedData['location_id'] ? (int) $sanitizedData['location_id'] : null;

            // Check if the location exists
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

            if (!$result) {
                throw new \Exception("Failed to update the facility with ID {$facility->id}.");
            }

            // Update facility tags relation
            if ($facility->tags !== null) {
                $this->manageFacilityTags($facility->id, $facility->tags);
            }

            // Commit the transaction
            $this->db->commit();

            return "Facility with ID {$facility->id} successfully updated.";
        } catch (\Exception $e) {
            // Rollback the transaction if any error occurs
            $this->db->rollBack();
            throw $e;
        }
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

    // Search facilities by parameters
    public function searchFacilities(string $query, string $filter): array
    {
        // Sanitize query
        $query = InputSanitizer::sanitize(['query' => $query])['query'];
        $query = '%' . $query . '%'; // Add wildcards to the query for partial matching

        // Build the WHERE clause
        $whereClause = $this->buildWhereClause($filter);

        // Build the SQL query
        $sql = "
            SELECT 
                f.id AS facility_id,
                f.name AS facility_name,
                l.city AS location_city,
                GROUP_CONCAT(t.name) AS tags
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
            GROUP BY 
                f.id
        ";

        // Execute the query
        $stmt = $this->db->executeSelectQuery($sql, [':query' => $query]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert the string tags into an array
        return $this->convertTagsIntoArray($results);
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

    /**
     * Synchronize the tags associated with a facility.
     * 
     * This method first validates all provided tags. If any tag is invalid, no changes are made.
     * If all tags are valid, it removes all existing tags associated with the given facility
     * and then links the provided tags to the facility.
     * 
     * @param int $facilityId The ID of the facility.
     * @param array $tags An array of tag IDs to associate with the facility.
     * @return void
     * @throws \Exception If any tag ID is invalid.
     */
    private function manageFacilityTags(int $facilityId, array $tags): void
    {
        // Step 1: Validate all tags
        foreach ($tags as $tagId) {
            $tagQuery = "SELECT COUNT(*) FROM Tags WHERE id = :id";
            $tagStmt = $this->db->executeSelectQuery($tagQuery, [':id' => $tagId]);
            $tagExists = $tagStmt->fetchColumn();

            if (!$tagExists) {
                throw new \Exception("Tag with ID $tagId does not exist. No changes were made.");
            }
        }

        // Step 2: Delete all existing tags for the facility
        $deleteTagsQuery = "DELETE FROM Facility_Tags WHERE facility_id = :facility_id";
        $this->db->executeQuery($deleteTagsQuery, [':facility_id' => $facilityId]);

        // Step 3: Link the new tags to the facility
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

    // Helper method to build the WHERE clause based on the filter
    private function buildWhereClause(string $filter): string
    {
        switch ($filter) {
            case 'facility':
                return 'f.name LIKE :query';
            case 'city':
                return 'l.city LIKE :query';
            case 'tag':
                return 't.name LIKE :query';
            default:
                return 'f.name LIKE :query OR l.city LIKE :query OR t.name LIKE :query';
        }
    }

    // Helper method to convert tags into an array
    private function convertTagsIntoArray(array $results): array
    {
        foreach ($results as &$result) {
            if (!empty($result['tags'])) {
                $result['tags'] = explode(',', $result['tags']);
            } else {
                $result['tags'] = [];
            }
        }
        return $results;
    }
}
