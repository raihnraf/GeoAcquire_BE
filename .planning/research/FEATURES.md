# Feature Research

**Domain:** Land Acquisition Spatial Analysis API
**Researched:** 2026-04-11
**Confidence:** MEDIUM

## Feature Landscape

### Table Stakes (Users Expect These)

Features users assume exist. Missing these = product feels incomplete.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| **CRUD endpoints for land parcels** | Basic data management is assumed | LOW | GET/POST/PUT/DELETE /api/parcels |
| **Spatial query: within distance** | "Show parcels near X" is fundamental use case | MEDIUM | ST_Distance() or ST_Distance_Sphere() |
| **Spatial query: within bounds** | Map view requires bounding box queries | LOW | MBRContains() or ST_Within() |
| **Spatial query: contains point** | "Click map to find parcel" interaction | LOW | ST_Contains() |
| **Area calculation** | Land acquisition is about area (sqm, hectares) | LOW | ST_Area() on POLYGON |
| **GeoJSON response format** | Standard for web mapping (Leaflet, Mapbox) | MEDIUM | Must match RFC 7946 spec |
| **GeoJSON import** | Bulk data loading from GIS software | MEDIUM | POST /api/parcels/import with GeoJSON |
| **GeoJSON export** | Data portability, external analysis | LOW | GET /api/parcels?format=geojson |
| **Spatial data validation** | Prevent invalid geometries from breaking queries | MEDIUM | ST_IsValid(), closure checks |
| **Land status filtering** | Core workflow: Free/Negotiating/Target | LOW | WHERE status IN (...) |
| **Basic pagination** | Prevent memory exhaustion on large datasets | LOW | Laravel paginate() |
| **Spatial index** | Query performance on location-based searches | LOW | CREATE SPATIAL INDEX |

### Differentiators (Competitive Advantage)

Features that set the product apart. Not required, but valuable.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Buffer zone analysis** | Find parcels within X meters of infrastructure | MEDIUM | ST_Buffer() + ST_Intersects() |
| **Bulk buffer analysis** | "Show all parcels within 500m of toll road" | HIGH | Single query for multiple parcels |
| **Intersection detection** | Find overlapping parcels (data quality) | MEDIUM | ST_Intersects() self-join |
| **Area aggregation** | Total freed area by region/owner | MEDIUM | SUM(ST_Area()) GROUP BY |
| **Spatial validation API** | Separate endpoint for geometry validation | LOW | POST /api/validate-geometry |
| **Bulk operations** | Update status/price for multiple parcels | MEDIUM | PATCH /api/parcels/bulk |
| **GeoJSON streaming export** | Handle large datasets without memory issues | HIGH | Yield/Generator pattern |
| **Coordinate system conversion** | Support multiple projections (WGS84, UTM) | HIGH | ST_Transform() |
| **Multi-polygon support** | Handle disconnected land parcels | MEDIUM | ST_NumInteriorRings() validation |
| **Spatial statistics** | "Average price per sqm in buffer zone" | HIGH | Subquery with spatial join |
| **Proximity ranking** | "Sort parcels by distance to point" | MEDIUM | ORDER BY ST_Distance() |
| **Historical spatial tracking** | Audit trail for boundary changes | HIGH | Separate geometry_history table |

### Anti-Features (Commonly Requested, Often Problematic)

Features that seem good but create problems.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| **Real-time map updates** | "See changes immediately" | Requires WebSockets, complex state management, overkill for portfolio project | Polling every 30s or manual refresh |
| **Advanced GIS operations** | "Union, difference, split geometries" | PostGIS has these, MySQL doesn't. Complexity explodes for v1 | Use QGIS for editing, API for querying |
| **Multiple coordinate systems in API** | "Support WGS84, UTM, local projections" | Conversion errors, client confusion, parsing complexity | Store in WGS84, convert client-side if needed |
| **Complex spatial joins in single request** | "Get parcels + owners + transactions + boundaries" | N+1 queries, slow responses, caching hell | Separate endpoints, let frontend compose |
| **Binary geometry formats** | "WKB is faster than GeoJSON" | Clients can't use it without conversion, debugging nightmare | GeoJSON is human-readable, works everywhere |
| **Dynamic styling based on data** | "Color parcels by price range" | Mixing data and presentation, hard to test | Return raw data, let frontend style |
| **Full-text search on spatial data** | "Search by owner name AND location" | Complex query planning, hard to index | Separate text search and spatial filters |
| **Authentication per feature** | "Some endpoints public, some private" | Authorization complexity, middleware hell | API tokens or all-public for v1 |

## Feature Dependencies

