# Technology Stack

**Project:** GeoAcquire - Laravel 12 Spatial Backend
**Researched:** 2026-04-11
**Overall confidence:** MEDIUM

## Recommended Stack

### Core Framework
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel | 12.0 | Backend API framework | Latest Laravel with native PHP 8.2+ support, improved queue system, and better type safety for spatial data handling |
| MySQL | 8.0+ | Spatial data storage | Native spatial types (POLYGON, POINT), ST_* spatial functions, SPATIAL indexes for performance |
| PHP | 8.2+ | Runtime | Required by Laravel 12, improved type system for geometry classes |

### Spatial Libraries
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| matanyadaev/laravel-eloquent-spatial | ^2.0 | Eloquent spatial integration | Modern, Laravel 12-compatible, supports MySQL 8.0 spatial types, GeoJSON import/export, ST_* functions |
| grimzy/laravel-mysql-spatial | ^4.0 (alternative) | Legacy spatial support | Backup option if eloquent-spatial has issues, mature package but less actively maintained |

### GeoJSON & Data Handling
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel API Resources | Native | GeoJSON response formatting | Built-in Laravel feature for transforming models to JSON/GeoJSON, full control over response structure |
| league/geotools | ^1.0 (optional) | Spatial calculations | If native MySQL ST_* functions insufficient, provides PHP-level spatial operations |

### Development Tools
| Technology | Version | Purpose | Why |
|------------|---------|---------|-----|
| Laravel Tinker | ^2.10 | Interactive spatial testing | Test spatial queries and GeoJSON output in console before building API |
| Laravel Sail | ^1.41 | Local Docker environment | Consistent MySQL 8.0 configuration with spatial extensions enabled |
| PHPUnit | ^11.5 | Spatial query testing | Verify spatial relationships, area calculations, and buffer zones |

## Alternatives Considered

| Category | Recommended | Alternative | Why Not |
|----------|-------------|-------------|---------|
| Spatial Library | matanyadaev/laravel-eloquent-spatial | grimzy/laravel-mysql-spatial | eloquent-spatial is more actively maintained, better Laravel 12 support, cleaner API |
| GeoJSON Format | API Resources + custom GeoJSON layer | Dedicated GeoJSON package | Laravel's API Resources provide full control over GeoJSON structure, no need for extra dependency |
| Spatial Calculations | MySQL ST_* functions | PHP-level calculations (geoPHP) | Database-level is faster, uses indexes, handles large datasets better |

## Installation

```bash
# Core spatial package
composer require matanyadaev/laravel-eloquent-spatial

# Publish config (if available)
php artisan vendor:publish --tag="eloquent-spatial-config"

# Optional: League Geotools for advanced operations
composer require league/geotools

# Dev dependencies for spatial testing
composer require --dev laravel/tinker
```

## MySQL 8.0 Spatial Functions Reference

### Core Spatial Functions for Land Acquisition

| Function | Purpose | Example Use Case |
|----------|---------|------------------|
| `ST_GeomFromText('POLYGON(...)')` | Create geometry from WKT | Storing land parcel boundaries from user input |
| `ST_AsGeoJSON(geometry)` | Convert to GeoJSON | API responses for frontend mapping |
| `ST_Distance(geom1, geom2)` | Calculate distance between geometries | Find parcels near specific point |
| `ST_Distance_Sphere(geom1, geom2)` | Spherical distance (meters) | Accurate distance for large areas |
| `ST_Area(geometry)` | Calculate polygon area | Total land area in square meters |
| `ST_Buffer(geometry, distance)` | Create buffer zone | Find parcels within X meters of road/facility |
| `ST_Contains(geom1, geom2)` | Point in polygon check | Verify if coordinate is within land parcel |
| `ST_Intersects(geom1, geom2)` | Geometries intersection | Find overlapping parcels |
| `ST_Within(geom1, geom2)` | Containment check | Verify parcel is within zone |
| `ST_Centroid(geometry)` | Get center point | Display labels at parcel center |
| `ST_Envelope(geometry)` | Get bounding rectangle | Quick spatial filtering with MBRContains |

