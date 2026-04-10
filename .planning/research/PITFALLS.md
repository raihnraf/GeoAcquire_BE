# Domain Pitfalls

**Domain:** Laravel Spatial/GIS Backend
**Researched:** 2026-04-11
**Confidence:** MEDIUM (based on established spatial database patterns and Laravel ecosystem knowledge)

## Critical Pitfalls

Mistakes that cause rewrites or major issues.

### Pitfall 1: SRID/CRS Projection Mismatches

**What goes wrong:**
Coordinates stored in different spatial reference systems (SRIDs) without proper transformation. WGS84 (SRID 4326) data mixed with projected coordinate systems like UTM or local projections. Distance calculations return wrong values (e.g., calculating distance in degrees instead of meters). Buffer zones are created in wrong units (degrees vs meters).

**Why it happens:**
- Laravel's default spatial packages don't enforce SRID validation
- MySQL accepts geometries with any SRID, even mixed within the same column
- Developers assume all coordinates are "just coordinates" without understanding projection systems
- Copying data from different sources (Google Maps, shapefiles, GPS) without checking SRID

**How to avoid:**
1. **Standardize on SRID 4326 (WGS84)** for all storage — this is the standard for GPS and web mapping
2. **Add SRID validation in model casts** — reject geometries with wrong SRID
3. **Transform on input, not on query** — convert all incoming data to SRID 4326 before storage
4. **Use ST_Distance_Sphere() instead of ST_Distance()** —前者 returns meters, latter returns degrees
5. **Document SRID requirements** in API specs and data import guidelines

```php
// In migration
$table->polygon('boundary')->nullable();
$table->unsignedInteger('boundary_srid')->default(4326);

// In model
protected $casts = [
    'boundary' => Geometry::class,
];

// Validation
if ($geometry->getSRID() !== 4326) {
    $geometry = $geometry->transform(4326); // Transform to WGS84
}
```

**Warning signs:**
- Distance queries return values like "0.00123" instead of "137 meters"
- Buffer zones appear as tiny squares or massive circles
- Polygons don't align when visualized on maps
- "Point is within polygon" queries return wrong results

**Phase to address:**
**Phase 1 (Foundation)** — Must be addressed before storing any spatial data. Once wrong SRID data exists, fixing it requires complex transformations and potential data loss.

---

### Pitfall 2: Invalid Geometry Storage

**What goes wrong:**
Self-intersecting polygons, unclosed rings, polygons with holes that aren't properly nested, or polygons with too few vertices. MySQL accepts these but spatial queries fail or return wrong results. ST_Area() returns 0 or negative values. ST_Contains() returns inconsistent results.

**Why it happens:**
- GeoJSON from clients isn't validated before storage
- Users draw polygons on maps that self-intersect
- Bulk imports from shapefiles skip validation for speed
- Manual coordinate entry has typos (missing closing coordinate)
- Laravel spatial packages don't validate by default

**How to avoid:**
1. **Validate all geometries before insertion** using `ST_IsValid()`
2. **Auto-fix invalid geometries** using `ST_MakeValid()` before storage
3. **Add Laravel validation rules** for GeoJSON input
4. **Reject polygons with < 4 vertices** (triangle minimum)
5. **Validate polygon closure** — first coordinate must equal last

```php
// In migration validator
\DB::statement('ALTER TABLE parcels ADD CONSTRAINT check_boundary_valid 
    CHECK (ST_IsValid(boundary))');

// In Laravel validation
'geojson' => ['required', 'json', function ($attribute, $value, $fail) {
    $geometry = Geometry::fromJson(json_encode($value));
    if (!$geometry->isValid()) {
        $fail('The geometry is invalid. Please check for self-intersections.');
    }
}]);
```

**Warning signs:**
- ST_Area() returns 0 for polygons that should have area
- Visual rendering shows "broken" polygons
- ST_Contains() returns false for points clearly inside polygons
- Queries throw "Invalid geometry" errors

**Phase to address:**
**Phase 1 (Foundation)** — Must catch this early. Invalid geometries in production are extremely difficult to fix retrospectively.

---

### Pitfall 3: Missing or Misconfigured Spatial Indexes

**What goes wrong:**
Spatial queries (ST_Contains, ST_Intersects, ST_Distance) become progressively slower as data grows. Queries that took 10ms with 100 rows take 10 seconds with 10,000 rows. MySQL doesn't use spatial indexes, performing full table scans with expensive geometry calculations on every row.

