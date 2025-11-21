# Refactoring Documentation

This document describes code improvements made to enhance code quality, maintainability, and user experience.

---

## 1. DRY Refactoring: `mapToFacilityObjects()` Helper

**File:** `App/Services/FacilityService.php`

### Problem
Duplicate facility transformation logic in 3 methods (~30 lines redundancy):
- `getFacilities()`
- `getFacilitiesByLocation()`
- `getFacilitiesByTag()`

### Solution
Created `mapToFacilityObjects()` helper method:

```php
private function mapToFacilityObjects(array $facilitiesData): array
```

**What it does:**
1. Takes raw facility data from repository
2. Fetches related Location and Tags for each facility
3. Returns array of Facility model objects

### Benefits
✅ Single source of truth  
✅ Eliminated ~30 lines of duplicate code  
✅ Easier maintenance  
✅ Better testability

### Code Comparison

```php
// Before (repeated in 3 methods)
foreach ($facilitiesData as $facilityData) {
    $location = $this->locationService->getLocationById($facilityData['location_id']);
    $tags = $this->tagService->getTagsByFacilityId($facilityData['facility_id']) ?? [];
    $facilities[] = new Facility(...);
}

// After (1 helper, 3 callers)
return $this->mapToFacilityObjects($facilitiesData);
```

### Future Improvements
- **N+1 Query Problem:** Use batch fetching with `IN` clause
- **SRP Enhancement:** Extract to dedicated `FacilityMapper` class

---

## 2. DELETE Endpoint Response Change (204 → 200)

**Files Modified:**
- `App/Controllers/FacilityController.php`
- `App/Controllers/LocationController.php`
- `App/Controllers/TagController.php`

### Problem
DELETE operations returned **204 No Content** but caused Postman parse errors:
- `Status.php` always sends `Content-Type: application/json` header
- 204 has empty body → Postman tries to parse JSON → fails

### Solution
Changed to **200 OK** with success message:

```php
// Before
$response = new NoContent();

// After
$response = new Ok(['message' => 'Facility deleted successfully']);
```

### Why 200 OK?
- ✅ Postman compatibility
- ✅ User-friendly success messages
- ✅ Easier frontend integration
- ⚠️ Trade-off: Slightly less RESTful but more practical

### Modified Methods
- `FacilityController::deleteFacility()`
- `LocationController::deleteLocation()`
- `TagController::deleteTag()`

All now return: `new Ok(['message' => 'Resource deleted successfully'])`

### Response Comparison
| Aspect | Before (204) | After (200) |
|--------|--------------|-------------|
| Status Code | 204 No Content | 200 OK |
| Response Body | Empty | `{"message": "..."}` |
| Postman | ❌ Parse Error | ✅ Works |
| RESTfulness | More standard | Pragmatic |

---

## Summary

Both refactorings improve code quality and usability:
- **DRY Refactoring:** Addresses code duplication (SOLID/PSR improvement)
- **DELETE Response:** Enhances API consumer experience and tool compatibility
