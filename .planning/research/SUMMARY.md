# Project Research Summary

**Project:** GeoAcquire - Laravel 12 Spatial Backend for Land Acquisition
**Domain:** Spatial/GIS API Backend
**Researched:** 2026-04-11
**Confidence:** MEDIUM

## Executive Summary

GeoAcquire is a Laravel 12-based spatial analysis API for land acquisition workflows. This is a geospatial backend application that combines traditional REST API patterns with specialized spatial database capabilities (MySQL 8.0 spatial extensions) to enable location-based queries, buffer zone analysis, and GeoJSON data management. Experts build these systems using native spatial database types (POLYGON, POINT) with spatial indexes for performance, layered architecture (Controllers → Services → Repositories) to isolate complex spatial calculations, and strict GeoJSON compliance for frontend compatibility.

The recommended approach is **Laravel 12 with MySQL 8.0 spatial extensions** using the `matanyadaev/laravel-eloquent-spatial` package for type-safe spatial operations, `ST_*` spatial functions for database-level calculations (area, distance, buffer zones), and Laravel API Resources for GeoJSON transformation. This stack balances modern PHP capabilities with proven spatial patterns while avoiding PostGIS overkill for basic-to-intermediate spatial needs.

**Key risks and mitigation:** (1) **SRID/CRS mismatches** can cause wrong distance calculations and buffer zones — mitigate by standardizing on SRID 4326 (WGS84) for all storage and using `ST_Distance_Sphere()` for meter-based calculations; (2) **Invalid geometry storage** breaks spatial queries — mitigate by validating with `ST_IsValid()` before insertion and auto-fixing with `ST_MakeValid()`; (3) **Missing spatial indexes** causes catastrophic performance degradation — mitigate by adding `spatialIndex()` in all migrations for geometry columns before any data is loaded. These are all Phase 1 critical items.

## Key Findings

### Recommended Stack

**Core technologies:**
- **Laravel 12** — Backend API framework with native PHP 8.2+ support, improved type safety for spatial data handling, and modern queue system
- **MySQL 8.0+** — Spatial data storage with native POLYGON/POINT types, ST_* spatial functions (ST_Area, ST_Distance_Sphere, ST_Buffer), and SPATIAL indexes for query performance
- **matanyadaev/laravel-eloquent-spatial ^2.0** — Eloquent spatial integration with Laravel 12 compatibility, GeoJSON import/export, and clean spatial query API
- **Laravel API Resources** — GeoJSON response formatting with full control over Feature/FeatureCollection structure
- **PHP 8.2+** — Runtime required by Laravel 12 with improved type system for geometry classes

**Critical version requirements:**
- MySQL 8.0+ required for spatial functions (ST_Buffer, ST_Distance_Sphere, ST_Area)
- PHP 8.2+ required by Laravel 12
- Spatial package must support Laravel 12 (verify matanyadaev/laravel-eloquent-spatial v2.0 compatibility)

### Expected Features

**Must have (table stakes):**
- Spatial CRUD endpoints for land parcels — users expect basic data management
- Spatial query: within distance — "Show parcels near X" is fundamental use case
- Spatial query: contains point — "Click map to find parcel" interaction
- Area calculation — Land acquisition is about area measurement (sqm, hectares)
- GeoJSON response format — Standard for web mapping (Leaflet, Mapbox)
- GeoJSON import — Bulk data loading from GIS software
- Land status filtering — Core workflow: Free/Negotiating/Target
- Spatial index — Query performance on location-based searches
- Basic spatial validation — Prevent invalid geometries from breaking queries

**Should have (competitive):**
- Buffer zone analysis — Find parcels within X meters of infrastructure
- Bulk buffer analysis — "Show all parcels within 500m of toll road"
- Area aggregation — Total freed area by region/owner
- Bulk operations — Update status/price for multiple parcels
- GeoJSON export — Data portability, external analysis

**Defer (v2+):**
- Multi-polygon support — Handle disconnected land parcels
- Spatial statistics — "Average price per sqm in buffer zone"
- Coordinate system conversion — Support multiple projections (WGS84, UTM)
- Historical spatial tracking — Audit trail for boundary changes
- Real-time map updates — Requires WebSockets, overkill for portfolio project

### Architecture Approach

**Recommended architecture:** Layered service-oriented architecture with clear separation between HTTP handling (Controllers), business logic (Services), data access (Repositories), and GeoJSON transformation (API Resources). This isolates complex spatial calculations, makes spatial operations testable and reusable, and ensures GeoJSON compliance at the HTTP boundary.

**Major components:**
1. **Controllers (ParcelController, SpatialController, ImportController)** — HTTP request handling, validation, response formatting
2. **Services (ParcelService, SpatialService, GeoJSONService)** — Business logic, spatial calculations (buffer zones, area), orchestration
3. **Repositories (ParcelRepository, SpatialQueryBuilder)** — Data access abstraction, spatial query building with ST_* functions
4. **Models (Parcel with spatial traits)** — Entity representation with spatial field mapping (POLYGON, POINT)
5. **API Resources (ParcelResource, ParcelCollectionResource)** — GeoJSON Feature/FeatureCollection transformation

