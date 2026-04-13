# Requirements: GeoAcquire

**Defined:** 2026-04-11
**Core Value:** Spatial data must be queryable and analyzable — if a user can't get land parcels within a buffer zone or calculate accurate areas, the system fails.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Data Management (DATA)

- [x] **DATA-01**: API can create land parcel with GeoJSON geometry via POST /api/parcels
- [x] **DATA-02**: API can retrieve single parcel via GET /api/parcels/{id} returning GeoJSON Feature
- [x] **DATA-03**: API can update parcel via PUT /api/parcels/{id}
- [x] **DATA-04**: API can delete parcel via DELETE /api/parcels/{id}
- [x] **DATA-05**: API can list all parcels via GET /api/parcels returning GeoJSON FeatureCollection
- [x] **DATA-06**: API can import bulk GeoJSON via POST /api/parcels/import with validation feedback

### Spatial Queries (SPATIAL)

- [x] **SPAT-01**: API can find parcels within bounding box via GET /api/parcels?bbox=minx,miny,maxx,maxy
- [x] **SPAT-02**: API can find parcels within buffer zone of point via POST /api/analysis/buffer
- [x] **SPAT-03**: API can find parcels within buffer zone of parcel via GET /api/parcels/{id}/buffer?distance=500

### Analysis (ANALYSIS)

- [x] **ANAL-01**: API can calculate parcel area via GET /api/parcels/{id}/area returning sqm and hectares
- [x] **ANAL-02**: API can aggregate total area by status via GET /api/parcels/aggregate/area?by=status
- [x] **ANAL-03**: API returns area in all parcel GeoJSON responses as property

### Foundation (FOUND)

- [x] **FOUND-01**: Database uses POLYGON type for parcel boundaries with SRID 4326 (WGS84)
- [x] **FOUND-02**: Database uses POINT type for parcel centroids
- [x] **FOUND-03**: Spatial indexes created on all geometry columns
- [x] **FOUND-04**: Geometry validation (ST_IsValid) runs before insert/update
- [x] **FOUND-05**: API filters parcels by status (target, negotiating, free) via ?status= parameter
- [x] **FOUND-06**: Laravel 12 project structure with layered architecture (Controllers → Services → Repositories)
- [x] **FOUND-07**: API Resources transform Eloquent models to GeoJSON Feature/FeatureCollection format
- [x] **FOUND-08**: Seeder populates database with 10-20 dummy parcels in Gading Serpong area

### API Response Format (API)

- [x] **API-01**: All parcel endpoints return valid GeoJSON (RFC 7946)
- [x] **API-02**: GeoJSON coordinates use correct order [longitude, latitude]
- [x] **API-03**: GeoJSON Feature includes properties: id, owner_name, status, price_per_sqm, area_sqm
- [x] **API-04**: Error responses return consistent JSON format with message field

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Spatial Queries Extended

- **SPAT-10**: API can find parcels within distance of point via GET /api/parcels/near?lat=&lng=&radius=
- **SPAT-11**: API can find parcels containing point via GET /api/parcels/contains?lat=&lng=

### Data Export

- **DATA-10**: API can export all parcels as GeoJSON download via GET /api/parcels/export

### Analysis Extended

- **ANAL-10**: API can rank parcels by proximity to point
- **ANAL-11**: API can detect overlapping parcels (intersection detection)

### Bulk Operations

- **DATA-20**: API can bulk update parcel status via PATCH /api/parcels/bulk
- **DATA-21**: API can bulk delete parcels via DELETE /api/parcels/bulk

## Out of Scope

| Feature | Reason |
|---------|--------|
| User authentication | v1 is API-first, public read access for portfolio demo |
| Real-time map updates | Requires WebSockets, overkill for portfolio project |
| Advanced GIS operations (union, difference, split) | MySQL spatial lacks these features; use QGIS for editing |
| Multiple coordinate systems | WGS84 (SRID 4326) only; conversion complexity not justified for v1 |
| Multi-polygon support | Single parcel = single polygon; disconnected parcels defer to v2 |
| Spatial statistics (avg price in buffer) | Defer until core workflow validated |
| Historical spatial tracking | Audit trails not needed for portfolio demo |
| Frontend UI | Separate React repository |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| FOUND-01 | Phase 1 | **Complete** |
| FOUND-02 | Phase 1 | **Complete** |
| FOUND-03 | Phase 1 | **Complete** |
| FOUND-04 | Phase 1 | **Complete** |
| FOUND-06 | Phase 1 | **Complete** |
| FOUND-07 | Phase 1 | **Complete** |
| FOUND-08 | Phase 1 | **Complete** |
| DATA-01 | Phase 1 | **Complete** |
| DATA-02 | Phase 1 | **Complete** |
| DATA-03 | Phase 1 | **Complete** |
| DATA-04 | Phase 1 | **Complete** |
| DATA-05 | Phase 1 | **Complete** |
| API-01 | Phase 1 | **Complete** |
| API-02 | Phase 1 | **Complete** |
| API-03 | Phase 1 | **Complete** |
| API-04 | Phase 1 | **Complete** |
| ANAL-01 | Phase 1 | **Complete** |
| ANAL-03 | Phase 1 | **Complete** |
| DATA-06 | Phase 2 | Complete |
| SPAT-01 | Phase 2 | Complete |
| FOUND-05 | Phase 2 | Complete |
| SPAT-02 | Phase 2 | Complete |
| SPAT-03 | Phase 2 | Complete |
| ANAL-02 | Phase 2 | Complete |

**Coverage:**
- v1 requirements: 25 total
- Phase 1 complete: 18/18 ✅
- Phase 2 complete: 6/6 ✅
- All requirements complete! 🎉

---
*Requirements defined: 2026-04-11*
*Last updated: 2026-04-11 after initial definition*
