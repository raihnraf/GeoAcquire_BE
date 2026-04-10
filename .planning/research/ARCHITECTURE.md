# Architecture Patterns

**Domain:** Laravel 12 Spatial/GIS Backend API
**Researched:** 2026-04-11
**Confidence:** MEDIUM

## Standard Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    API Layer (Routes)                        │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ ParcelController│  │ SpatialController│  │ ImportController│  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                 │                 │                │
├─────────┼─────────────────┼─────────────────┼────────────────┤
│         ↓                 ↓                 ↓                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  ParcelService│  │ SpatialService│  │  ImportService│  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                 │                 │                │
├─────────┼─────────────────┼─────────────────┼────────────────┤
│         ↓                 ↓                 ↓                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ParcelRepository│  │SpatialRepository│  │ParcelRepository│  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
│         │                 │                 │                │
├─────────┴─────────────────┴─────────────────┴────────────────┤
│                    Data Layer (Eloquent)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Parcel Model│  │  Geometry    │  │MySQL Spatial │      │
│  │  (Spatial)   │  │  Types       │  │  Functions   │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| **Controllers** | HTTP request handling, validation, response formatting | Laravel Controllers + Form Requests |
| **Services** | Business logic, spatial calculations, orchestration | PHP Service Classes with spatial operations |
| **Repositories** | Data access abstraction, spatial query building | Eloquent with spatial traits, query scopes |
| **Models** | Entity representation, spatial field mapping | Eloquent Models with spatial package traits |
| **API Resources** | GeoJSON transformation, response formatting | Laravel API Resources with GeoJSON formatting |
| **Middleware** | Request validation, CORS, error handling | Laravel Middleware + custom GeoJSON validation |

## Recommended Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ParcelController.php       # CRUD for land parcels
│   │   │   ├── SpatialController.php      # Buffer zones, distance queries
│   │   │   └── ImportController.php       # GeoJSON import/export
│   │   └── Requests/
│   │       ├── StoreParcelRequest.php     # Validation for parcel creation
│   │       └── BufferQueryRequest.php     # Validation for spatial queries
│   └── Resources/
│       ├── ParcelResource.php             # GeoJSON Feature transformation
│       └── ParcelCollectionResource.php   # GeoJSON FeatureCollection
├── Models/
│   └── Parcel.php                         # Land parcel with spatial fields
├── Services/
│   ├── ParcelService.php                  # Business logic for parcels
│   ├── SpatialService.php                 # Buffer zones, area calculations
│   └── GeoJSONService.php                 # GeoJSON parsing/generation
├── Repositories/
│   ├── ParcelRepository.php               # Data access layer
│   └── SpatialQueryBuilder.php           # Spatial query composition
├── Spatial/
│   ├── Types/
│   │   └── GeometryTypes.php              # Custom spatial type wrappers
│   └── Calculators/
│       ├── AreaCalculator.php             # ST_Area wrapper
│       └── BufferCalculator.php           # ST_Buffer wrapper
└── Exceptions/
    └── SpatialValidationException.php     # Custom spatial errors

database/
├── migrations/
│   └── create_parcels_table.php           # Spatial column definitions
└── seeders/
    └── ParcelSeeder.php                   # Gading Serpong dummy data

routes/
└── api.php                                # RESTful API endpoints

config/
└── spatial.php                            # Spatial package configuration
```

### Structure Rationale

- **app/Http/Controllers/Api/** - Separates API controllers from potential web controllers, maintains Laravel conventions
- **app/Http/Resources/** - Leverages Laravel's API Resources for consistent response formatting, critical for GeoJSON compliance
- **app/Services/** - Encapsulates business logic, keeps controllers thin, makes spatial calculations testable and reusable
- **app/Repositories/** - Abstracts data access, enables easier testing, provides location for complex spatial query building
- **app/Spatial/** - Domain-specific folder for spatial operations, separates geometry concerns from business logic
- **app/Models/** - Eloquent models remain at root level per Laravel convention, spatial traits added here

## Architectural Patterns

### Pattern 1: Service Layer for Spatial Operations

**What:** Separate service classes that contain business logic and spatial calculations, keeping controllers thin and operations reusable.

**When to use:** All spatial operations (buffer zones, area calculations, intersections) and multi-step business processes.

**Trade-offs:**
- **Pros:** Testable, reusable, clear separation of concerns, easier to mock in tests
- **Cons:** More files, additional abstraction layer (may feel like overkill for simple CRUD)

**Example:**
```php
// app/Services/SpatialService.php
class SpatialService
{
    public function findParcelsWithinBuffer(
        float $latitude,
        float $longitude,
        float $distanceInMeters
    ): Collection {
        $point = new Point($latitude, $longitude);
        
        return Parcel::query()
            ->whereWithinDistance('boundary', $point, $distanceInMeters)
            ->with(['owner', 'statusHistory'])
            ->get();
    }
    