```
[Spatial CRUD endpoints]
    ├──requires──> [Spatial data types (POLYGON, POINT)]
    ├──requires──> [GeoJSON serialization]
    └──enhanced by──> [Spatial index]

[Spatial query: within distance]
    ├──requires──> [Spatial CRUD endpoints]
    └──requires──> [ST_Distance() or ST_Distance_Sphere()]

[Buffer zone analysis]
    ├──requires──> [Spatial CRUD endpoints]
    ├──requires──> [ST_Buffer() function]
    └──enhanced by──> [Spatial index]

[GeoJSON import/export]
    ├──requires──> [Spatial CRUD endpoints]
    └──requires──> [GeoJSON serialization]

[Spatial data validation]
    ├──requires──> [ST_IsValid() function]
    └──enhances──> [GeoJSON import]

[Bulk operations]
    ├──requires──> [Spatial CRUD endpoints]
    └──requires──> [Transaction support]

[Area aggregation]
    ├──requires──> [Spatial CRUD endpoints]
    └──requires──> [ST_Area() function]
```

### Dependency Notes

- **Spatial CRUD endpoints requires spatial data types:** Can't store or query POLYGON/POINT without MySQL spatial types
- **Buffer zone analysis requires ST_Buffer():** MySQL 8.0+ has this, older versions don't
- **GeoJSON import requires spatial validation:** Invalid geometries break all spatial queries
- **Bulk operations require transaction support:** Either all updates succeed or all fail
- **Area aggregation requires ST_Area():** Can't calculate accurate area from raw coordinates

## MVP Definition

### Launch With (v1)

Minimum viable product — what's needed to validate the concept.

- [ ] **Spatial CRUD endpoints** — Core data management for parcels
- [ ] **Area calculation** — Land acquisition is about area measurement
- [ ] **Buffer zone analysis** — Key differentiator for land acquisition teams
- [ ] **GeoJSON response format** — Required for Leaflet/Mapbox frontend
- [ ] **Spatial query: within distance** — "Show parcels near X" is primary use case
- [ ] **GeoJSON import** — Load demo data easily, enable bulk loading
- [ ] **Land status filtering** — Core workflow: Target → Negotiating → Freed
- [ ] **Spatial index** — Query performance on location-based searches
- [ ] **Basic spatial validation** — Prevent invalid geometries
- [ ] **Seeder with dummy data** — Immediate portfolio demonstration

### Add After Validation (v1.x)

Features to add once core is working.

- [ ] **GeoJSON export** — When users need data portability
- [ ] **Bulk operations** — When manual updates become tedious
- [ ] **Spatial query: within bounds** — When map view needs bounding box filtering
- [ ] **Spatial query: contains point** — When click-to-select is needed
- [ ] **Proximity ranking** — When "sort by distance" is requested
- [ ] **Area aggregation** — When reporting total area by region/owner

### Future Consideration (v2+)

Features to defer until product-market fit is established.

- [ ] **Multi-polygon support** — When disconnected parcels are common
- [ ] **Spatial statistics** — When advanced analytics are needed
- [ ] **Coordinate system conversion** — When working with non-WGS84 data
- [ ] **Historical spatial tracking** — When audit trails become compliance requirement
- [ ] **Intersection detection** — When data quality issues emerge
- [ ] **GeoJSON streaming export** — When datasets exceed memory limits

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Spatial CRUD endpoints | HIGH | LOW | P1 |
| Area calculation | HIGH | LOW | P1 |
| Buffer zone analysis | HIGH | MEDIUM | P1 |
| GeoJSON response format | HIGH | MEDIUM | P1 |
| Spatial query: within distance | HIGH | MEDIUM | P1 |
| GeoJSON import | HIGH | MEDIUM | P1 |
| Land status filtering | HIGH | LOW | P1 |
| Spatial index | MEDIUM | LOW | P1 |
| Basic spatial validation | MEDIUM | MEDIUM | P1 |
| Seeder with dummy data | HIGH | LOW | P1 |
| GeoJSON export | MEDIUM | LOW | P2 |
| Spatial query: within bounds | MEDIUM | LOW | P2 |
| Spatial query: contains point | MEDIUM | LOW | P2 |
| Bulk operations | MEDIUM | MEDIUM | P2 |
| Proximity ranking | MEDIUM | MEDIUM | P2 |
| Area aggregation | MEDIUM | MEDIUM | P2 |
| Intersection detection | LOW | MEDIUM | P3 |
| Multi-polygon support | LOW | MEDIUM | P3 |
| Spatial statistics | LOW | HIGH | P3 |
| Coordinate system conversion | LOW | HIGH | P3 |
| Historical spatial tracking | LOW | HIGH | P3 |
| GeoJSON streaming export | LOW | HIGH | P3 |

**Priority key:**
- P1: Must have for launch
- P2: Should have, add when possible
- P3: Nice to have, future consideration

## API Endpoint Patterns

### Core CRUD Endpoints

```php
// List all parcels with optional spatial filtering
GET /api/parcels
GET /api/parcels?status=free
GET /api/parcels?bbox=minx,miny,maxx,maxy

// Get single parcel
GET /api/parcels/{id}

// Create new parcel
POST /api/parcels
{
  "owner_name": "John Doe",
  "status": "target",
  "price_per_sqm": 5000000,
  "geometry": {
    "type": "Polygon",
    "coordinates": [[...]]
  }
}

// Update parcel
PUT /api/parcels/{id}

// Delete parcel
DELETE /api/parcels/{id}
```

