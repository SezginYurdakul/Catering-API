<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Facility;
use App\Repositories\FacilityRepository;
use App\Helpers\PaginationHelper;
use App\Domain\Exceptions\DatabaseException;
use App\Domain\Exceptions\ResourceInUseException;
use App\Helpers\Logger;

class FacilityService implements IFacilityService
{
    private FacilityRepository $facilityRepository;
    private ILocationService $locationService;
    private ITagService $tagService;
    private ?Logger $logger;

    public function __construct(
        FacilityRepository $facilityRepository,
        ILocationService $locationService,
        ITagService $tagService,
        ?Logger $logger = null
    ) {
        $this->facilityRepository = $facilityRepository;
        $this->locationService = $locationService;
        $this->tagService = $tagService;
        $this->logger = $logger;
    }

    /**
     * Get all facilities with enhanced search and filtering capabilities.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getFacilities(
        int $page = 1,
        int $perPage = 10,
        ?string $name = null,
        ?string $tag = null,
        ?string $city = null,
        ?string $country = null,
        string $operator = 'AND',
        array $filters = [],
        ?string $query = null
    ): array {
        try {
            $offset = ($page - 1) * $perPage;
            $facilityName = $name;

            $whereData = $this->buildWhereClause($filters, $query, $operator, $facilityName, $city, $tag);
            $whereClause = $whereData['whereClause'];
            $bind = $whereData['bind'];

            if (empty($whereClause)) {
                $whereClause = '1';
            }

            $facilitiesData = $this->facilityRepository->getFacilities($whereClause, $bind, $perPage, $offset);
            $facilities = $this->mapToFacilityObjects($facilitiesData);

            // Use filtered count when filters are applied
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
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', $e->getMessage());
        }
    }

    /**
     * Get a facility by its ID.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getFacilityById(int $id): ?Facility
    {
        try {
            $facilityData = $this->facilityRepository->getFacilityById($id);

            if (!$facilityData) {
                return null;
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
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', $e->getMessage(), ['id' => $id]);
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
                // It's a tag ID - add directly
                $tagIds[] = (int) $tag;
            } elseif (is_string($tag)) {
                try {
                    // It's a tag name - find or create the tag
                    $allTags = $this->tagService->getAllTags(1, 1000);
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
                        // Create new tag
                        $newTagObject = new \App\Models\Tag(0, $tag);
                        $newTag = $this->tagService->createTag($newTagObject);
                        $tagIds[] = $newTag['tag']['id'];
                    }
                } catch (\Exception $e) {
                    // Log and skip problematic tag
                    error_log("Failed to process tag '{$tag}': " . $e->getMessage());
                    continue;
                }
            }
        }

        return array_unique($tagIds);
    }

    /**
     * Create a new facility with smart tag handling.
     * 
     * Note: Location validation is done in controller layer.
     * 
     * @throws DatabaseException If database operation fails
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

            if (!$createdFacilityId) {
                throw new DatabaseException('INSERT', 'Facilities', 'Failed to retrieve facility ID');
            }

            // Associate tags with facility
            if (!empty($processedTagIds)) {
                $this->facilityRepository->addTagsToFacility($createdFacilityId, $processedTagIds);
            }

            // Retrieve the created facility object
            $createdFacilityObject = $this->getFacilityById($createdFacilityId);

            return [
                "message" => "Facility '{$facility->name}' successfully created.",
                "facility" => $createdFacilityObject
            ];
        } catch (\PDOException $e) {
            throw new DatabaseException('INSERT', 'Facilities', $e->getMessage(), [
                'name' => $facility->name,
                'location_id' => $facility->location->id
            ]);
        }
    }

    /**
     * Update a facility with smart tag handling.
     * 
     * Note: Location validation is done in controller layer.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function updateFacility(Facility $facility, array $tags = []): array
    {
        try {
            // Update facility basic information
            $fields = [
                'name' => $facility->name,
                'location_id' => $facility->location->id
            ];

            $result = $this->facilityRepository->updateFacility($facility->id, $fields);

            if (!$result) {
                throw new DatabaseException('UPDATE', 'Facilities', 'Update operation returned false', [
                    'id' => $facility->id
                ]);
            }

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
        } catch (\PDOException $e) {
            throw new DatabaseException('UPDATE', 'Facilities', $e->getMessage(), [
                'id' => $facility->id
            ]);
        }
    }

    /**
     * Delete a facility by its ID.
     * 
     * @throws ResourceInUseException If facility has assigned employees
     * @throws DatabaseException If database operation fails
     */
    public function deleteFacility(int $id): array
    {
        try {
            $employees = $this->facilityRepository->getEmployeesByFacilityId($id);
            if (empty($employees)) {
                $result = $this->facilityRepository->deleteFacility($id);
            } else {
                throw new ResourceInUseException('Facility', $id, 'Employees');
            }

            if (!$result) {
                 $this->logger->logDatabaseError('DELETE', 'deleteFacility', 'Delete operation returned false', ['id' => $id]);
                throw new DatabaseException('DELETE', 'Facilities', 'Delete operation returned false', [
                    'id' => $id
                ]);
            }

            return ["message" => "Facility deleted successfully"];
        } catch (\PDOException $e) {
            throw new DatabaseException('DELETE', 'Facilities', $e->getMessage(), ['id' => $id]);
        }
    }

