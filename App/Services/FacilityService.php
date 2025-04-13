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
     * Get facilities with pagination and optional filters.
     * Retrieves a paginated list of facilities, optionally filtered by a query string and additional filters.
     *
     * @param int $page
     * @param int $perPage
     * @param string|null $query
     * @param array $filters
     * @param string $operator
     * @return array
     */
    public function getFacilities(
        int $page,
        int $perPage,
        ?string $query = null,
        array $filters = [],
        string $operator = 'OR'
    ): array {
        try {
            $offset = ($page - 1) * $perPage;

            $whereData = $this->buildWhereClause($filters, $query, $operator);
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

            $totalItems = $this->facilityRepository->getTotalFacilitiesCount();
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
     * Create a new facility.
     * Creates a new facility and associates it with tags if provided.
     * @param Facility $facility
     * @param array $tagIds
     * @param array $tagNames
     * @return array|string
     */
    public function createFacility(Facility $facility, array $tagIds = [], array $tagNames = []): array|string
    {
        try {
            // Create the facility
            $createdFacilityId = $this->facilityRepository->createFacility([
                ':name' => $facility->name,
                ':location_id' => $facility->location->id
            ]);

            // Handle tagIds
            if (!empty($tagIds)) {
                $this->facilityRepository->addTagsToFacility($createdFacilityId, $tagIds);
            }

            // Handle tagNames (if provided, create new tags and associate them)
            if (!empty($tagNames)) {
                foreach ($tagNames as $tagName) {
                    // Tag nesnesi oluştur
                    $tag = new \App\Models\Tag(0, $tagName);
                    $tagId = $this->tagService->createTag($tag)['tag']->id; // Yeni oluşturulan tag ID'sini al
                    $this->facilityRepository->addTagsToFacility($createdFacilityId, [$tagId]);
                }
            }
            // Retrieve the created facility object
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
     * Update a facility.
     * Updates the facility's name and location, and optionally adds tags.
     * @param $facility
     * @param array $tagIds
     * @param array $tagNames
     * @return array
     */
    public function updateFacility(Facility $facility, array $tagIds = [], array $tagNames = []): array
    {
        try {
            $fields = [
                'name' => $facility->name,
                'location_id' => $facility->location->id
            ];

            $this->facilityRepository->updateFacility($facility->id, $fields);
            // Handle tagIds
            if (!empty($tagIds)) {
                $this->facilityRepository->addTagsToFacility($facility->id, $tagIds);
            }

            $updatedFacilityObject = $this->getFacilityById($facility->id);

            return [
                "message" => "Facility '{$facility->name}' successfully updated.",
                "facility" => $updatedFacilityObject
            ];
        } catch (\Exception $e) {
            throw new \Exception("Failed to create facility: " . $e->getMessage());
        }
    }

    /**
     * Delete a facility.
     * Deletes the facility and its associated tags.
     * @param Facility $facility
     * @return string
     */
    public function deleteFacility(Facility $facility): string
    {
        try {
            $this->facilityRepository->deleteFacility($facility->id);

            return "Facility with ID {$facility->id} successfully deleted.";
        } catch (\Exception $e) {
            throw new \Exception("Failed to delete facility: " . $e->getMessage());
        }
    }

    /**
     * Build the WHERE clause for the SQL query based on filters and query string.
     * @param array $filters
     * @param string|null $query
     * @param string $operator
     * @return array
     */
    private function buildWhereClause(array $filters, ?string $query, string $operator): array
    {
        $whereClause = [];
        $bind = [];

        // Add query filter
        if ($query) {
            $whereClause[] = "(f.name LIKE :query)";
            $bind[':query'] = "%$query%";
        }

        // Add additional filters
        foreach ($filters as $key => $value) {
            $whereClause[] = "f.$key = :$key";
            $bind[":$key"] = $value;
        }

        // Combine filters with the specified operator (AND/OR)
        $whereClauseString = implode(" $operator ", $whereClause);

        return [
            'whereClause' => $whereClauseString,
            'bind' => $bind
        ];
    }
}