### Spatial Query Endpoints

```php
// Find parcels within distance of point
GET /api/parcels/near?lat=-6.23&lng=106.62&radius=500
// Returns: GeoJSON FeatureCollection

// Find parcels within bounding box (map view)
GET /api/parcels/within-bbox?minx=-6.25&miny=106.60&maxx=-6.20&maxy=106.65
// Returns: GeoJSON FeatureCollection

// Find parcels that contain point (click interaction)
GET /api/parcels/contains?lat=-6.23&lng=106.62
// Returns: GeoJSON Feature or 404

// Find parcels within buffer zone
GET /api/parcels/within-buffer?parcel_id=123&distance=500
// Returns: GeoJSON FeatureCollection of parcels within 500m
```

### Analysis Endpoints

```php
// Calculate area of parcel
GET /api/parcels/{id}/area
// Returns: {"area_sqm": 1250.5, "area_hectares": 0.125}

// Buffer zone analysis
POST /api/analysis/buffer
{
  "geometry": {...},  // GeoJSON
  "distance": 500     // meters
}
// Returns: GeoJSON FeatureCollection of intersecting parcels

// Bulk buffer analysis (e.g., "all parcels within 500m of toll road")
POST /api/analysis/buffer-bulk
{
  "source_parcels": [1, 2, 3],  // parcel IDs
  "distance": 500
}
// Returns: GeoJSON FeatureCollection

// Area aggregation
GET /api/parcels/aggregate/area?by=status
// Returns: [{"status": "free", "total_area_sqm": 50000}, ...]
```

### Data Import/Export Endpoints

```php
// GeoJSON import
POST /api/parcels/import
Content-Type: application/json
{
  "type": "FeatureCollection",
  "features": [...]
}
// Returns: {"imported": 10, "failed": 2, "errors": [...]}

// GeoJSON export
GET /api/parcels/export?format=geojson
// Returns: GeoJSON FeatureCollection download

// Validate geometry (without creating parcel)
POST /api/validate-geometry
{
  "geometry": {...}  // GeoJSON
}
// Returns: {"valid": true, "errors": []}
```

### Bulk Operations Endpoints

```php
// Bulk update status
PATCH /api/parcels/bulk
{
  "ids": [1, 2, 3, 4],
  "updates": {"status": "negotiating"}
}
// Returns: {"updated": 4}

// Bulk delete
DELETE /api/parcels/bulk
{
  "ids": [1, 2, 3]
}
// Returns: {"deleted": 3}
```

## Competitor Feature Analysis

| Feature | ArcGIS API | PostGIS API | Our Approach |
|---------|-----------|-------------|--------------|
| Spatial queries | ✓ Advanced | ✓ Advanced | ✓ Basic (within distance, bounds, contains) |
| Area calculation | ✓ | ✓ | ✓ Using ST_Area() |
| Buffer analysis | ✓ | ✓ | ✓ Using ST_Buffer() |
| GeoJSON support | ✓ | ✓ (via extension) | ✓ Native Laravel response |
| Bulk operations | ✓ | ✓ | ✓ Planned for v1.x |
| Spatial validation | ✓ | ✓ | ✓ Basic (ST_IsValid()) |
| Coordinate systems | ✓ (many) | ✓ (many) | ✗ WGS84 only (simplification) |
| Advanced topology | ✓ | ✓ | ✗ Deferred to v2 |

**Key Differentiator:** Focus on land acquisition workflow (status tracking, buffer zones) rather than generic GIS operations.

## Sources

**Confidence: MEDIUM** (Limited by search service rate limiting — findings based on project requirements, MySQL spatial documentation knowledge, and standard GIS API patterns)

- **GeoJSON Specification (RFC 7946)** — Standard format for geographic data
- **MySQL 8.0 Spatial Reference** — ST_Distance(), ST_Area(), ST_Buffer(), ST_Contains()
- **Project Requirements** — Land acquisition workflow, buffer zone analysis needs
- **Standard GIS API Patterns** — RESTful endpoints for spatial queries
- **Land Acquisition Domain Knowledge** — Status workflow, area calculations, proximity analysis

**Gaps to Address:**
- Real-world examples of similar Laravel spatial APIs (search service limited)
- Competitor analysis of actual land acquisition software (search service limited)
- Performance benchmarks for spatial queries in MySQL vs PostGIS (needs validation)

**Flags for Validation:**
- MySQL spatial function completeness compared to PostGIS (MEDIUM confidence)
- Buffer zone performance on large datasets (needs testing)
- GeoJSON serialization performance (needs benchmarking)

---
*Feature research for: Land Acquisition Spatial Analysis API*
*Researched: 2026-04-11*