    public function calculatePolygonArea(Polygon $polygon): float
    {
        // Uses MySQL ST_Area function via spatial package
        return $polygon->area(); // Returns area in square meters
    }
    
    public function createBufferZone(
        Geometry $geometry,
        float $distanceInMeters
    ): Polygon {
        // Uses MySQL ST_Buffer function
        return $geometry->buffer($distanceInMeters);
    }
}

// Controller usage
class SpatialController extends Controller
{
    public function __construct(
        private SpatialService $spatialService
    ) {}
    
    public function bufferQuery(BufferQueryRequest $request)
    {
        $parcels = $this->spatialService->findParcelsWithinBuffer(
            $request->latitude,
            $request->longitude,
            $request->distance
        );
        
        return ParcelCollectionResource::collection($parcels);
    }
}
```

### Pattern 2: API Resources for GeoJSON Transformation

**What:** Use Laravel API Resources to transform Eloquent models into GeoJSON-compliant responses.

**When to use:** All API endpoints returning spatial data to frontend mapping libraries.

**Trade-offs:**
- **Pros:** Consistent GeoJSON format, decouples response structure from database schema, supports includes/conditional fields
- **Cons:** Learning curve for GeoJSON spec, additional transformation layer

**Example:**
```php
// app/Http/Resources/ParcelResource.php
class ParcelResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Polygon',
                'coordinates' => $this->boundary->toArray(), // Spatial package
            ],
            'properties' => [
                'id' => $this->id,
                'owner_name' => $this->owner_name,
                'status' => $this->status,
                'price_per_sqm' => $this->price_per_sqm,
                'area_sqm' => $this->area_sqm,
                'acquired_at' => $this->acquired_at?->format('Y-m-d'),
            ],
        ];
    }
}

// app/Http/Resources/ParcelCollectionResource.php
class ParcelCollectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'type' => 'FeatureCollection',
            'features' => ParcelResource::collection($this->collection),
            'metadata' => [
                'total' => $this->collection->count(),
                'bounds' => $this->calculateBounds(), // Helper for map viewport
            ],
        ];
    }
    
    private function calculateBounds(): array
    {
        // Calculate bounding box for initial map viewport
        $allCoordinates = $this->collection
            ->flatMap(fn ($parcel) => $parcel->boundary->getPoints());
        
        return [
            'min_lat' => $allCoordinates->min(fn ($p) => $p->latitude),
            'max_lat' => $allCoordinates->max(fn ($p) => $p->latitude),
            'min_lng' => $allCoordinates->min(fn ($p) => $p->longitude),
            'max_lng' => $allCoordinates->max(fn ($p) => $p->longitude),
        ];
    }
}

// Controller usage
return new ParcelCollectionResource(Parcel::all());
// Returns:
// {
//   "type": "FeatureCollection",
//   "features": [
//     {
//       "type": "Feature",
//       "geometry": { "type": "Polygon", "coordinates": [...] },
//       "properties": { "id": 1, "owner_name": "John Doe", ... }
//     }
//   ],
//   "metadata": { "total": 20, "bounds": {...} }
// }
```

### Pattern 3: Repository Pattern for Spatial Queries

**What:** Repository classes that encapsulate complex spatial query logic, keeping models clean and queries reusable.

**When to use:** Complex spatial queries (buffer zones, intersections, distance calculations) used across multiple services.

**Trade-offs:**
- **Pros:** Centralized query logic, easier to test, supports caching layer, clear data access interface
- **Cons:** Additional abstraction, may feel like overkill for simple queries

**Example:**
```php
// app/Repositories/ParcelRepository.php
class ParcelRepository
{
    public function __construct(
        private Parcel $model
    ) {}
    
    public function findWithinBuffer(
        Point $center,
        float $distanceInMeters,
        string $status = null
    ): Collection {
        $query = $this->model
            ->newQuery()
            ->whereWithinDistance('boundary', $center, $distanceInMeters);
        
        if ($status) {
            $query->where('status', $status);
        }
        
        return $query->get();
    }
    
