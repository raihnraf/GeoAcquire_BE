# GeoAcquire Roadmap

**Created:** 2026-04-11
**Granularity:** Standard
**Phases:** 2

## Phases

- [x] **Phase 1: Spatial Foundation** - Database setup, basic CRUD operations, and GeoJSON API format
- [ ] **Phase 2: Spatial Analysis** - Buffer zones, spatial queries, and area aggregation

## Phase Details

### Phase 1: Spatial Foundation

**Goal:** API can store, retrieve, and validate land parcels as spatial data returning valid GeoJSON

**Depends on:** Nothing

**Requirements:**
- FOUND-01, FOUND-02, FOUND-03, FOUND-04, FOUND-06, FOUND-07, FOUND-08
- DATA-01, DATA-02, DATA-03, DATA-04, DATA-05
- API-01, API-02, API-03, API-04
- ANAL-01, ANAL-03

**Success Criteria** (what must be TRUE):
1. API can create a land parcel via POST /api/parcels with GeoJSON geometry and stores it as POLYGON in MySQL
2. API can retrieve a parcel via GET /api/parcels/{id} returning valid GeoJSON Feature with correct coordinate order [longitude, latitude]
3. API can list all parcels via GET /api/parcels returning valid GeoJSON FeatureCollection
4. API can update and delete parcels via PUT/PATCH and DELETE endpoints
5. Database has spatial indexes on geometry columns and validates geometry before insertion (ST_IsValid)
6. Seeder populates database with 10-20 dummy parcels in Gading Serpong area
7. API returns calculated area (sqm) in all parcel GeoJSON responses

**Plans:**
- [x] 01-01-PLAN.md — Spatial foundation implementation

### Phase 2: Spatial Analysis

**Goal:** API can perform spatial queries, buffer zone analysis, and aggregate statistics

**Depends on:** Phase 1 (requires spatial data storage and GeoJSON format)

**Requirements:**
- SPAT-01, SPAT-02, SPAT-03
- FOUND-05
- DATA-06
- ANAL-02

**Success Criteria** (what must be TRUE):
1. API can find parcels within a bounding box via GET /api/parcels?bbox=minx,miny,maxx,maxy
2. API can find parcels within a buffer zone of a point via POST /api/analysis/buffer
3. API can find parcels within a buffer zone of a parcel via GET /api/parcels/{id}/buffer?distance=500
4. API can filter parcels by status (target, negotiating, free) via ?status= parameter
5. API can aggregate total area by status via GET /api/parcels/aggregate/area?by=status
6. API can import bulk GeoJSON via POST /api/parcels/import with validation feedback

**Plans:**
- [x] 02-01-PLAN.md — Spatial query endpoints (bounding box, point buffer, status filter)
- [x] 02-02-PLAN.md — Parcel buffer and aggregation endpoints
- [x] 02-03-PLAN.md — Bulk GeoJSON import endpoint
- [x] 02-04-PLAN.md — Fix bulk import partial success (Gap 1 closure)
- [x] 02-05-PLAN.md — Add bounding box coordinate range validation (Gap 2 closure)

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Spatial Foundation | 1/1 | **Complete** | 18/18 requirements, 14 tests passing |
| 2. Spatial Analysis | 3/5 | In Progress | 5/6 requirements verified, 2 gaps found |

## Gap Closure Plans

### Phase 02 Gap Closure

**Gaps Found:** 2 (from 02-VERIFICATION.md)

| Plan | Gap Addressed | Description | Status |
|------|---------------|-------------|--------|
| 02-04-PLAN.md | Gap 1: Bulk Import Partial Success | Remove geometry type validation from BulkImportRequest to enable service-layer per-feature validation | Pending |
| 02-05-PLAN.md | Gap 2: Bounding Box Coordinate Ranges | Add lng (-180 to 180), lat (-90 to 90), and min < max validation to ParcelController::index() | Pending |

---

**Last updated:** 2026-04-11
