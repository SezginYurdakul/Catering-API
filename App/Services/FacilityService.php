<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;
use App\Repositories\FacilityRepository;
use App\Helpers\PaginationHelper;

class FacilityService implements IFacilityService
{
    private $facilityRepository;
    private $locationService;
    private $tagService;

    public function __construct(
        FacilityRepository $facilityRepository,
        ILocationService $locationService,
        ITagService $tagService
    ) {
        $this->facilityRepository = $facilityRepository;
        $this->locationService = $locationService;
        $this->tagService = $tagService;
    }

            /**
     * Get all facilities with enhanced search and filtering capabilities.
     * Returns all facilities as paginated result with optional filtering by name, tag, or location.
     * Enhanced to support field-specific search parameters.
     *
     * @param int $page The page number for pagination.
     * @param int $perPage The number of items per page.
     * @param string|null $name Optional name filter.
     * @param string|null $tag Optional tag filter.
     * @param string|null $city Optional city filter.
     * @param string|null $country Optional country filter.
     * @param string $operator Operator for combining filters ('AND' or 'OR').
     * @return array
     */
    public function getFacilities(int $page = 1, int $perPage = 10, ?string $name = null, ?string $tag = null, ?string $city = null, ?string $country = null, string $operator = 'AND'): array
    {
        try {
            $offset = ($page - 1) * $perPage;

            // Build filters array for compatibility with existing method
            $filters = [];
            $query = null;
            $facilityName = $name;

            $whereData = $this->buildWhereClause($filters, $query, $operator, $facilityName, $city, $tag);
            $whereClause = $whereData['whereClause'];
            $bind = $whereData['bind'];

            if (empty($whereClause)) {
                $whereClause = '1';
            }

            $facilitiesData = $this->facilityRepository->getFacilities($whereClause, $bind, $perPage, $offset);

            $facilities = [];
            foreach ($facilitiesData as $facilityData) {
                $location = $this->locationService->getLocationById($facilityData['location_id']);
                $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];

                $facilities[] = new Facility(
                    $facilityData['facility_id'],
                    $facilityData['facility_name'],
                    $location,
                    $facilityData['creation_date'],
                    $tags
                );
            }

            // Use filtered count when filters are applied, otherwise use total count
            if (!empty($whereClause) && $whereClause !== '1') {
                $totalItems = $this->facilityRepository->getFilteredFacilitiesCount($whereClause, $bind);
            } else {
                $totalItems = $this->facilityRepository->getTotalFacilitiesCount();
            }
            
            $pagination = PaginationHelper::paginate($totalItems, $page, $perPage);

            return [
                'facilities' => $facilities,
                'pagination' => $pagination
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve facilities: " . $e->getMessage());
        }
    }