**Why it happens:**
- Forgetting to add `spatialIndex` in Laravel migrations
- Adding spatial index after data exists (slow index build)
- Using functions that prevent index usage (e.g., `ST_Distance() < 1000` without bounding box)
- Not understanding that spatial indexes only work with specific functions

**How to avoid:**
1. **Always add spatial indexes in migrations** for columns used in WHERE clauses
2. **Use bounding box queries first** — `MBRContains()` before `ST_Contains()`
3. **Create indexes before bulk imports** — much faster than adding after
4. **Use ST_Distance_Sphere() with AND conditions** that leverage indexes
5. **Monitor query performance** with EXPLAIN on spatial queries

```php
// In migration
Schema::create('parcels', function (Blueprint $table) {
    $table->id();
    $table->polygon('boundary');
    $table->spatialIndex('boundary'); // CRITICAL
    $table->point('center');
    $table->spatialIndex('center'); // CRITICAL
});

// Efficient query pattern
$parcels = Parcel::whereRaw('MBRContains(ST_Buffer(?, ?), boundary)', [
    $point->toWkt(),
    $bufferDistance
])->whereRaw('ST_Distance_Sphere(center, ?) < ?', [
    $point->toWkt(),
    $maxDistance
])->get();
```

**Warning signs:**
- Query time increases linearly with row count
- EXPLAIN shows "ALL" type instead of "range"
- CPU spikes during spatial queries
- Queries timeout with larger datasets

**Phase to address:**
**Phase 1 (Foundation)** — Add indexes from day one. Retroactively adding spatial indexes to large tables can take hours and lock the table.

---

### Pitfall 4: GeoJSON Format Violations

**What goes wrong:**
API responses that claim to be GeoJSON but violate the spec. Missing "type" field, wrong coordinate order (lat/long instead of long/lat), missing "crs" property when using non-WGS84, FeatureCollection without "features" array. Frontend mapping libraries fail silently or render nothing.

**Why it happens:**
- Manually building JSON instead of using GeoJSON libraries
- Copying GeoJSON examples without understanding the spec
- Assuming GeoJSON is "just JSON with coordinates"
- Not testing with actual mapping libraries (Leaflet, Mapbox)
- Laravel's default JSON serialization doesn't enforce GeoJSON structure

**How to avoid:**
1. **Use dedicated GeoJSON libraries** — don't manually build JSON
2. **Validate GeoJSON output** against spec using json-schema
3. **Test with real frontend** — use Leaflet/Mapbox from day one
4. **Standardize coordinate order** — always [longitude, latitude]
5. **Add API tests** that parse responses as GeoJSON

```php
// Use a GeoJSON resource
class ParcelGeoJsonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'Feature',
            'geometry' => json_decode($this->boundary->toJson()), // Proper GeoJSON
            'properties' => [
                'id' => $this->id,
                'owner_name' => $this->owner_name,
                'status' => $this->status,
                'area_sqm' => $this->area_sqm,
            ],
        ];
    }
}

// Return as FeatureCollection
return response()->json([
    'type' => 'FeatureCollection',
    'features' => ParcelGeoJsonResource::collection($parcels),
]);
```

**Warning signs:**
- Frontend shows "Invalid GeoJSON" errors
- Map renders but geometries are in wrong locations (ocean instead of land)
- Coordinates appear reversed
- Leaflet/Mapbox fail to parse responses

**Phase to address:**
**Phase 2 (API Development)** — Can be caught during API development, but better to prevent in Phase 1 by using GeoJSON libraries from the start.

---

### Pitfall 5: Bulk Import Failures

**What goes wrong:**
Importing 10,000+ parcels from GeoJSON or shapefiles fails midway through. Transaction deadlocks. Memory exhaustion. Invalid geometries crash the entire import. No way to resume partial imports. Data corruption from partial commits.

**Why it happens:**
- Loading entire file into memory before processing
- Single transaction for all inserts (locks too long)
- No batch processing (one INSERT per row)
- No validation before import (fails on row 9,237 of 10,000)
- Not disabling indexes during import
- No progress tracking or resumption capability

**How to avoid:**
1. **Process in batches** — 500-1000 rows per transaction
2. **Validate entire file before import** — fail fast, not midway
3. **Disable spatial indexes during import**, rebuild after
4. **Use queue jobs** for large imports (prevent timeouts)
5. **Track progress** — store "imported X of Y rows" status
6. **Implement resume capability** — skip already-imported rows

