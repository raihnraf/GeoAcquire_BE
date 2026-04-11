# Phase 2: Spatial Analysis - Research

**Researched:** 2026-04-11
**Domain:** Spatial Query APIs, Buffer Zone Analysis, GeoJSON Bulk Import
**Confidence:** HIGH

## Summary

Phase 2 builds on Phase 1's spatial foundation to add spatial queries, buffer zone analysis, and bulk data import. The existing codebase already contains stub implementations of the core spatial queries in `ParcelRepository` and `ParcelService` — these need API endpoints exposed. The primary technical challenges are: (1) exposing existing repository methods via HTTP endpoints, (2) implementing status filtering with enum validation, (3) creating a bulk GeoJSON import endpoint with per-feature error reporting, and (4) building aggregate statistics with proper GeoJSON formatting.

**Primary recommendation:** Use the existing spatial query implementations in `ParcelRepository` (already verified working via tests) and expose them through new controller methods with minimal changes. The `ParcelStatus` enum already exists and provides type-safe status values.

## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| SPAT-01 | API can find parcels within bounding box via GET /api/parcels?bbox=minx,miny,maxx,maxy | Repository method `findWithinBoundingBox()` already exists and tested (Edge Case 4 test) |
| SPAT-02 | API can find parcels within buffer zone of point via POST /api/analysis/buffer | Repository method `findWithinBuffer()` already exists using `ST_Distance_Sphere` |
| SPAT-03 | API can find parcels within buffer zone of parcel via GET /api/parcels/{id}/buffer?distance=500 | Repository method `findWithinBufferOfParcel()` already exists and tested (Edge Case 3 test) |
| FOUND-05 | API filters parcels by status (target, negotiating, free) via ?status= parameter | `ParcelStatus` enum exists with values: free, negotiating, target. Repository has `findByStatus()` |
| DATA-06 | API can import bulk GeoJSON via POST /api/parcels/import with validation feedback | `ParcelService::importGeoJsonFeatures()` already implements this with error tracking |
| ANAL-02 | API can aggregate total area by status via GET /api/parcels/aggregate/area?by=status | Repository method `getAggregateAreaByStatus()` exists using `SUM(area_sqm) GROUP BY status` |

**Key finding:** All core spatial logic is already implemented and tested. This phase is primarily about exposing these methods via HTTP endpoints with proper validation and response formatting.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel | 12.0 | Backend API framework | Existing installation, provides routing, validation, API Resources |
| MySQL | 8.0.45 | Spatial data storage | Verified installed, spatial indexes on `boundary` and `centroid` columns |
| PHP | 8.2.30 | Runtime | Verified installed, required by Laravel 12 |
| matanyadaev/laravel-eloquent-spatial | 4.7.0 | Eloquent spatial integration | VERIFIED installed via `composer show`, released 2026-03-18 |

### Spatial Query Functions
| Function | Purpose | MySQL Implementation |
|----------|---------|---------------------|
| `ST_Distance_Sphere(geom1, geom2)` | Spherical distance in meters | Used for buffer zone queries, works with POINT geometries |
| `ST_Intersects(geom1, geom2)` | Geometry intersection test | Used for bounding box queries via polygon intersection |
| `ST_Area(geometry)` | Polygon area in square meters | Already used in area calculation, accurate for SRID 4326 |
| `ST_GeomFromText(wkt, srid)` | Create geometry from WKT | Used to construct bounding box polygons and buffer zones |
| `MBRContains(min, geom)` | Minimum bounding rectangle contains | Faster pre-filter before `ST_Intersects()` for large datasets |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel API Resources | Native | GeoJSON response formatting | Already used in `ParcelResource` and `ParcelCollectionResource` |
| PHPUnit | 11.5+ | Testing spatial queries | Existing test infrastructure with MySQL connection configured |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `ST_Distance_Sphere` on centroid | `ST_Distance_Sphere` on boundary polygon | Centroid is faster and sufficient for parcel proximity. Full polygon distance requires `ST_Distance` which returns degrees, not meters |
| `ST_Intersects` for bbox | `MBRContains` for bbox | MBRContains is faster but only checks bounding boxes. `ST_Intersects` is more accurate for rotated polygons. Current implementation uses `ST_Intersects` |
| Bulk import via API | Bulk import via Artisan command | Both are implemented. API endpoint allows remote imports; command is useful for local admin tasks |

