<!-- GSD:project-start source:PROJECT.md -->
## Project

**GeoAcquire**

A Laravel 12 backend API for land acquisition and spatial analysis. Returns GeoJSON format for interactive mapping, stores spatial data (POLYGON/POINT) in MySQL, and provides area calculation and buffer zone analysis. Frontend lives in a separate repository.

**Core Value:** Spatial data must be queryable and analyzable — if a user can't get land parcels within a buffer zone or calculate accurate areas, the system fails.

### Constraints

- **Backend Only**: Frontend is separate React repository — this project builds the API
- **Laravel 12**: Must use latest Laravel, not Laravel 11
- **Spatial Data**: Must use MySQL spatial types (POLYGON, POINT), not store coordinates as text
- **GeoJSON Format**: API responses must be GeoJSON-compliant for Leaflet/Mapbox consumption
- **Free Tier Deployment**: Design for Aiven (MySQL) and Render (Laravel) free tier limitations
- **Demo-Ready**: Must have seeded dummy data for immediate portfolio demonstration
<!-- GSD:project-end -->

<!-- GSD:stack-start source:research/STACK.md -->
## Technology Stack

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
# Core spatial package
# Publish config (if available)
# Optional: League Geotools for advanced operations
# Dev dependencies for spatial testing
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
## Laravel 12 Spatial Implementation Pattern
### Migration Example
### Model with Eloquent Spatial
### GeoJSON API Response with API Resources
### Spatial Query Examples
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
# Start tinker
# Test spatial creation
# Test spatial query
# Test GeoJSON output
### Seeding Spatial Data
## Stack Patterns by Variant
- Use Materialized Views or cache results
- Consider `MBRContains()` for initial filtering before `ST_Intersects()`
- Pre-calculate buffer zones for static infrastructure
- Simplify geometries with `ST_SimplifyPreserveTopology()`
- Use `ST_AsGeoJSON()` directly in queries instead of model conversion
- Implement pagination for large datasets
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
- matanyadaev/laravel-eloquent-spatial Laravel 12 compatibility (verify on GitHub)
- MySQL 8.0 free tier spatial index limitations (verify with Aiven docs)
- Current best practices for GeoJSON API responses in Laravel 12
- Check if laravel-eloquent-spatial v2.0 officially supports Laravel 12
- Confirm MySQL 8.0 spatial function performance on free tiers
- Verify GeoJSON import/export capabilities in eloquent-spatial package
<!-- GSD:stack-end -->

<!-- GSD:conventions-start source:CONVENTIONS.md -->
## Conventions

Conventions not yet established. Will populate as patterns emerge during development.
<!-- GSD:conventions-end -->

<!-- GSD:architecture-start source:ARCHITECTURE.md -->
## Architecture

Architecture not yet mapped. Follow existing patterns found in the codebase.
<!-- GSD:architecture-end -->

<!-- GSD:skills-start source:skills/ -->
## Project Skills

No project skills found. Add skills to any of: `.claude/skills/`, `.agents/skills/`, `.cursor/skills/`, or `.github/skills/` with a `SKILL.md` index file.
<!-- GSD:skills-end -->

<!-- GSD:workflow-start source:GSD defaults -->
## GSD Workflow Enforcement

Before using Edit, Write, or other file-changing tools, start work through a GSD command so planning artifacts and execution context stay in sync.

Use these entry points:
- `/gsd-quick` for small fixes, doc updates, and ad-hoc tasks
- `/gsd-debug` for investigation and bug fixing
- `/gsd-execute-phase` for planned phase work

Do not make direct repo edits outside a GSD workflow unless the user explicitly asks to bypass it.
<!-- GSD:workflow-end -->



<!-- GSD:profile-start -->
## Developer Profile

> Profile not yet configured. Run `/gsd-profile-user` to generate your developer profile.
> This section is managed by `generate-claude-profile` -- do not edit manually.
<!-- GSD:profile-end -->