    public function findIntersectingGeometry(
        Geometry $geometry,
        array $statuses = []
    ): Collection {
        return $this->model
            ->newQuery()
            ->whereIntersects('boundary', $geometry)
            ->when(!empty($statuses), fn ($q) => $q->whereIn('status', $statuses))
            ->get();
    }
    
    public function findByAreaRange(
        float $minSqm,
        float $maxSqm
    ): Collection {
        // Using ST_Area in where clause (database-side calculation)
        return $this->model
            ->newQuery()
            ->whereRaw("ST_Area(boundary) BETWEEN ? AND ?", [$minSqm, $maxSqm])
            ->get();
    }
    
    public function findNearbyParcels(int $parcelId, int $limit = 5): Collection
    {
        $parcel = $this->model->findOrFail($parcelId);
        
        return $this->model
            ->newQuery()
            ->where('id', '!=', $parcelId)
            ->whereClosestTo('boundary', $parcel->boundary)
            ->limit($limit)
            ->get();
    }
}

// Service usage
class ParcelService
{
    public function __construct(
        private ParcelRepository $repository
    ) {}
    
    public function getParcelsNearTollRoad(
        float $roadLat,
        float $roadLng,
        float $bufferMeters
    ): Collection {
        return $this->repository->findWithinBuffer(
            new Point($roadLat, $roadLng),
            $bufferMeters,
            status: 'target' // Only target status
        );
    }
}
```

### Pattern 4: Spatial Model with Trait Composition

**What:** Eloquent models using spatial package traits to automatically handle geometry field casting and spatial queries.

**When to use:** All models with spatial columns (POLYGON, POINT, LINESTRING).

**Trade-offs:**
- **Pros:** Clean model syntax, automatic type conversion, spatial query scopes available directly on model
- **Cons:** Package dependency, less control over SQL generation

**Example:**
```php
// app/Models/Parcel.php
use Grimzy\LaravelMysqlSpatial\Eloquent\SpatialTrait;
use Grimzy\LaravelMysqlSpatial\Types\Polygon;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class Parcel extends Model
{
    use SpatialTrait;
    
    protected $spatialFields = ['boundary', 'centroid'];
    
    protected $fillable = [
        'owner_name',
        'status',
        'price_per_sqm',
        'boundary',  // Polygon type
        'centroid',  // Point type (auto-calculated center)
    ];
    
    protected $casts = [
        'area_sqm' => 'float',
        'price_per_sqm' => 'decimal:2',
        'acquired_at' => 'date',
    ];
    
    // Spatial relationship example
    public function nearbyParcels(float $distanceMeters = 100)
    {
        return self::query()
            ->whereWithinDistance('boundary', $this->centroid, $distanceMeters)
            ->where('id', '!=', $this->id);
    }
    
    // Accessor for formatted area
    public function getFormattedAreaAttribute(): string
    {
        return number_format($this->area_sqm, 0, ',', '.') . ' m²';
    }
    
    // Mutator for automatic centroid calculation
    public function setBoundaryAttribute(Polygon $polygon)
    {
        $this->attributes['boundary'] = $polygon;
        $this->attributes['centroid'] = $polygon->centroid(); // Auto-calc center
        $this->attributes['area_sqm'] = $polygon->area();    // Auto-calc area
    }
    
    // Query scope for status
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
    
    // Query scope for area range
    public function scopeWithAreaBetween($query, float $min, float $max)
    {
        return $query->whereRaw("ST_Area(boundary) BETWEEN ? AND ?", [$min, $max]);
    }
}

// Usage examples
$parcel = Parcel::create([
    'owner_name' => 'John Doe',
    'status' => 'target',
    'price_per_sqm' => 2500000,
    'boundary' => new Polygon([
        [new Point(-6.234, 106.567), new Point(-6.234, 106.568),
         new Point(-6.235, 106.568), new Point(-6.235, 106.567)]
    ]),
    // centroid and area_sqm auto-calculated
]);

// Spatial queries
$nearby = Parcel::query()
    ->whereWithinDistance('boundary', new Point(-6.234, 106.567), 500)
    ->where('status', 'free')
    ->get();

$largeParcels = Parcel::withAreaBetween(1000, 5000)->get();
```

## Data Flow

### Request Flow

```
[Client Request: GET /api/parcels?buffer=500&lat=-6.234&lng=106.567]
    ↓
[Route: routes/api.php]
    ↓
[Controller: SpatialController@bufferQuery]
    ↓
[Validation: BufferQueryRequest]
    ↓