**Installation:**
```bash
# All dependencies already installed
composer show matanyadaev/laravel-eloquent-spatial
# name     : matanyadaev/laravel-eloquent-spatial
# versions : * 4.7.0
```

**Version verification:**
- `matanyadaev/laravel-eloquent-spatial`: 4.7.0 [VERIFIED: composer show] - Released 2026-03-18
- MySQL: 8.0.45 [VERIFIED: mysql --version]
- PHP: 8.2.30 [VERIFIED: php --version]

## Architecture Patterns

### Existing Project Structure
```
app/
├── Console/
│   └── Commands/
│       └── ImportGeoJson.php      # Artisan command for bulk import
├── Enums/
│   └── ParcelStatus.php           # Enum: free, negotiating, target
├── Http/
│   ├── Controllers/Api/
│   │   ├── ParcelController.php   # CRUD endpoints
│   │   └── AreaController.php     # Area calculation endpoint
│   ├── Requests/
│   │   ├── StoreParcelRequest.php
│   │   └── UpdateParcelRequest.php
│   └── Resources/
│       ├── ParcelResource.php     # Single parcel as GeoJSON Feature
│       └── ParcelCollectionResource.php  # Collection as FeatureCollection
├── Models/
│   └── Parcel.php                 # HasSpatial trait, casts for boundary/centroid
├── Repositories/
│   └── ParcelRepository.php       # Spatial queries already implemented
├── Rules/
│   └── GeoJsonPolygon.php         # GeoJSON validation rule
├── Services/
│   └── ParcelService.php          # Business logic, bulk import
└── Support/
    └── GeometryHelper.php         # Coordinate conversion helpers
```

### Pattern 1: Spatial Query via Repository Layer
**What:** Non-trivial database queries live in `ParcelRepository`, simple CRUD uses Eloquent directly in Service layer.
**When to use:** Any query involving spatial functions or complex WHERE clauses.
**Example:**
```php
// Source: Existing ParcelRepository.php (lines 62-88)
public function findWithinBoundingBox(
    float $minLng,
    float $minLat,
    float $maxLng,
    float $maxLat
): Collection {
    // Specify SRID 4326 and axis-order to match boundary column
    return $this->model::whereRaw(
        'ST_Intersects(boundary, ST_GeomFromText(?, 4326, ?))',
        [
            sprintf(
                'POLYGON((%s %s, %s %s, %s %s, %s %s, %s %s))',
                $minLng, $minLat, $maxLng, $minLat,
                $maxLng, $maxLat, $minLng, $maxLat,
                $minLng, $minLat
            ),
            'axis-order=long-lat',
        ]
    )->get();
}
```

### Pattern 2: Enum-Based Status Filtering
**What:** PHP 8.2+ enum for type-safe status values with validation.
**When to use:** Any field with fixed set of values.
**Example:**
```php
// Source: Existing ParcelStatus.php (lines 5-28)
enum ParcelStatus: string
{
    case Free = 'free';
    case Negotiating = 'negotiating';
    case Target = 'target';

    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    public static function fromString(string $value): self
    {
        return match ($value) {
            'free' => self::Free,
            'negotiating' => self::Negotiating,
            'target' => self::Target,
            default => throw new \InvalidArgumentException("Invalid parcel status: {$value}"),
        };
    }
}
```

### Pattern 3: Bulk Import with Per-Feature Error Tracking
**What:** Import continues on individual feature failures, returns summary with errors.
**When to use:** Any bulk operation where partial success is acceptable.
**Example:**
```php
// Source: Existing ParcelService.php (lines 117-153)
public function importGeoJsonFeatures(array $geojsonData): array
{
    $imported = 0;
    $errors = [];

    foreach ($geojsonData['features'] as $index => $feature) {
        try {
            $this->validateGeoJsonFeature($feature);
            // ... create parcel
            $imported++;
        } catch (\Exception $e) {
            $errors[] = [
                'feature_index' => $index,
                'error' => $e->getMessage(),
            ];
        }
    }

    return [
        'imported' => $imported,
        'errors' => $errors,
    ];
}
```