```php
// Bulk import with batching
DB::statement('SET unique_checks=0');
DB::statement('SET foreign_key_checks=0');
DB::statement('ALTER TABLE parcels DISABLE KEYS');

DB::transaction(function () use ($features) {
    $chunks = array_chunk($features, 500);
    
    foreach ($chunks as $chunk) {
        Parcel::insert($chunk); // Batch insert
    }
}, 3); // 3 retries for deadlocks

DB::statement('ALTER TABLE parcels ENABLE KEYS');
DB::statement('SET unique_checks=1');
DB::statement('SET foreign_key_checks=1');
```

**Warning signs:**
- Imports hang or timeout after 30-60 seconds
- "Maximum execution time exceeded" errors
- MySQL deadlocks during import
- Partial data with no way to identify what's missing

**Phase to address:**
**Phase 3 (Bulk Operations)** — This is specifically a bulk operations concern, but design for it in Phase 2 by ensuring your schema supports efficient batch inserts.

---

### Pitfall 6: Massive GeoJSON API Responses

**What goes wrong:**
API endpoint returns 50MB GeoJSON for 10,000 parcels. Frontend takes 30 seconds to download, browser crashes trying to parse. Memory exhaustion on server. No pagination or filtering. Clients can't use the API for anything beyond tiny datasets.

**Why it happens:**
- No pagination or spatial filtering
- Returning all properties for all features
- Returning full geometries when only centroids needed
- No simplification of complex geometries
- Assuming "small dataset = always small"
- No consideration for frontend rendering performance

**How to avoid:**
1. **Implement spatial filtering** — bounding box queries via URL params
2. **Simplify geometries** — use `ST_Simplify()` for display
3. **Property filtering** — allow clients to select fields
4. **Pagination** — even if using cursor-based for spatial data
5. **Tile-based approach** — consider vector tiles for large datasets
6. **GeoJSON compression** — consider Compact GeoJSON or gzip

```php
// Spatial filtering endpoint
public function index(Request $request)
{
    $bbox = $request->input('bbox'); // "minx,miny,maxx,maxy"
    
    $parcels = Parcel::whereRaw('MBRIntersects(
        ST_GeomFromText(?),
        boundary
    )', [$bbox])
    ->selectRaw('id, owner_name, status, 
        ST_Simplify(boundary, 0.0001) as boundary') // Simplify
    ->paginate(100);
    
    return ParcelGeoJsonResource::collection($parcels);
}
```

**Warning signs:**
- Response times > 5 seconds for GeoJSON endpoints
- Network tab shows 10MB+ JSON files
- Frontend freezes on map load
- Memory usage spikes on server during API calls

**Phase to address:**
**Phase 2 (API Development)** — Design API with filtering from day one. Adding pagination later is a breaking change.

---

### Pitfall 7: Incorrect Buffer Zone Calculations

**What goes wrong:**
Buffer zones calculated in degrees instead of meters (SRID 4326 issue). 500m buffer creates massive zone or tiny dot depending on direction. "Find parcels within 1km" returns wrong results. Asymmetric buffers (wider in one direction).

**Why it happens:**
- Using `ST_Buffer()` on WGS84 geometries without transformation
- Buffer distance interpreted as degrees, not meters
- Not transforming to projected coordinate system for buffer operations
- Copying buffer code from PostGIS (which handles this better)

**How to avoid:**
1. **Transform to projected CRS** before buffering
2. **Use ST_Buffer in meters** by transforming to appropriate UTM zone
3. **Transform back to WGS84** after buffering
4. **Document buffer units clearly** in API
5. **Test buffer symmetry** — should be circular, not oval

```php
// Correct buffer approach in Laravel
public function findNearby(Point $point, $meters)
{
    // Transform to appropriate projected CRS
    $pointProjected = $point->transform(3857); // Web Mercator (meters)
    
    // Buffer in meters
    $buffer = $pointProjected->buffer($meters);
    
    // Transform back to WGS84
    $bufferWgs84 = $buffer->transform(4326);
    
    // Query using buffer
    return Parcel::whereRaw('ST_Intersects(boundary, ?)', [
        $bufferWgs84->toWkt()
    ])->get();
}
```

**Warning signs:**
- Buffers appear as ovals instead of circles
- North-south buffers wider than east-west (or vice versa)
- Buffer size varies by latitude
- "Within 1km" returns parcels 500m away but misses 800m parcels

