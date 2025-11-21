# DRY Refactoring: `mapToFacilityObjects()` Helper Method

## Overview

**Location:** `App/Services/FacilityService.php`

**Date:** November 21, 2025

**Issue:** Code duplication (DRY principle violation)

## Problem

The same facility transformation logic was duplicated in three methods:
- `getFacilities()`
- `getFacilitiesByLocation()`
- `getFacilitiesByTag()`

### Before (duplicated code)

Each method contained this identical loop:

```php
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
```

**Result:** ~15 lines of duplicate code × 3 methods = 45 lines of redundancy

## Solution

Created a private helper method to centralize the transformation logic:

### Method Signature

```php
/**
 * Transform raw facility data array into Facility model objects.
 * Eliminates duplicate code (DRY principle) by centralizing the mapping logic.
 * 
 * @param array $facilitiesData Raw facility data from repository
 * @return array Array of Facility model objects
 */
private function mapToFacilityObjects(array $facilitiesData): array
```

### What It Does

1. Takes raw facility data array from repository
2. Iterates through each facility record
3. Fetches related Location object via `locationService->getLocationById()`
4. Fetches related Tags array via `tagService->getTagsByFacilityId()`
5. Constructs Facility model objects with all relationships
6. Returns array of fully-populated Facility objects

### After (clean code)

Now each method uses the helper:

```php
$facilitiesData = $this->facilityRepository->getFacilities($whereClause, $bind, $perPage, $offset);
$facilities = $this->mapToFacilityObjects($facilitiesData);
```

## Benefits

✅ **Single Source of Truth:** Transformation logic exists in only one place

✅ **Easier Maintenance:** Changes to mapping logic only need to be made once

✅ **Reduced Code:** Eliminated ~30 lines of duplicate code

✅ **Better Readability:** Each method is now more concise and focused

✅ **Testability:** Can unit test the transformation logic independently

## Implementation Details

### Methods Updated

1. **`getFacilities()`** (line ~63)
   - Before: 13 lines of transformation code
   - After: 1 line method call

2. **`getFacilitiesByLocation()`** (line ~358)
   - Before: 13 lines of transformation code
   - After: 1 line method call

3. **`getFacilitiesByTag()`** (line ~376)
   - Before: 13 lines of transformation code
   - After: 1 line method call

### Code Comparison

#### Before:
```php
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
```

#### After:
```php
public function getFacilitiesByLocation(int $locationId): array
{
    try {
        $facilitiesData = $this->facilityRepository->getFacilitiesByLocation($locationId);
        
        // Transform raw data to Facility objects using reusable helper
        return $this->mapToFacilityObjects($facilitiesData);
    } catch (\Exception $e) {
        throw new \Exception("Failed to get facilities by location: " . $e->getMessage());
    }
}
```

## Future Improvements

### Performance Optimization (N+1 Query Problem)

Currently, the helper method makes individual queries for each facility:
- 1 query for locations per facility
- 1 query for tags per facility

**Suggestion:** Implement batch fetching:

```php
// Instead of N queries
foreach ($facilities as $facility) {
    $location = $this->locationService->getLocationById($facility['location_id']);
}

// Use 1 query with IN clause
$locationIds = array_column($facilitiesData, 'location_id');
$locations = $this->locationService->getLocationsByIds($locationIds);
```

### Architectural Enhancement

Consider extracting to a separate class for better Single Responsibility Principle (SRP):

```php
class FacilityMapper
{
    public function mapArrayToModels(array $facilitiesData): array
    {
        // Transformation logic here
    }
}
```

This would:
- Further separate concerns
- Make testing easier
- Allow reuse across different services

## Testing

To verify the refactoring works correctly:

```bash
# 1. Get authentication token
TOKEN=$(curl -s -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin"}' | grep -o '"token":"[^"]*"' | cut -d'"' -f4)

# 2. Test getFacilities()
curl -s "http://localhost:8080/facilities" \
  -H "Authorization: Bearer $TOKEN" | jq '.facilities[0]'

# 3. Test with filters (uses same helper)
curl -s "http://localhost:8080/facilities?tag=corporate" \
  -H "Authorization: Bearer $TOKEN" | jq '.facilities[0]'

# 4. Verify facility structure includes location and tags
curl -s "http://localhost:8080/facilities?city=Amsterdam" \
  -H "Authorization: Bearer $TOKEN" | jq '.facilities[0] | {id, name, location, tags}'
```

## Impact on SOLID/PSR Score

**Before:** -1 point for code duplication (DRY violation)

**After:** Improved adherence to DRY principle, cleaner code structure

This refactoring addresses one of the main SOLID/PSR improvement areas identified in the project evaluation.