### Anti-Patterns to Avoid
- **Using ST_Distance instead of ST_Distance_Sphere**: `ST_Distance` returns degrees for geographic coordinates. Always use `ST_Distance_Sphere` for meter-based distance queries in SRID 4326.
- **Filtering status without enum validation**: Don't accept arbitrary strings. Use `ParcelStatus::fromString()` or validate against `ParcelStatus::values()`.
- **Returning raw Eloquent collections from API endpoints**: Always wrap in API Resources for consistent GeoJSON formatting. Use `ParcelCollectionResource` for lists.
- **Blocking entire import on single feature error**: The existing implementation continues and returns errors. Maintain this pattern for better UX.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| GeoJSON validation | Custom polygon validator | `GeoJsonPolygon` rule (already exists) | Handles coordinate bounds, ring closure, nested ring validation |
| Geometry casting | Manual WKT parsing | `HasSpatial` trait with `Polygon::class` and `Point::class` casts | Automatic conversion between WKT and PHP objects |
| Centroid calculation | Manual coordinate averaging | `$polygon->centroid` property or `GeometryHelper::centroidFromCoordinates()` | Built-in spatial operations, tested |
| Status enum validation | if/else chains | PHP 8.2+ `match` expression with enum | Type-safe, exhaustiveness check by compiler |

**Key insight:** The existing codebase already has spatial helpers, validation rules, and service methods. Phase 2 should wire these to HTTP endpoints rather than rebuilding functionality.

## Common Pitfalls

### Pitfall 1: ST_Distance_Sphere Only Works with POINT Geometry
**What goes wrong:** Trying to use `ST_Distance_Sphere` directly on POLYGON boundary returns incorrect results or errors.
**Why it happens:** `ST_Distance_Sphere` is designed for POINT-to-POINT calculations. POLYGON distances require different handling.
**How to avoid:** Use the pre-calculated `centroid` column (POINT type) for distance calculations. The existing repository already does this correctly.
**Warning signs:** Distance queries returning unexpectedly large values, or queries failing with "Geometry type not supported" errors.

### Pitfall 2: Bounding Box Parameter Order Confusion
**What goes wrong:** Users pass `bbox=lat1,lng1,lat2,lng2` instead of `lng1,lat1,lng2,lat2`.
**Why it happens:** GeoJSON uses `[lng, lat]` order but many APIs use `[lat, lng]`.
**How to avoid:** Document parameter order explicitly: `bbox=minLng,minLat,maxLng,maxLat`. Consider adding validation that min < max for both coordinates.
**Warning signs:** Queries returning no results when they should, or parcels far outside the visible area.

### Pitfall 3: Forgetting axis-order Parameter for SRID 4326
**What goes wrong:** Geometries created with `ST_GeomFromText(wkt, 4326)` without `axis-order=long-lat` parameter may interpret coordinates incorrectly in MySQL 8.0.
**Why it happens:** MySQL 8.0+ requires explicit axis-order specification for geographic SRIDs.
**How to avoid:** Always use `ST_GeomFromText(?, 4326, 'axis-order=long-lat')` as the existing code does.
**Warning signs:** Coordinates appearing swapped (latitude stored in longitude column), parcel shapes distorted.

### Pitfall 4: Bulk Import Timeouts on Large Files
**What goes wrong:** Large GeoJSON files (1000+ features) cause HTTP timeout during import.
**Why it happens:** Synchronous processing blocks the request. Free-tier hosting has 30-60 second timeouts.
**How to avoid:** For Phase 2, document a reasonable limit (e.g., 100 features per request). Future enhancement: queue-based import with job status tracking.
**Warning signs:** 504 Gateway Timeout errors, incomplete imports.

### Pitfall 5: Aggregate Area Missing Zero-Value Statuses
**What goes wrong:** `GET /api/parcels/aggregate/area?by=status` only returns statuses that have parcels, missing "free: 0" for empty statuses.
**Why it happens:** `GROUP BY` only returns rows with matching data.
**How to avoid:** Post-process results to include all enum values with zero for missing ones, or use a COALESCE pattern with a generated rows table.
**Warning signs:** Frontend charts showing incomplete data, missing legend entries.