**Phase to address:**
**Phase 1 (Foundation)** — Core spatial operations must be correct from day one. Buffer logic is fundamental to the "find parcels near X" feature.

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Storing coordinates as separate lat/lng columns | Simpler queries, can use standard indexes | Can't use spatial functions, must calculate distance manually, no spatial joins | NEVER — defeats purpose of spatial database |
| Skipping SRID validation | Faster imports, less validation code | Data corruption, wrong calculations, expensive fixes | NEVER — fatal flaw |
| Using raw JSON for GeoJSON instead of spatial types | Simple storage, no migration needed | No spatial queries, no spatial indexes, can't calculate area | NEVER for spatial data, acceptable for non-spatial metadata |
| Manual GeoJSON building instead of libraries | Faster initial development, one less dependency | Spec violations, fragile, breaks easily | MVP only, must replace before production |
| Skipping bulk import optimization | Simpler code, works for small datasets | Can't scale, hours-long imports, timeouts | Acceptable for < 1000 rows, must optimize before production |
| Loading all parcels on map load | Simpler frontend, no filtering logic | Unusable at scale, poor UX, can't demo with real data | Demo-only, must add filtering before any real use |
| Using ST_Distance() instead of ST_Distance_Sphere() | Simpler, fewer parameters | Wrong results (degrees vs meters), subtle bugs | NEVER — always use ST_Distance_Sphere for WGS84 |

---

## Integration Gotchas

Common mistakes when connecting to external services.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Leaflet.js frontend | Sending lat/lng in wrong order to API | Always send [longitude, latitude] — document in API spec |
| Shapefile imports | Assuming shapefile SRID matches WGS84 | Always check .prj file and transform if needed |
| Google Maps API | Using Google's projected coordinates directly | Transform from Web Mercator (3857) to WGS84 (4326) |
| QGIS exports | Exporting as WKT without SRID info | Always export with SRID, validate on import |
| GPS devices | Assuming all GPS data is WGS84 | Validate SRID, some older devices use other systems |
| Third-party GIS APIs | Assuming their GeoJSON is valid | Validate before storing, test with real data |

---

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| No spatial indexes | Queries slow down linearly with row count | Add spatial indexes in migration | 1,000+ rows |
| ST_Distance() on entire table | Full table scan, expensive distance calc | Use MBRContains + ST_Distance_Sphere | 500+ rows |
| Loading all geometries for list view | Massive JSON responses, slow rendering | Return centroids only, load full geometry on-demand | 100+ features in response |
| No query result caching | Repeated expensive spatial queries | Cache frequent queries (Redis), use ETags | 10+ requests per second |
- Individual polygon queries fine, bulk queries timeout | Use batch processing, avoid N+1 queries | 100+ parcels in single operation |
- Client-side filtering of large datasets | Fast with 100 rows, browser crashes with 10,000 | Always filter server-side via bounding box | 1,000+ features |
- Real-time buffer calculations on every request | Expensive geometry operations repeated | Cache buffer zones, pre-calculate for common queries | 10+ concurrent users |

---

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Accepting any GeoJSON without validation | SQL injection via malicious WKT, DoS via complex geometries | Validate geometry size, complexity, and structure before parsing |
| Exposing internal parcel IDs in API | Enumeration attack, exposing acquisition targets | Use UUIDs or opaque tokens, don't expose sequential IDs |
| No rate limiting on spatial queries | DoS via expensive buffer/intersection queries | Rate limit spatial endpoints, require auth for write operations |
| Returning all parcel data including owner info | Privacy breach, exposing negotiation status | Implement field-level permissions, filter sensitive data |
| Allowing unauthenticated buffer calculations | Revealing parcel acquisition targets | Require auth for analysis endpoints, log all queries |
| No input sanitization on GeoJSON | Malformed geometries crash server | Validate GeoJSON schema, limit coordinate precision, reject excessive vertices |

---

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| No spatial filtering in API | Can't zoom to specific area, must load all data | Implement bbox parameter in API, return only visible parcels |
- Missing units in API responses (meters vs feet) | Confusion about buffer distances, wrong acquisition decisions | Always include units, document in API spec, use metric system consistently |
- Slow-loading maps | Portfolio demo fails, stakeholders lose interest | Optimize queries, simplify geometries, implement server-side clustering |
- No error messages for invalid geometries | Users don't know why parcel import failed | Return specific validation errors ("Polygon self-intersects at vertex 3") |
- No progress indication for bulk imports | Users assume system crashed, refresh and cause corruption | WebSocket progress updates, show "Processing 450/1000" |
- Can't see parcel area on map | Must click each parcel to get details | Show area in tooltip or popup on hover |
- No visual feedback for buffer zones | Users don't know if 500m buffer was applied | Render buffer on map with distinct style, show area covered |