**Data flow:** Client Request → Route → Controller (validation) → Service (business logic) → Repository (spatial query builder) → Model (spatial trait) → MySQL (spatial index scan) → API Resource (GeoJSON transform) → Client

### Critical Pitfalls

1. **SRID/CRS projection mismatches** — Coordinates stored in different spatial reference systems without transformation cause wrong distance calculations and asymmetric buffer zones. **Avoid by:** Standardizing on SRID 4326 (WGS84) for all storage, adding SRID validation in model casts, using `ST_Distance_Sphere()` instead of `ST_Distance()` for meter-based results.

2. **Invalid geometry storage** — Self-intersecting polygons, unclosed rings, or polygons with too few vertices break spatial queries (ST_Area returns 0, ST_Contains returns inconsistent results). **Avoid by:** Validating all geometries with `ST_IsValid()` before insertion, auto-fixing with `ST_MakeValid()`, rejecting polygons with < 4 vertices.

3. **Missing or misconfigured spatial indexes** — Spatial queries become progressively slower (10ms → 10 seconds) as data grows because MySQL performs full table scans with expensive geometry calculations. **Avoid by:** Always adding `spatialIndex()` in migrations for geometry columns, creating indexes before bulk imports, using bounding box queries (`MBRContains()`) before expensive `ST_Contains()`.

4. **GeoJSON format violations** — API responses claiming to be GeoJSON but violating spec (missing "type" field, wrong coordinate order) cause frontend mapping libraries to fail silently. **Avoid by:** Using dedicated GeoJSON libraries (not manual JSON building), validating output against spec, testing with real frontend (Leaflet/Mapbox) from day one, standardizing coordinate order as [longitude, latitude].

5. **Incorrect buffer zone calculations** — Buffer zones calculated in degrees instead of meters create massive zones or tiny dots depending on direction. **Avoid by:** Transforming to projected coordinate system (e.g., Web Mercator 3857) before buffering, applying buffer in meters, transforming back to WGS84, testing buffer symmetry (should be circular not oval).

## Implications for Roadmap

Based on research synthesis, suggested phase structure:

### Phase 1: Foundation (Spatial Core)
**Rationale:** Spatial database foundation must be correct from day one. Wrong SRID data, missing indexes, or invalid geometries are extremely difficult to fix retrospectively. Research flags 7 critical pitfalls that must be addressed in this phase.

**Delivers:**
- Laravel 12 project with MySQL 8.0 spatial support
- Parcel model with spatial fields (boundary: POLYGON, centroid: POINT)
- Migration with spatial columns and indexes
- Basic CRUD endpoints (Controllers → Services → Repositories)
- API Resources for GeoJSON Feature/FeatureCollection transformation
- Spatial data validation (ST_IsValid, SRID enforcement)
- Seeder with Gading Serpong dummy data

**Addresses:** All table stakes features (spatial CRUD, area calculation, GeoJSON response format, spatial validation, basic spatial queries)

**Avoids:** SRID mismatches, invalid geometry storage, missing spatial indexes, GeoJSON format violations, incorrect buffer calculations

**Stack usage:** Laravel 12, MySQL 8.0, matanyadaev/laravel-eloquent-spatial, PHP 8.2

### Phase 2: Spatial Operations
**Rationale:** Once spatial CRUD works and data validation is solid, add advanced spatial capabilities. Service layer isolates complex calculations, making them testable and reusable.

**Delivers:**
- Spatial query endpoints (within distance, within bounds, contains point)
- Buffer zone analysis (single parcel and bulk)
- SpatialService for calculations (area, buffer, intersection)
- Spatial query optimization (bounding box pre-filter + ST_Distance_Sphere)
- Area aggregation endpoints (total area by status/region)
- Proximity ranking (sort parcels by distance to point)

**Addresses:** Differentiator features (buffer zone analysis, area aggregation, proximity ranking)

**Uses:** MySQL ST_* functions (ST_Buffer, ST_Distance_Sphere, ST_Area), spatial indexes from Phase 1

**Implements:** Architecture Pattern 1 (Service Layer for Spatial Operations), Pattern 3 (Repository for Spatial Queries)

### Phase 3: Data Import/Export & Optimization
**Rationale:** Import/export enables data loading and portability. Optimization needed before adding more features or scaling to larger datasets.

**Delivers:**
- GeoJSON import service (bulk loading with validation)
- GeoJSON export endpoint (with spatial filtering)
- Bulk operations (PATCH /api/parcels/bulk for status updates)
- Query caching (Redis) for expensive spatial operations
- Pagination for all collection endpoints
- Spatial filtering (bbox parameter for map views)

**Addresses:** GeoJSON import/export, bulk operations, performance optimization

**Uses:** GeoJSONService, queue jobs for large imports, Redis caching

**Avoids:** Bulk import failures, massive API responses

### Phase 4: Advanced Features (Future)
**Rationale:** Build after core is stable and performance is proven. These are nice-to-have features for product-market fit validation.

**Delivers:**
- Multi-polygon support (disconnected land parcels)
- Spatial statistics (average price per sqm in buffer zone)
- Intersection detection (find overlapping parcels)
- Historical spatial tracking (audit trail for boundary changes)