### Spatial Indexing

```sql
-- Create spatial index on geometry column
CREATE SPATIAL INDEX idx_land_parcels_spatial ON land_parcels(boundary);

-- For InnoDB tables (required for spatial indexes in MySQL 8.0)
ALTER TABLE land_parcels ENGINE=InnoDB;

-- Query with spatial index usage
SELECT * FROM land_parcels
WHERE ST_Contains(boundary, ST_GeomFromText('POINT(106.123 -6.456)'));
```

## Laravel 12 Spatial Implementation Pattern

### Migration Example
```php
Schema::create('land_parcels', function (Blueprint $table) {
    $table->id();
    $table->string('owner_name');
    $table->enum('status', ['free', 'negotiating', 'target']);
    $table->decimal('price_per_sqm', 12, 2);
    $table->geometry('boundary'); // POLYGON type
    $table->point('centroid')->nullable(); // POINT type for quick lookups
    $table->decimal('area_sqm', 15, 2)->nullable();
    $table->timestamps();

    // Spatial indexes for performance
    $table->spatialIndex('boundary');
    $table->spatialIndex('centroid');
});
```

### Model with Eloquent Spatial
```php
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use MatanYadaev\EloquentSpatial\Objects\Point;

class LandParcel extends Model
{
    protected $casts = [
        'boundary' => Polygon::class,
        'centroid' => Point::class,
    ];

    // Auto-calculate area on save
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($parcel) {
            if ($parcel->boundary) {
                $parcel->area_sqm = DB::raw(
                    "ST_Area(boundary)"
                );
                $parcel->centroid = DB::raw(
                    "ST_Centroid(boundary)"
                );
            }
        });
    }
}
```

### GeoJSON API Response with API Resources
```php
use Illuminate\Http\Resources\Json\JsonResource;

class LandParcelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'type' => 'Feature',
            'geometry' => json_decode($this->boundary->toGeoJson()),
            'properties' => [
                'id' => $this->id,
                'owner_name' => $this->owner_name,
                'status' => $this->status,
                'price_per_sqm' => $this->price_per_sqm,
                'area_sqm' => $this->area_sqm,
                'color' => $this->getStatusColor(),
            ],
        ];
    }

    private function getStatusColor(): string
    {
        return match($this->status) {
            'free' => '#10b981',      // Green
            'negotiating' => '#f59e0b', // Yellow
            'target' => '#ef4444',     // Red
        };
    }
}
```

### Spatial Query Examples
```php
// Find parcels within buffer zone
$parcelsNearRoad = LandParcel::whereRaw(
    'ST_Intersects(boundary, ST_Buffer(ST_GeomFromText(?), ?))',
    ["POINT({$lng} {$lat})", $distanceInMeters]
)->get();

// Find parcels containing a point
$parcelsContainingPoint = LandParcel::whereRaw(
    'ST_Contains(boundary, ST_GeomFromText(?))',
    ["POINT({$lng} {$lat})"]
)->get();

// Calculate total area within zone
$totalArea = DB::table('land_parcels')
    ->whereRaw('ST_Within(boundary, ST_Buffer(ST_GeomFromText(?), ?))', [...])
    ->selectRaw('SUM(ST_Area(boundary)) as total_area')
    ->value('total_area');
```

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Storing coordinates as TEXT/JSON columns | No spatial indexing, slow queries, no validation | MySQL GEOMETRY types with spatial indexes |
| Calculating areas in PHP | Inefficient, doesn't use database indexes | MySQL `ST_Area()` function |
| Creating custom GeoJSON formatters | Error-prone, non-standard | Laravel API Resources with proper GeoJSON structure |
| PostGIS for this project | Overkill for basic spatial needs, harder to deploy on free tiers | MySQL 8.0 native spatial (sufficient for requirements) |
| Deprecated spatial packages | May not support Laravel 12 or MySQL 8.0 ST_* functions | matanyadaev/laravel-eloquent-spatial (actively maintained) |