[Service: SpatialService->findParcelsWithinBuffer]
    ↓
[Repository: ParcelRepository->findWithinBuffer]
    ↓
[Query Builder: Spatial Scopes & MySQL ST_Distance_Sphere]
    ↓
[Model: Parcel (with spatial trait)]
    ↓
[Database: MySQL 8.0 Spatial Index Scan]
    ↓
[Response Transform: ParcelCollectionResource]
    ↓
[GeoJSON Response: FeatureCollection format]
    ↓
[Client: Leaflet/Mapbox renders map]
```

### Key Data Flows

1. **Parcel Creation Flow:**
   - POST /api/parcels with GeoJSON polygon
   - StoreParcelRequest validates geometry structure
   - ParcelService parses GeoJSON, creates Polygon object
   - Model auto-calculates centroid and area
   - MySQL stores POLYGON in spatial column
   - Returns GeoJSON Feature in response

2. **Buffer Zone Query Flow:**
   - GET /api/sppatial/buffer with center point + distance
   - SpatialService constructs Point from params
   - Repository builds ST_Distance_Sphere query
   - MySQL Spatial Index finds parcels within radius
   - ParcelCollectionResource transforms to GeoJSON FeatureCollection
   - Frontend receives ready-to-render map data

3. **GeoJSON Import Flow:**
   - POST /api/import with GeoJSON file
   - ImportRequest validates file format
   - GeoJSONService parses Features
   - For each Feature: extract properties, convert geometry to Polygon/Point
   - ParcelService creates records in transaction
   - Returns summary (imported count, errors)

4. **Area Calculation Flow:**
   - GET /api/parcels/{id} includes area_sqm in properties
   - Area stored as column (calculated on save via ST_Area)
   - No runtime calculation needed for display
   - Optional: recalculate endpoint triggers fresh ST_Area query

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 0-1k users | Single monolith, MySQL with spatial indexes on all geometry columns, eager loading for N+1 prevention |
| 1k-100k users | Add Redis caching for expensive spatial queries, database read replicas, spatial query result caching (buffer zones don't change often), consider spatial materialized views for common queries |
| 100k+ users | Split read/write spatial operations, read replicas for map display, specialized spatial database (PostGIS) if MySQL limits hit, consider tile server for map rendering instead of direct queries |

### Scaling Priorities

1. **First bottleneck:** Spatial query performance
   - **Fix:** Spatial indexes on all geometry columns (created in migration), use bounding box queries before expensive distance calculations, cache common buffer zone results in Redis (TTL 1 hour)
   
2. **Second bottleneck:** GeoJSON serialization for large datasets
   - **Fix:** Pagination (1000 parcels max per request), use GeoJSON simplification for map display (reduce coordinate precision), consider GeoJSON streaming for exports, implement tile-based queries (only return visible map area)

### Performance Optimization Patterns

**Bounding Box Pre-filter:**
```php
// Before expensive distance query, filter by bounding box
public function findWithinBuffer(Point $center, float $meters)
{
    $boundingBox = $this->calculateBoundingBox($center, $meters);
    
    return Parcel::query()
        ->whereWithin('boundary', $boundingBox) // Fast index lookup
        ->get()
        ->filter(fn ($p) => $p->distanceTo($center) <= $meters); // Precise filter
}

private function calculateBoundingBox(Point $center, float $meters): Polygon
{
    // Rough box calculation (1 degree ≈ 111km)
    $latDelta = $meters / 111000;
    $lngDelta = $meters / (111000 * cos($center->latitude));
    
    return new Polygon([
        [new Point($center->latitude + $latDelta, $center->longitude - $lngDelta)],
        [new Point($center->latitude + $latDelta, $center->longitude + $lngDelta)],
        [new Point($center->latitude - $latDelta, $center->longitude + $lngDelta)],
        [new Point($center->latitude - $latDelta, $center->longitude - $lngDelta)],
    ]);
}
```

**Spatial Index Strategy:**
```php
// Migration with spatial index
Schema::create('parcels', function (Blueprint $table) {
    $table->id();
    $table->string('owner_name');
    $table->enum('status', ['target', 'negotiating', 'freed']);
    $table->decimal('price_per_sqm', 12, 2);
    $table->polygon('boundary'); // Spatial column
    $table->point('centroid');
    $table->float('area_sqm');
    $table->timestamps();
    
    // Spatial indexes for fast location queries
    $table->spatialIndex('boundary');
    $table->spatialIndex('centroid');
    
    // Compound index for common queries
    $table->index(['status', 'area_sqm']);
});
```

## Anti-Patterns

### Anti-Pattern 1: Storing Coordinates as JSON/Text

**What people do:** Store polygon coordinates as JSON strings or comma-separated text in TEXT columns, then parse in PHP.

**Why it's wrong:** 
- No spatial queries possible (can't use ST_Distance, ST_Intersects)
- No spatial indexing (slow table scans)
- Manual coordinate parsing prone to errors
- Can't enforce valid geometry constraints
- Violates MySQL's spatial data type capabilities

**Do this instead:**
```php
// ❌ Wrong
$table->text('coordinates'); // Stores "[[lat,lng], [lat,lng]]"