## Code Examples

Verified patterns from existing codebase:

### Spatial Query with Status Filter
```php
// Source: Existing ParcelRepository.php (lines 18-21)
public function findByStatus(string $status): Collection
{
    return $this->model->withStatus($status)->get();
}

// Scope in Parcel.php (lines 88-91)
public function scopeWithStatus($query, string $status)
{
    return $query->where('status', $status);
}
```

### Buffer Zone Query (Point-Based)
```php
// Source: Existing ParcelRepository.php (lines 23-34)
public function findWithinBuffer(
    float $longitude,
    float $latitude,
    float $distanceInMeters
): Collection {
    return $this->model::whereRaw(
        "ST_Distance_Sphere(centroid, ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326, 'axis-order=long-lat')) <= ?",
        [$longitude, $latitude, $distanceInMeters]
    )->get();
}
```

### Aggregate Area by Status
```php
// Source: Existing ParcelRepository.php (lines 90-95)
public function getAggregateAreaByStatus(): Collection
{
    return $this->model::selectRaw('status, SUM(area_sqm) as total_area')
        ->groupBy('status')
        ->get();
}
```

### GeoJSON Bulk Import with Validation
```php
// Source: Existing ParcelService.php (lines 117-153)
public function importGeoJsonFeatures(array $geojsonData): array
{
    $imported = 0;
    $errors = [];

    if (! isset($geojsonData['features']) || ! is_array($geojsonData['features'])) {
        throw new \InvalidArgumentException('Invalid GeoJSON: missing features array');
    }

    foreach ($geojsonData['features'] as $index => $feature) {
        try {
            $this->validateGeoJsonFeature($feature);
            $geometry = $this->parseGeometryFromGeoJson($feature['geometry']);
            $properties = $feature['properties'] ?? [];

            Parcel::create([
                'boundary' => $geometry,
                'owner_name' => $properties['owner_name'] ?? 'Unknown',
                'status' => $properties['status'] ?? 'free',
                'price_per_sqm' => $properties['price_per_sqm'] ?? null,
            ]);

            $imported++;
        } catch (\Exception $e) {
            $errors[] = [
                'feature_index' => $index,
                'error' => $e->getMessage(),
            ];
        }
    }

    return [
        'imported' => $imported,
        'errors' => $errors,
    ];
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `ST_Distance` for geographic coords | `ST_Distance_Sphere` | MySQL 5.7+ | Accurate meter-based distances instead of degrees |
| Text coordinate storage | Spatial GEOMETRY columns | MySQL 5.7+ | Spatial indexing, native spatial functions |
| Manual WKT parsing | EloquentSpatial casts | Laravel 8+ | Type-safe geometry objects, automatic conversion |

**Deprecated/outdated:**
- `MBRContains()` as primary filter: Still useful as pre-filter, but `ST_Intersects()` provides accurate results. Use both for performance on large datasets.
- Calculating areas in PHP: Always use `ST_Area()` in database for accuracy and performance.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | MySQL 8.0 `ST_Buffer()` function works for buffer zone visualization | Architecture Patterns | LOW - `ST_Buffer()` generates polygon geometries but Phase 2 uses distance-based filtering via `ST_Distance_Sphere()` which is already tested |
| A2 | Free-tier Render/Aiven can handle 100-feature bulk import within 30s timeout | Common Pitfalls | MEDIUM - May need to reduce limit or implement chunking. Mitigation: Document reasonable limits |
| A3 | `ST_Intersects()` with axis-order parameter works correctly on Ubuntu MySQL 8.0.45 | Code Examples | LOW - Verified installed version, existing tests pass |
| A4 | Existing `ParcelStatus` enum values (free, negotiating, target) are the only valid statuses | Standard Stack | LOW - Enum is type-safe, any addition requires code change |

## Open Questions

1. **Bulk import size limits**
   - What we know: Existing `importGeoJsonFeatures()` processes all features in one transaction
   - What's unclear: Maximum number of features before timeout on free tier
   - Recommendation: Set a conservative limit (100 features) for Phase 2, document as known limitation

2. **Aggregate response format**
   - What we know: `getAggregateAreaByStatus()` returns `Collection` with `status` and `total_area`
   - What's unclear: Should response include all enum values (with 0 for missing) or only existing statuses?
   - Recommendation: Return only existing statuses for Phase 2. Frontend can handle missing values.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| MySQL 8.0+ | Spatial queries (ST_*) | Yes | 8.0.45 | — |
| PHP 8.2+ | Laravel 12, enum syntax | Yes | 8.2.30 | — |
| matanyadaev/laravel-eloquent-spatial | Geometry casts, spatial types | Yes | 4.7.0 | — |
| PHPUnit | Spatial query testing | Yes | 11.5 | — |

**Missing dependencies with no fallback:** None

**Missing dependencies with fallback:** None

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | PHPUnit 11.5 |
| Config file | phpunit.xml (tests/Feature, tests/Unit) |
| Quick run command | `php artisan test --testsuite=Feature --filter=ParcelApiTest` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SPAT-01 | Bounding box filter returns parcels within bounds | feature | `php artisan test --filter=test_bounding_box_finds_parcels_within_bounds` | Yes (Edge Case 4) |
| SPAT-02 | Buffer zone from point returns nearby parcels | feature | `php artisan test --filter=test_small_distance_buffer_finds_nearby_parcels` | Yes (Edge Case 3) |
| SPAT-03 | Buffer zone from parcel returns neighboring parcels | feature | `php artisan test --filter=test_small_distance_buffer_finds_nearby_parcels` | Yes (existing) |
| FOUND-05 | Status filter returns only matching parcels | feature | `php artisan test --filter=test_filter_by_invalid_status_returns_empty` | Yes (MT-10) |
| DATA-06 | Bulk import handles mixed valid/invalid features | feature | `php artisan test --filter=test_import_geojson_command_handles_mixed_valid_invalid_features` | Yes (MT-7 variant) |
| ANAL-02 | Aggregate area groups by status | unit | NEW test needed | No - Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --testsuite=Feature`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/SpatialAnalysisApiTest.php` — Tests for new endpoints (bbox, buffer, aggregate, import)
- [ ] `tests/Unit/ParcelStatusEnumTest.php` — Enum validation tests
- [ ] Request validation classes for new endpoints: `BufferAnalysisRequest`, `BulkImportRequest`

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | No | Public API for portfolio demo |
| V3 Session Management | No | Stateless API |
| V4 Access Control | No | Public read access |
| V5 Input Validation | Yes | GeoJSON validation via `GeoJsonPolygon` rule, enum validation for status |
| V6 Cryptography | No | No encrypted fields |

### Known Threat Patterns for Laravel Spatial API

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| SQL injection via bbox parameters | Tampering | Parameterized queries in `whereRaw()` (already implemented) |
| GeoJSON bomb (deeply nested coordinates) | Denial of Service | Validate coordinate array depth in `GeoJsonPolygon` rule |
| Invalid coordinate overflow | Tampering | Bounds checking: lng [-180, 180], lat [-90, 90] (already implemented) |
| Bulk import memory exhaustion | Denial of Service | Limit feature count per request, use chunking |

## Sources

### Primary (HIGH confidence)
- **Existing codebase analysis** - All PHP files in `app/` directory reviewed
- **composer show** - Verified `matanyadaev/laravel-eloquent-spatial` v4.7.0 installed
- **mysql --version** - Verified MySQL 8.0.45 available
- **php --version** - Verified PHP 8.2.30 available
- **phpunit.xml** - Test configuration verified

### Secondary (MEDIUM confidence)
- **MySQL 8.0 Reference Manual** - ST_Distance_Sphere, ST_Intersects, ST_Area function behavior (based on training data, consistent with codebase usage)

### Tertiary (LOW confidence)
- **Web search** - Rate-limited, unable to verify. All spatial function usage based on existing working code and MySQL documentation patterns.

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All versions verified via system commands
- Architecture: HIGH - Based on existing codebase patterns
- Pitfalls: MEDIUM - Web search rate-limited, some MySQL behaviors based on training data

**Research date:** 2026-04-11
**Valid until:** 2026-05-11 (30 days - stable tech stack)