    /**
     * Get a facility by its ID.
     *
     * @param int $id
     * @return Facility
     */
    public function getFacilityById(int $id): Facility
    {
        try {
            $facilityData = $this->facilityRepository->getFacilityById($id);

            if (!$facilityData) {
                throw new \Exception("Facility with ID $id does not exist.");
            }

            $location = $this->locationService->getLocationById($facilityData['location_id']);
            $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];

            return new Facility(
                $facilityData['facility_id'],
                $facilityData['facility_name'],
                $location,
                $facilityData['creation_date'],
                $tags
            );
        } catch (\Exception $e) {
            throw new \Exception("Failed to retrieve facility: " . $e->getMessage());
        }
    }

    /**
     * Smart tag handling - processes mixed array of tag IDs and names.
     * 
     * @param array $tags Mixed array of integers (tag IDs) and strings (tag names)
     * @return array Array of tag IDs ready for database operations
     */
    private function processSmartTags(array $tags): array
    {
        $tagIds = [];
        
        foreach ($tags as $tag) {
            if (is_int($tag) || is_numeric($tag)) {
                // It's a tag ID - validate it exists
                $tagIds[] = (int) $tag;
            } elseif (is_string($tag)) {
                // It's a tag name - find or create the tag
                try {
                    // Try to find existing tag by name (use getAllTags and search)
                    $allTags = $this->tagService->getAllTags(1, 1000); // Get all tags
                    $existingTag = null;
                    
                    foreach ($allTags['tags'] as $existingTagData) {
                        if (strcasecmp($existingTagData->name, $tag) === 0) {
                            $existingTag = $existingTagData;
                            break;
                        }
                    }
                    
                    if ($existingTag) {
                        $tagIds[] = $existingTag->id;
                    } else {
                        // Tag doesn't exist, create it using existing createTag method
                        $newTagObject = new \App\Models\Tag(0, $tag);
                        $newTag = $this->tagService->createTag($newTagObject);
                        $tagIds[] = $newTag['tag']['id'];
                    }
                } catch (\Exception $e) {
                    // If tag creation fails, skip this tag
                    continue;
                }
            }
        }
        
        return array_unique($tagIds);
    }

    /**
     * Create a new facility with smart tag handling.
     * 
     * @param Facility $facility
     * @param array $tags Mixed array of tag IDs (int) and tag names (string)
     * @return array
     */
    public function createFacility(Facility $facility, array $tags = []): array
    {
        try {
            // Process smart tags
            $processedTagIds = $this->processSmartTags($tags);
            
            // Create the facility
            $createdFacilityId = $this->facilityRepository->createFacility([
                ':name' => $facility->name,
                ':location_id' => $facility->location->id
            ]);

            // Associate tags with facility
            if (!empty($processedTagIds)) {
                $this->facilityRepository->addTagsToFacility($createdFacilityId, $processedTagIds);
            }

            // Retrieve the created facility object with all relationships
            $createdFacilityObject = $this->getFacilityById($createdFacilityId);

            return [
                "message" => "Facility '{$facility->name}' successfully created.",
                "facility" => $createdFacilityObject
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create facility: " . $e->getMessage());
        }
    }

    /**
     * Update a facility with smart tag handling.
     * 
     * @param Facility $facility
     * @param array $tags Mixed array of tag IDs (int) and tag names (string)
     * @return array
     */
    public function updateFacility(Facility $facility, array $tags = []): array
    {
        try {
            // Update facility basic information
            $fields = [
                'name' => $facility->name,
                'location_id' => $facility->location->id
            ];

            $this->facilityRepository->updateFacility($facility->id, $fields);
            
            // Process smart tags if provided
            if (!empty($tags)) {
                $processedTagIds = $this->processSmartTags($tags);
                $this->facilityRepository->addTagsToFacility($facility->id, $processedTagIds);
            }

            // Retrieve updated facility object
            $updatedFacilityObject = $this->getFacilityById($facility->id);

            return [
                "message" => "Facility '{$facility->name}' successfully updated.",
                "facility" => $updatedFacilityObject
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to update facility: " . $e->getMessage());
        }
    }

    /**
     * Delete a facility by its ID.
     * @param int $id
     * @return array
     */
    public function deleteFacility(int $id): array
    {
        try {
            // Get facility first to check if it exists
            $facility = $this->getFacilityById($id);
            if (!$facility) {
                throw new \Exception("Facility with ID {$id} does not exist");
            }

            $this->facilityRepository->deleteFacility($id);
            return ["message" => "Facility deleted successfully"];
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete facility: " . $e->getMessage());
        }
    }

    /**
     * Build the WHERE clause for the SQL query based on filters and query string.
     * Supports both legacy filter-based search and new field-specific search.
     * @param array $filters
     * @param string|null $query
     * @param string $operator
     * @param string|null $facilityName
     * @param string|null $city
     * @param string|null $tag
     * @return array
     */
    private function buildWhereClause(
        array $filters, 
        ?string $query, 
        string $operator, 
        ?string $facilityName = null, 
        ?string $city = null, 
        ?string $tag = null
    ): array {
        $whereClause = [];
        $bind = [];

        // Priority 1: Field-specific search (new enhanced API)
        if ($facilityName || $city || $tag) {
            if ($facilityName) {
                $whereClause[] = "f.name LIKE :facility_name";
                $bind[':facility_name'] = "%$facilityName%";
            }
            if ($city) {
                $whereClause[] = "l.city LIKE :city";
                $bind[':city'] = "%$city%";
            }
            if ($tag) {
                $whereClause[] = "t.name LIKE :tag";
                $bind[':tag'] = "%$tag%";
            }
            // For field-specific search, default to AND if no operator specified
            if ($operator === 'OR' && count($whereClause) > 1) {
                // Keep OR if explicitly requested
            } else {
                $operator = 'AND';
            }
        }
        // Priority 2: Legacy filter-based search (backward compatibility)
        elseif ($query) {
            $queryConditions = [];
            
            // If no filters specified, search in facility name by default
            if (empty($filters)) {
                $queryConditions[] = "f.name LIKE :query";
            } else {
                // Search in specified fields
                foreach ($filters as $filter) {
                    switch ($filter) {
                        case 'facility_name':
                            $queryConditions[] = "f.name LIKE :query";
                            break;
                        case 'city':
                            $queryConditions[] = "l.city LIKE :query";
                            break;
                        case 'tag':
                            $queryConditions[] = "t.name LIKE :query";
                            break;
                    }
                }
            }
            
            if (!empty($queryConditions)) {
                $whereClause[] = "(" . implode(" OR ", $queryConditions) . ")";
                $bind[':query'] = "%$query%";
            }
        }

        // Combine filters with the specified operator (AND/OR)
        $whereClauseString = implode(" $operator ", $whereClause);

        return [
            'whereClause' => $whereClauseString,
            'bind' => $bind
        ];
    }

    /**
     * Get facilities for a specific location.
     * @param int $locationId
     * @return array
     */
    public function getFacilitiesByLocation(int $locationId): array
    {
        try {
            $facilitiesData = $this->facilityRepository->getFacilitiesByLocation($locationId);
            $facilities = [];
            
            foreach ($facilitiesData as $facilityData) {
                $location = $this->locationService->getLocationById($facilityData['location_id']);
                $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];

                $facilities[] = new Facility(
                    $facilityData['facility_id'],
                    $facilityData['facility_name'],
                    $location,
                    $facilityData['creation_date'],
                    $tags
                );
            }
            
            return $facilities;
        } catch (\Exception $e) {
            throw new \Exception("Failed to get facilities by location: " . $e->getMessage());
        }
    }

    /**
     * Get facilities for a specific tag.
     * @param int $tagId
     * @return array
     */
    public function getFacilitiesByTag(int $tagId): array
    {
        try {
            $facilitiesData = $this->facilityRepository->getFacilitiesByTag($tagId);
            $facilities = [];
            
            foreach ($facilitiesData as $facilityData) {
                $location = $this->locationService->getLocationById($facilityData['location_id']);
                $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];

                $facilities[] = new Facility(
                    $facilityData['facility_id'],
                    $facilityData['facility_name'],
                    $location,
                    $facilityData['creation_date'],
                    $tags
                );
            }
            
            return $facilities;
        } catch (\Exception $e) {
            throw new \Exception("Failed to get facilities by tag: " . $e->getMessage());
        }
    }

    /**
     * Add tags to a facility.
     * @param int $facilityId
     * @param array $tagIds
     * @return array
     */
    public function addTagsToFacility(int $facilityId, array $tagIds): array
    {
        try {
            $this->facilityRepository->addTagsToFacility($facilityId, $tagIds);
            return ["message" => "Tags added to facility successfully"];
        } catch (\Exception $e) {
            throw new \Exception("Failed to add tags to facility: " . $e->getMessage());
        }
    }

    /**
     * Remove tags from a facility.
     * @param int $facilityId
     * @param array $tagIds
     * @return array
     */
    public function removeTagsFromFacility(int $facilityId, array $tagIds): array
    {
        try {
            $this->facilityRepository->removeTagsFromFacility($facilityId, $tagIds);
            return ["message" => "Tags removed from facility successfully"];
        } catch (\Exception $e) {
            throw new \Exception("Failed to remove tags from facility: " . $e->getMessage());
        }
    }

    /**
     * Get total count of facilities that match the given filters.
     * @param string|null $name
     * @param string|null $tag
     * @param string|null $city
     * @param string|null $country
     * @param string $operator
     * @return int
     */
    public function getFilteredFacilitiesCount(?string $name = null, ?string $tag = null, ?string $city = null, ?string $country = null, string $operator = 'AND'): int
    {
        try {
            // Build filters array for compatibility with existing method
            $filters = [];
            $query = null;
            $facilityName = $name;

            $whereData = $this->buildWhereClause($filters, $query, $operator, $facilityName, $city, $tag);
            $whereClause = $whereData['whereClause'];
            $bind = $whereData['bind'];

            if (empty($whereClause)) {
                return $this->facilityRepository->getTotalFacilitiesCount();
            }

            return $this->facilityRepository->getFilteredFacilitiesCount($whereClause, $bind);
        } catch (\Exception $e) {
            throw new \Exception("Failed to get filtered facilities count: " . $e->getMessage());
        }
    }
}