// ✅ Right
$table->polygon('boundary'); // Spatial column with spatial index
$table->spatialIndex('boundary');
```

### Anti-Pattern 2: GeoJSON Parsing in Controllers

**What people do:** Put GeoJSON parsing, validation, and transformation logic directly in controller methods.

**Why it's wrong:**
- Controllers become bloated and hard to test
- Can't reuse parsing logic across endpoints
- Difficult to add validation rules
- Violates single responsibility principle

**Do this instead:**
```php
// ❌ Wrong
class ParcelController extends Controller
{
    public function store(Request $request)
    {
        $geojson = json_decode($request->input('geojson'), true);
        $coordinates = $geojson['geometry']['coordinates'];
        // ... 50 lines of parsing logic
    }
}

// ✅ Right
class ParcelController extends Controller
{
    public function __construct(
        private GeoJSONService $geoJsonService,
        private ParcelService $parcelService
    ) {}
    
    public function store(StoreParcelRequest $request)
    {
        $polygon = $this->geoJsonService->parsePolygon($request->geojson);
        $parcel = $this->parcelService->createParcel($polygon, $request->validated());
        return new ParcelResource($parcel);
    }
}
```

### Anti-Pattern 3: Skipping Spatial Indexes

**What people do:** Create spatial columns but forget to add spatial indexes, then wonder why queries are slow.

**Why it's wrong:**
- Spatial queries scan entire table (O(n) instead of O(log n))
- 1000x slower for location-based searches
- Performance degrades quadratically with data growth
- Defeats purpose of using spatial database

**Do this instead:**
```php
// ❌ Wrong
Schema::create('parcels', function (Blueprint $table) {
    $table->polygon('boundary');
    // No index!
});

// ✅ Right
Schema::create('parcels', function (Blueprint $table) {
    $table->polygon('boundary');
    $table->spatialIndex('boundary'); // Critical for performance
});
```

### Anti-Pattern 4: Calculating Area on Every Request

**What people do:** Calculate polygon area using ST_Area() in every query or in PHP on every request.

**Why it's wrong:**
- Unnecessary CPU usage (geometry doesn't change)
- Slow queries (recalculating same values)
- Can't index or sort by area efficiently

**Do this instead:**
```php
// ❌ Wrong
public function index()
{
    return Parcel::all()->map(fn ($p) => [
        'id' => $p->id,
        'area' => $p->boundary->area(), // Recalculated every request
    ]);
}

// ✅ Right
// Calculate on save, store in column
public function setBoundaryAttribute(Polygon $polygon)
{
    $this->attributes['boundary'] = $polygon;
    $this->attributes['area_sqm'] = $polygon->area(); // Store once
}

// Query uses cached value
public function index()
{
    return Parcel::select(['id', 'area_sqm'])->get();
}
```

### Anti-Pattern 5: Returning Non-Standard GeoJSON

**What people do:** Return custom JSON format instead of GeoJSON spec, requiring frontend to transform data.

**Why it's wrong:**
- Frontend can't use standard libraries (Leaflet, Mapbox expect GeoJSON)
- Reinventing the wheel, prone to bugs
- Not interoperable with other GIS tools
- Violates GeoJSON specification

**Do this instead:**
```php
// ❌ Wrong
return response()->json([
    'parcels' => [
        ['id' => 1, 'coords' => [[lat, lng], [lat, lng]]]
    ]
]);