    /**
     * Build the WHERE clause for the SQL query based on filters and query string.
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

        // Priority 1: Field-specific search
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
        }
        // Priority 2: Legacy filter-based search
        elseif ($query) {
            $queryConditions = [];

            // If no filters specified, search in facility name by default
            if (empty($filters)) {
                $queryConditions[] = "f.name LIKE :query";
            } else {
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

        $whereClauseString = implode(" $operator ", $whereClause);

        return [
            'whereClause' => $whereClauseString,
            'bind' => $bind
        ];
    }

    /**
     * Get facilities for a specific location.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getFacilitiesByLocation(int $locationId): array
    {
        try {
            $facilitiesData = $this->facilityRepository->getFacilitiesByLocation($locationId);
            return $this->mapToFacilityObjects($facilitiesData);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', $e->getMessage(), [
                'location_id' => $locationId
            ]);
        }
    }

    /**
     * Get facilities for a specific tag.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getFacilitiesByTag(int $tagId): array
    {
        try {
            $facilitiesData = $this->facilityRepository->getFacilitiesByTag($tagId);
            return $this->mapToFacilityObjects($facilitiesData);
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', $e->getMessage(), ['tag_id' => $tagId]);
        }
    }

    /**
     * Add tags to a facility.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function addTagsToFacility(int $facilityId, array $tagIds): array
    {
        try {
            $this->facilityRepository->addTagsToFacility($facilityId, $tagIds);
            return ["message" => "Tags added to facility successfully"];
        } catch (\PDOException $e) {
            throw new DatabaseException('INSERT', 'Facility_Tags', $e->getMessage(), [
                'facility_id' => $facilityId,
                'tag_ids' => $tagIds
            ]);
        }
    }

    /**
     * Remove tags from a facility.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function removeTagsFromFacility(int $facilityId, array $tagIds): array
    {
        try {
            $this->facilityRepository->removeTagsFromFacility($facilityId, $tagIds);
            return ["message" => "Tags removed from facility successfully"];
        } catch (\PDOException $e) {
            throw new DatabaseException('DELETE', 'Facility_Tags', $e->getMessage(), [
                'facility_id' => $facilityId,
                'tag_ids' => $tagIds
            ]);
        }
    }

    /**
     * Get total count of facilities that match the given filters.
     * 
     * @throws DatabaseException If database operation fails
     */
    public function getFilteredFacilitiesCount(
        ?string $name = null,
        ?string $tag = null,
        ?string $city = null,
        ?string $country = null,
        string $operator = 'AND'
    ): int {
        try {
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
        } catch (\PDOException $e) {
            throw new DatabaseException('SELECT', 'Facilities', 'Failed to count facilities', [
                'name' => $name,
                'tag' => $tag,
                'city' => $city
            ]);
        }
    }

    /**
     * Transform raw facility data array into Facility model objects.
     */
    private function mapToFacilityObjects(array $facilitiesData): array
    {
        $facilities = [];

        foreach ($facilitiesData as $facilityData) {
            try {
                $location = $this->locationService->getLocationById($facilityData['location_id']);
                $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];

                $facilities[] = new Facility(
                    $facilityData['facility_id'],
                    $facilityData['facility_name'],
                    $location,
                    $facilityData['creation_date'],
                    $tags
                );
            } catch (\Exception $e) {
                // Log and skip problematic facility
                error_log("Failed to map facility {$facilityData['facility_id']}: " . $e->getMessage());
                continue;
            }
        }

        return $facilities;
    }

    private function getEmployeesByFacilityId(int $facilityId): array
    {
        return $this->facilityRepository->getEmployeesByFacilityId($facilityId);
    }
}