## Development Workflow

### Testing Spatial Queries with Tinker
```bash
# Start tinker
php artisan tinker

# Test spatial creation
$parcel = new LandParcel([
    'boundary' => Polygon::fromWkt('POLYGON((...))'),
    'owner_name' => 'Test Owner',
    'status' => 'free'
]);
$parcel->save();

# Test spatial query
LandParcel::whereRaw('ST_Contains(boundary, ST_GeomFromText(?))', ["POINT(106.123 -6.456)"])->get();

# Test GeoJSON output
(new LandParcelResource($parcel))->toResponse(request())->getContent();
```

### Seeding Spatial Data
```php
// Use real GeoJSON for Gading Serpong area
public function run()
{
    $geojsonData = json_decode(file_get_contents(database_path('seeders/gading_serpong_parcels.json')), true);

    foreach ($geojsonData['features'] as $feature) {
        LandParcel::create([
            'boundary' => Polygon::fromJson(json_encode($feature['geometry'])),
            'owner_name' => fake()->name(),
            'status' => fake()->randomElement(['free', 'negotiating', 'target']),
            'price_per_sqm' => fake()->numberBetween(5000000, 15000000),
        ]);
    }
}
```

## Stack Patterns by Variant

**If buffer zones are performance bottleneck:**
- Use Materialized Views or cache results
- Consider `MBRContains()` for initial filtering before `ST_Intersects()`
- Pre-calculate buffer zones for static infrastructure

**If GeoJSON responses are slow:**
- Simplify geometries with `ST_SimplifyPreserveTopology()`
- Use `ST_AsGeoJSON()` directly in queries instead of model conversion
- Implement pagination for large datasets

**If free tier deployment (Aiven/Render):**
- Optimize spatial queries to minimize memory usage
- Use connection pooling for MySQL
- Consider read replicas for spatial queries (if scale needed)

## Version Compatibility

| Package | Laravel 12 | PHP 8.2 | MySQL 8.0 | Notes |
|---------|------------|---------|-----------|-------|
| matanyadaev/laravel-eloquent-spatial ^2.0 | ✅ Compatible | ✅ Required | ✅ Required | Active maintenance, modern architecture |
| grimzy/laravel-mysql-spatial ^4.0 | ⚠️ May need testing | ✅ Compatible | ✅ Compatible | Less active, use as backup |
| league/geotools ^1.0 | ✅ Compatible | ✅ Compatible | Not required | Optional, only if MySQL functions insufficient |

## Deployment Considerations for Free Tiers

### Aiven MySQL (Free Tier)
- Verify spatial extensions enabled (default on MySQL 8.0)
- Monitor spatial index size (counts toward storage limits)
- Use InnoDB engine (required for spatial indexes)

### Render (Laravel Free Tier)
- Optimize autoloader: `composer install --optimize-autoloader`
- Cache config: `php artisan config:cache`
- Cache routes: `php artisan route:cache`
- Minimize spatial query complexity for cold starts

## Sources

- **LOW CONFIDENCE** (Web search rate-limited, based on training data):
  - matanyadaev/laravel-eloquent-spatial GitHub repository
  - MySQL 8.0 Reference Manual - Spatial Functions
  - Laravel 12 Documentation (upgrade guide)
  - Laravel API Resources Documentation

**Gaps requiring validation:**
- matanyadaev/laravel-eloquent-spatial Laravel 12 compatibility (verify on GitHub)
- MySQL 8.0 free tier spatial index limitations (verify with Aiven docs)
- Current best practices for GeoJSON API responses in Laravel 12

**Verification needed:**
- Check if laravel-eloquent-spatial v2.0 officially supports Laravel 12
- Confirm MySQL 8.0 spatial function performance on free tiers
- Verify GeoJSON import/export capabilities in eloquent-spatial package

---
*Stack research for: GeoAcquire Laravel 12 Spatial Backend*
*Researched: 2026-04-11*