// ✅ Right
return new ParcelCollectionResource($parcels);
// Returns valid GeoJSON FeatureCollection:
// { "type": "FeatureCollection", "features": [...] }
```

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| **MySQL 8.0** | Native spatial types via PDO | Use `matanyadaev/laravel-eloquent-spatial` package for type-safe spatial operations |
| **Aiven (MySQL)** | Standard Laravel database config | Free tier has connection limits, implement connection pooling for scale |
| **Render (Laravel)** | Environment-based config | Set spatial SRID in config, use Redis for query caching |
| **Leaflet/Mapbox Frontend** | GeoJSON API responses | No direct integration, frontend consumes API endpoints |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| **Controllers ↔ Services** | Method calls (dependency injection) | Controllers don't contain business logic, services don't handle HTTP |
| **Services ↔ Repositories** | Interface contracts (return Collections, not Query Builders) | Enables swapping data sources, easier testing |
| **Repositories ↔ Models** | Eloquent ORM with spatial traits | Models can use spatial query scopes, repositories compose complex queries |
| **All Layers ↔ GeoJSON** | API Resources handle transformation | GeoJSON format only at HTTP boundary, internal uses geometry objects |

## Build Order Recommendations

### Phase 1: Foundation (Core Architecture)
1. **Setup Laravel project** with MySQL 8.0 spatial support
2. **Install spatial package** (`matanyadaev/laravel-eloquent-spatial`)
3. **Create Parcel model** with spatial fields and traits
4. **Create migration** with spatial columns and indexes
5. **Build basic CRUD** (Controllers → Services → Repositories)
6. **Implement API Resources** for GeoJSON transformation

**Rationale:** Establish the core architecture pattern before adding complexity. Spatial package integration is critical to get right first.

### Phase 2: Spatial Operations
1. **Implement spatial queries** (buffer zones, distance calculations)
2. **Add spatial service layer** for calculations (area, buffer, intersection)
3. **Build spatial endpoints** (buffer query, nearby parcels)
4. **Add spatial indexes** if not done in Phase 1
5. **Test with seed data** in Gading Serpong area

**Rationale:** Once basic CRUD works, add spatial capabilities. Service layer isolates complex calculations.

### Phase 3: Import/Export & Optimization
1. **Build GeoJSON import** service and controller
2. **Add GeoJSON export** endpoint
3. **Implement query caching** (Redis) for expensive operations
4. **Add pagination** to all collection endpoints
5. **Performance test** with 1000+ parcels

**Rationale:** Import/export enables data loading. Optimization needed before adding more features.

### Phase 4: Advanced Features (Future)
1. **Spatial relationships** (adjacent parcels, overlapping areas)
2. **Advanced filters** (area ranges, status combinations)
3. **Bulk operations** (status updates, price changes)
4. **Analytics endpoints** (area by status, total value calculations)

**Rationale:** Build after core is stable and performance is proven.

## Laravel 12 Specific Considerations

### New in Laravel 12
- **Improved enum casting** for status fields (`enum('status', [...])`)
- **Read/Write connections** for database scaling (configure for MySQL replicas)
- **Queueable validation** for async GeoJSON import processing
- **Improved API Resource responses** with conditional fields

### Spatial Package Compatibility
- Use `matanyadaev/laravel-eloquent-spatial` (actively maintained, Laravel 12 compatible)
- Alternative: `grimzy/laravel-mysql-spatial` (older but stable)
- Both support MySQL 8.0 spatial types and SRID configuration

### Configuration Structure
```php
// config/spatial.php
return [
    'srid' => 4326, // WGS84 (GPS coordinates)
    'max_buffer_distance' => 10000, // 10km max buffer
    'default_precision' => 6, // Coordinate decimal places
    'cache_ttl' => 3600, // Cache spatial queries for 1 hour
];
```

## Sources

**LOW Confidence** (web search rate-limited, based on general Laravel patterns and spatial best practices):
- Laravel 12 Eloquent documentation (assumed standard patterns)
- MySQL 8.0 spatial data types documentation
- General Laravel service/repository pattern best practices
- GeoJSON specification (RFC 7946)

**MEDIUM Confidence** (established patterns, but specific Laravel 12 spatial integration needs verification):
- Service layer pattern for Laravel applications
- API Resource transformation pattern
- Repository pattern for data access abstraction
- Spatial index strategy for MySQL

**Gaps requiring phase-specific research:**
- Exact Laravel 12 spatial package compatibility (verify `matanyadaev/laravel-eloquent-spatial` supports Laravel 12)
- MySQL 8.0 spatial function performance benchmarks (ST_Distance_Sphere vs ST_Distance)
- GeoJSON streaming for large datasets (10000+ parcels)
- Aiven/Render free tier spatial query performance limitations

---
*Architecture research for: Laravel 12 Spatial/GIS Backend*
*Researched: 2026-04-11*