---

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Spatial Queries:** Often missing spatial indexes — verify with `EXPLAIN` on ST_Contains queries
- [ ] **GeoJSON Responses:** Often missing "type" field or wrong coordinate order — verify with jsonschema validation
- [ ] **Buffer Zones:** Often calculated in degrees not meters — verify by checking if buffer is circular
- [ ] **Area Calculations:** Often return degrees squared not square meters — verify by checking known parcel sizes
- [ ] **Distance Queries:** Often use ST_Distance instead of ST_Distance_Sphere — verify results are in meters
- [ ] **Bulk Import:** Often skip validation or fail on row 5,000 of 10,000 — verify with test imports of invalid data
- [ ] **SRID Handling:** Often assume all data is WGS84 — verify by checking SRID of imported shapefiles
- [ ] **Geometry Validation:** Often accept self-intersecting polygons — verify with ST_IsValid() checks
- [ ] **API Pagination:** Often return all parcels regardless of count — verify with 10,000+ test dataset
- [ ] **Error Messages:** Often return "Invalid geometry" without specifics — verify with intentionally invalid test data

---

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Wrong SRID data already stored | HIGH | 1. Export all data, 2. Transform to correct SRID using GIS tool, 3. Drop table, 4. Re-import with validation, 5. Update all API docs |
| Missing spatial indexes on large table | MEDIUM | 1. Create new table with indexes, 2. Copy data in batches, 3. Rename tables, 4. Drop old table (allows zero-downtime) |
| Invalid geometries in production | HIGH | 1. Export data, 2. Run ST_MakeValid() on all geometries, 3. Manually fix failures, 4. Re-import with validation enabled, 5. Add validation to prevent recurrence |
| Wrong coordinate order in GeoJSON | MEDIUM | 1. Identify scope (API or storage), 2. If storage: swap coords via script, 3. If API: fix serialization, 4. Frontend may need update if it compensated |
- Slow bulk imports | LOW | 1. Disable indexes before import, 2. Use larger batch sizes (500-1000), 3. Use queue jobs for imports, 4. Re-enable indexes after |
- API response too large | LOW | 1. Add spatial filtering (bbox param), 2. Simplify geometries with ST_Simplify(), 3. Implement pagination, 4. Add property filtering |

---

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| SRID/CRS mismatches | Phase 1 (Foundation) | Import test data from different sources, verify ST_Distance_Sphere returns meters not degrees |
| Invalid geometry storage | Phase 1 (Foundation) | Try importing self-intersecting polygon, verify it's rejected |
| Missing spatial indexes | Phase 1 (Foundation) | Run EXPLAIN on spatial query, verify "range" type not "ALL" |
| GeoJSON format violations | Phase 2 (API Development) | Test API response with Leaflet.js, verify it renders correctly |
| Bulk import failures | Phase 3 (Bulk Operations) | Import 10,000 test parcels, verify completion under 60 seconds |
| Massive API responses | Phase 2 (API Development) | Query with bbox param, verify response < 500KB for 1,000 parcels |
- Buffer zone miscalculations | Phase 1 (Foundation) | Create 500m buffer at different latitudes, verify all are circular not oval |
- Coordinate order confusion | Phase 2 (API Development) | Create test point, verify it appears at correct location in Leaflet |

---

## Sources

**Note:** Web search tools were rate-limited during research. Findings are based on:

- **Established spatial database patterns** — SRID mismatches, invalid geometries, and index issues are well-documented in spatial database literature (PostGIS, MySQL spatial docs)
- **Laravel ecosystem knowledge** — Common patterns with grimzy/laravel-mysql-spatial package
- **GIS best practices** — GeoJSON spec compliance, coordinate order conventions
- **Performance optimization principles** — Index usage patterns, batch processing strategies

**Recommended verification:**
1. MySQL 8.0 Spatial Documentation — https://dev.mysql.com/doc/refman/8.0/en/spatial-index.html
2. GeoJSON Specification — https://geojson.org/
3. grimzy/laravel-mysql-spatial package docs — https://github.com/grimzy/laravel-mysql-spatial
4. GIS Stack Exchange — Search for "MySQL spatial index performance" and "SRID transformation"

**Confidence Level: MEDIUM** — Patterns are well-established in spatial database domain, but specific Laravel implementation details should be verified against current package documentation.

---
*Pitfalls research for: Laravel Spatial/GIS Backend*
*Researched: 2026-04-11*