**Addresses:** Future consideration features from research

**Uses:** Advanced spatial functions (ST_NumInteriorRings, ST_Intersects self-join)

### Phase Ordering Rationale

- **Foundation first**: Spatial database patterns (SRID, indexes, validation) are architectural — changing them later requires data migration and potential data loss
- **Spatial operations second**: Depends on solid foundation (spatial columns, indexes, validation) and enables core value proposition (buffer zone analysis)
- **Import/export third**: Depends on spatial operations being stable, enables data loading for testing and demo
- **Optimization in Phase 3**: Performance patterns emerge after real data is loaded, easier to optimize when you have actual query patterns
- **Advanced features last**: Defer until core workflow is validated and proven

This order follows **dependency chains** from research:
- Spatial CRUD → requires spatial data types + GeoJSON serialization
- Buffer zone analysis → requires ST_Buffer() + spatial index
- GeoJSON import → requires spatial validation (prevents corrupting database)
- Bulk operations → requires transaction support

### Research Flags

**Phases likely needing deeper research during planning:**
- **Phase 2 (Spatial Operations):** Buffer zone performance on large datasets — research flags indicate needs testing with 1000+ parcels, may need optimization strategies (materialized views, tile-based approach)
- **Phase 3 (Import/Export):** Bulk import patterns for spatial data — research flags indicate high risk of failures, memory exhaustion, transaction deadlocks. Needs `/gsd-research-phase` for batch processing strategies.

**Phases with standard patterns (skip research-phase):**
- **Phase 1 (Foundation):** Laravel 12 spatial package integration is well-documented, MySQL 8.0 spatial functions are standard, GeoJSON spec is established. Can proceed directly to planning.
- **Phase 4 (Advanced Features):** Defer until v2+, don't research now.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | MEDIUM | Core choices (Laravel 12, MySQL 8.0) are sound, but matanyadaev/laravel-eloquent-spatial v2.0 Laravel 12 compatibility needs verification. Web search was rate-limited. |
| Features | MEDIUM | Table stakes and differentiators well-defined based on land acquisition domain knowledge. GeoJSON compliance is standard. Some feature complexity estimates may need adjustment. |
| Architecture | MEDIUM | Layered service-oriented architecture is proven pattern for Laravel. Spatial model with trait composition is standard. Specific Laravel 12 spatial package integration needs verification. |
| Pitfalls | HIGH | SRID mismatches, invalid geometries, missing indexes, and GeoJSON violations are well-documented in spatial database literature. Patterns are established and consequences are clear. |

**Overall confidence:** MEDIUM

### Gaps to Address

- **Laravel 12 spatial package compatibility:** Verify matanyadaev/laravel-eloquent-spatial v2.0 officially supports Laravel 12. Check GitHub repository for Laravel 12 testing or issues.
- **MySQL 8.0 free tier spatial limitations:** Confirm Aiven MySQL free tier supports spatial indexes and ST_* functions without restrictions. Check Aiven documentation.
- **Buffer zone performance benchmarks:** Test ST_Buffer performance on datasets of 1000+ parcels. Research flags indicate this may be a bottleneck — may need optimization strategies (pre-calculation, materialized views).
- **GeoJSON streaming for large exports:** If export endpoint needs to handle 10,000+ parcels, research streaming approach (Yield/Generator pattern) to avoid memory exhaustion.

**How to handle gaps:**
- Package compatibility: Verify during Phase 1 setup by running `composer require` and checking for Laravel 12 compatibility warnings
- Free tier limitations: Test spatial queries on Aiven free tier during Phase 1, monitor for spatial index size limits
- Buffer performance: Add performance testing to Phase 2 acceptance criteria — if queries exceed 1 second, implement optimization strategies
- Large exports: Start with simple export in Phase 3, add streaming if memory issues emerge during testing

## Sources

### Primary (HIGH confidence)
- **MySQL 8.0 Reference Manual** — Spatial Functions (ST_Area, ST_Distance_Sphere, ST_Buffer, ST_Contains, ST_IsValid)
- **GeoJSON Specification (RFC 7946)** — Standard format for geographic data, coordinate order [longitude, latitude]
- **Laravel 12 Documentation** — Eloquent models, API Resources, migrations, validation
- **Laravel API Resources Documentation** — JSON transformation patterns

### Secondary (MEDIUM confidence)
- **matanyadaev/laravel-eloquent-spatial GitHub repository** — Package features, Laravel 12 compatibility claims (needs verification)
- **grimzy/laravel-mysql-spatial package** — Alternative spatial package (backup option)
- **Established spatial database patterns** — SRID handling, spatial indexing, geometry validation (PostGIS/MySQL spatial community knowledge)
- **GIS Stack Exchange** — Common pitfalls for spatial queries in MySQL

### Tertiary (LOW confidence)
- **Web search results (rate-limited)** — Limited access to current best practices and real-world Laravel spatial API examples
- **Competitor analysis** — Limited by search service, based on standard GIS API patterns rather than specific land acquisition software analysis

---
*Research completed: 2026-04-11*
*Ready for roadmap: yes*
