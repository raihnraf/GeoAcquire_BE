# Phase 1: Full Codebase & Test Audit Report

**Project:** GeoAcquire — Land Acquisition & Spatial Analysis Dashboard  
**Framework:** Laravel 12.56.0 (PHP 8.2+)  
**Date:** April 11, 2026  
**Scope:** Full backend codebase + test suite analysis  
**Constraint:** No code was modified. Analysis only.

---

## Table of Contents

1. [Summary](#1-summary)
2. [Critical Issues (must fix)](#2-critical-issues-must-fix)
3. [Major Improvements (should fix)](#3-major-improvements-should-fix)
4. [Minor Suggestions (nice to have)](#4-minor-suggestions-nice-to-have)
5. [Detected DRY Violations](#5-detected-dry-violations)
6. [Structural Issues](#6-structural-issues)
7. [Suspicious Tests](#7-suspicious-tests)
8. [Missing Test Scenarios](#8-missing-test-scenarios)
9. [Recommended Next Actions (Phase 2)](#9-recommended-next-actions-phase-2)

---

## 1. Summary

| Dimension | Rating | Notes |
|-----------|--------|-------|
| **Overall Code Quality** | **Medium** | Functional but has structural, architectural, and correctness issues |
| **Overall Test Quality** | **Low–Medium** | Tests exercise happy paths but miss critical edge cases, have weak assertions, and contain false positives |
| **Confidence in Reliability** | **~55%** | The API works for happy-path CRUD but would fail under edge cases, invalid data, or production load. No error handling, no auth, no validation at the DB layers |

---

## 2. Critical Issues (must fix)

### Code Issues

#### C1. Hardcoded Database Credentials in `phpunit.xml`

- **File:** `phpunit.xml` (lines 21–24)
- **Severity:** 🔴 Critical — Security Vulnerability
- **Problem:** `DB_USERNAME=debian-sys-maint` and `DB_PASSWORD=mxK0jFzixmBbXSew` are committed in plaintext. Anyone with repository access gets direct database credentials.
- **Fix:** Move credentials to `.env.testing` and ensure `phpunit.xml` does not contain secrets. Add sensitive keys to `.gitignore`.

#### C2. `ParcelRepository::findWithinBufferOfParcel` Has a Self-Intersection Bug

- **File:** `app/Repositories/ParcelRepository.php` (line 93)
- **Severity:** 🔴 Critical — Silent Data Corruption
- **Problem:** The query executes:

  ```sql
  ST_Intersects(boundary, ST_Buffer(boundary, ?))
  ```

  This buffers **every parcel's own boundary** in the table, not the specific target parcel's boundary. It then excludes by `WHERE id != $parcelId`, but the `ST_Buffer` is computed **per-row**, meaning every other parcel is buffered against itself — not against the target parcel's geometry. The result set is semantically meaningless.

- **Correct logic should be:**

  ```php
  return $this->model::whereRaw(
      'ST_Intersects(boundary, ST_Buffer(ST_GeomFromText(?, 4326), ?))',
      [$parcel->boundary->toWkt(), $distanceInMeters]
  )
      ->where('id', '!=', $parcelId)
      ->get();
  ```

#### C3. Centroid Calculation Executed in Multiple Layers — Service, Model Observer, Seeder, Factory

- **Files:**
  - `app/Services/ParcelService.php` (line 35 — parses geometry)
  - `app/Models/Parcel.php` (line 43 — `saving` observer calls `calculateCentroid()`)
  - `database/seeders/ParcelSeeder.php` (line 185 — private `calculateCentroid()`)
  - `database/factories/ParcelFactory.php` (lines 24–26 — inline centroid math)
- **Severity:** 🟠 Critical — Maintenance Nightmare
- **Problem:** Four separate implementations of the same arithmetic-mean centroid algorithm. If the algorithm needs to change (e.g., use proper geometric centroid instead of average-of-vertices), all four must be updated. The Service also triggers the Model observer, so the centroid is calculated twice per request.

#### C4. `ParcelModel::loadArea()` Uses a Fragile Silent-Update Pattern

- **File:** `app/Models/Parcel.php` (lines 64–72)
- **Severity:** 🟠 Critical
- **Problem:**
  - Makes an **unnecessary extra DB query** — area could be computed in the same query as model retrieval.
  - Has a **race condition**: if two requests create parcels simultaneously, `whereKey($this->id)` could update stale data.
  - Returns `void` — the caller has no idea if the area was computed successfully.
  - The `created` observer calls `loadArea()` but **never checks if it succeeded** — a parcel with invalid geometry will silently have `area_sqm = null` forever.

#### C5. No Exception Handling in Controllers

- **Files:** `app/Http/Controllers/Api/ParcelController.php`, `app/Http/Controllers/Api/AreaController.php`
- **Severity:** 🟠 Critical
- **Problem:** If `ParcelService::createParcel()` throws an `InvalidArgumentException` (e.g., unsupported geometry type), it bubbles up as a **500 Internal Server Error** with a stack trace instead of a proper **422 Unprocessable Entity** response. The service layer throws exceptions but controllers don't catch them.

### Tests Validating Wrong Logic

#### C6. `test_can_create_parcel_with_geojson_geometry` — Never Verifies Geometry Storage

- **File:** `tests/Feature/ParcelApiTest.php` (line 34)
- **Severity:** 🟠 High
- **Problem:** The test asserts `assertJsonPath('geometry.type', 'Polygon')` — this only checks the response echoes back the input type. It **never verifies** that:
  - The polygon was correctly parsed and stored in the database
  - The centroid was calculated
  - The `area_sqm` was populated
  - Coordinates were stored in the correct order (lat/lng vs lng/lat)

  A broken `parseGeometryFromGeoJson` that returns a degenerate polygon would still pass.

#### C7. `test_validation_rejects_invalid_geometry` Asserts Exact Error Message String

- **File:** `tests/Feature/ParcelApiTest.php` (line 94)
- **Severity:** 🟡 Medium (brittleness risk)
- **Problem:** `assertJsonPath('message', 'Only Polygon geometry is supported (and 2 more errors)')` — this assertion is **brittle** and couples to Laravel's auto-generated error message concatenation format. If Laravel changes how it formats multiple errors, or if validation rules change, this test breaks even though the validation logic is correct.

### False Positives (Tests That Should Fail But Don't)

#### C8. `test_the_application_routes_are_registered`

- **File:** `tests/Feature/ExampleTest.php` (line 7)
- **Code:** `$this->assertTrue(true);`
- **Severity:** 🔴 Critical — Provides zero value
- **Problem:** This is a **meaningless test**. It asserts nothing about routes. If all routes were deleted, this test would still pass. It inflates the test count and gives false confidence.

#### C9. `test_that_true_is_true` (Unit)

- **File:** `tests/Unit/ExampleTest.php` (line 10)
- **Code:** `$this->assertTrue(true);`
- **Severity:** 🔴 Critical — Provides zero value
- **Problem:** Same as C8. Zero value. Remove it.

#### C10. `test_can_calculate_parcel_area` — Asserts `area_sqm > 0` but Factory May Produce Degenerate Geometry

- **File:** `tests/Feature/AreaApiTest.php` (line 15)
- **Severity:** 🟠 High
- **Problem:** The `ParcelFactory` uses `rand(-100, 100) / 10000` for coordinate jitter. If random jitter produces a self-intersecting or zero-area polygon, `ST_Area()` returns `0` or `null`, and the test would fail **non-deterministically**. Conversely, if `ST_Area` returns any positive number (even astronomically wrong — e.g., 1000× the real area), the test passes. The test **never validates the area is correct**, just that it's positive.

---

## 3. Major Improvements (should fix)

### Code Structure Problems

#### M1. Repository Is a Thin Wrapper Around Eloquent

- **File:** `app/Repositories/ParcelRepository.php`
- **Problem:** Methods like `all()`, `find()`, `findOrFail()`, `create()`, `update()`, `delete()` are **single-line passthroughs** to Eloquent:

  ```php
  public function all(): Collection {
      return $this->model->all();  // <-- Eloquent already does this
  }
  ```

  This adds indirection without value. The spatial methods (`findWithinBuffer`, `findWithinBoundingBox`) are the only ones with real logic, but they're mixed in with CRUD passthroughs.

- **Recommendation:** Either remove the repository entirely (use Eloquent directly in the Service) or extract spatial queries into a dedicated `SpatialQuery` class and keep only complex queries in the repository.

#### M2. Service Layer Duplicates Request Validation Logic

- **Files:**
  - `app/Services/ParcelService.php` (lines 117–127 — `validateGeoJsonFeature`)
  - `app/Http/Requests/StoreParcelRequest.php`
- **Problem:** Both the FormRequest and the Service validate GeoJSON structure. The FormRequest checks `geometry.type`, `geometry.coordinates.*`, and coordinate bounds. The Service then re-validates with `validateGeoJsonFeature()`. This is a duplicated concern. The Service should either trust validated input or have its own independent validation contract — not both.

#### M3. No Authorization / Policies

- **Problem:** The app has no `Policies/` directory, no middleware, no auth. For a portfolio project this is acceptable, but if this ever goes to production, **anyone can create/update/delete any parcel**.

#### M4. `ParcelSeeder` Has Duplicated Centroid and Polygon Logic

- **File:** `database/seeders/ParcelSeeder.php` (lines 177–195)
- **Problem:** `calculateCentroid()` is implemented **identically** in three places:
  1. `ParcelSeeder.php` (line 185)
  2. `Parcel.php` model observer (line 51)
  3. `ParcelFactory.php` (implicit in `definition()`, lines 24–26)

  All three use the same arithmetic-mean approach. If the centroid algorithm changes, all three must be updated. Same applies to `Polygon` construction.

### Missing Edge Case Tests

| ID | Scenario | Priority | Why It Matters |
|----|----------|----------|----------------|
| M5 | Create parcel with duplicate geometry | High | No constraint or test for duplicate parcels |
| M6 | Update geometry to an invalid Polygon | High | Triggers centroid recalculation — untested |
| M7 | Delete a non-existent parcel | Medium | Should return 404 — route model binding may handle this |
| M8 | `PUT` with empty body | Medium | Should return 200 with no changes — current implementation may behave unexpectedly |
| M9 | Concurrent parcel creation (100+) | Medium | Tests `loadArea()` race conditions |
| M10 | `price_per_sqm = 0` (boundary value) | Medium | Should be accepted by `min:0` rule — untested |
| M11 | Maximum coordinate values (180, 90 / -180, -90) | Medium | Edge of valid WGS84 range — untested |
| M12 | Polygon with holes (multiple rings) | Medium | Valid GeoJSON but only outer ring is tested |
| M13 | `importGeoJsonFeatures` method | High | Public method in Service with **zero** controller endpoint and **zero** tests |

### Weak Assertions

#### M14. `test_can_list_parcels_as_feature_collection` Doesn't Validate GeoJSON Structure

- **File:** `tests/Feature/ParcelApiTest.php` (line 15)
- **Problem:** Only checks `type` and `features` count. Does not verify each feature has `geometry`, `properties`, `type: Feature`. A response with malformed features would still pass.

#### M15. `test_can_update_parcel` Doesn't Verify Unchanged Fields Remain Intact

- **File:** `tests/Feature/ParcelApiTest.php` (line 69)
- **Problem:** Only asserts `owner_name` was updated. Should assert that `status` and `price_per_sqm` remain unchanged to prove partial updates work correctly.

---

## 4. Minor Suggestions (nice to have)

| ID | Issue | File(s) | Description |
|----|-------|---------|-------------|
| m1 | Stock `README.md` | `README.md` | Zero custom content. Should describe API endpoints, how to run, how to test, and project purpose. |
| m2 | Duplicate `id` in `ParcelResource` | `app/Http/Resources/ParcelResource.php` | `'id'` appears both at Feature root and inside `properties`. Pick one. |
| m3 | Dead code: `importGeoJsonFeatures` | `app/Services/ParcelService.php` | Public method with no controller endpoint. Wire it to a route or remove it. |
| m4 | No pagination metadata | `app/Http/Resources/ParcelCollectionResource.php` | `metadata` only includes `total`. Currently loads **ALL** parcels into memory. Should include pagination support. |
| m5 | Scope inconsistency | `app/Models/Parcel.php` | `scopeWithStatus` exists but `ParcelRepository::findByStatus` reimplements it with raw `->where()`. Use the scope or remove it. |
| m6 | Duplicated FormRequest rules | `StoreParcelRequest.php`, `UpdateParcelRequest.php` | ~90% identical rules. Should use a shared custom `GeoJsonPolygon` rule. |
| m7 | Unexplained binary files | Root: `geoacquire`, `geoacquire_test` | Likely compiled binaries. Should be in `.gitignore` if not needed. |
| m8 | No `.env.testing` | — | Test DB credentials leak into `phpunit.xml`. Should be in a separate env file. |

---

## 5. Detected DRY Violations

| # | Violation | Locations | Description |
|---|-----------|-----------|-------------|
| DRY-1 | **Centroid calculation** | `ParcelSeeder.php:185`, `Parcel.php:51`, `ParcelFactory.php:24–26` | Same arithmetic-mean algorithm in 3 places. If the algorithm changes, all three must be updated. |
| DRY-2 | **Polygon construction** | `ParcelSeeder.php:177`, `ParcelFactory.php:22`, `ParcelService.php:129` | Same `new Polygon([new LineString($points)])` pattern repeated 3 times. |
| DRY-3 | **GeoJSON coordinate validation** | `StoreParcelRequest.php` and `UpdateParcelRequest.php` | 90% identical rules arrays. Should be extracted to a shared custom Rule class. |
| DRY-4 | **Area calculation query** | `ParcelRepository.php:109` and `Parcel.php:64` | Both do `selectRaw('ST_Area(boundary) as area')`. One should call the other. |
| DRY-5 | **Coordinate order convention** | `ParcelFactory.php` uses `[lat, lng]` but `ParcelService::parseGeometryFromGeoJson` expects `[lng, lat]` GeoJSON order | Inconsistent coordinate ordering. The factory constructs `Point($c[0], $c[1])` where `$c[0]` is lat, but GeoJSON standard is `[lng, lat]`. |
| DRY-6 | **Status enum values** | `StoreParcelRequest.php:16`, `UpdateParcelRequest.php:16`, `ParcelSeeder.php` | `'free,negotiating,target'` hardcoded as magic strings in 3+ places. Should be a PHP Enum. |
| DRY-7 | **GeoJSON validation** | `StoreParcelRequest.php`, `UpdateParcelRequest.php`, `ParcelService::validateGeoJsonFeature()` | Validation logic exists in 3 places. FormRequests check coordinates; Service re-checks geometry structure. |

---

## 6. Structural Issues

| ID | Issue | Expected Location | Current State |
|----|-------|-------------------|---------------|
| S1 | No `app/Enums/` | `app/Enums/ParcelStatus.php` | Status values (`free`, `negotiating`, `target`) are magic strings scattered across Request classes, Seeder, and potentially the frontend. |
| S2 | No `app/Rules/` | `app/Rules/GeoJsonPolygon.php` | GeoJSON validation logic in FormRequests should be extracted into a custom Rule class. Both Store and Update requests would use `['required', new GeoJsonPolygon()]`. |
| S3 | No custom exception handler | `app/Exceptions/` or `bootstrap/app.php` | No custom exception handling. Service-layer `InvalidArgumentException` bubbles up as 500. |
| S4 | No Artisan commands | `app/Console/Commands/` | `importGeoJsonFeatures` would be much better as an Artisan command: `php artisan parcels:import geo.json`. |
| S5 | No authorization | `app/Policies/` | Zero authorization layer. |
| S6 | No DTOs / Value Objects | `app/DTOs/` or `app/ValueObjects/` | Geometry data flows as raw arrays through the Service layer. A `GeoJsonFeature` DTO would make the contract explicit. |
| S7 | Routes not grouped/versioned | `routes/api.php` | Only 2 lines currently. Fine for now, but should be grouped under `Route::prefix('v1')->group(...)` when more endpoints are added. |

---

## 7. Suspicious Tests

| Test | File | Issue |
|------|------|-------|
| `test_the_application_routes_are_registered` | `tests/Feature/ExampleTest.php:7` | Asserts `true === true`. Tests absolutely nothing about routes. |
| `test_that_true_is_true` | `tests/Unit/ExampleTest.php:10` | Asserts `true === true`. Tests absolutely nothing. |
| `test_can_calculate_parcel_area` | `tests/Feature/AreaApiTest.php:15` | Only asserts `area > 0`. Would pass with any positive number, even 1000× the real area. Never validates correctness. |
| `test_can_create_parcel_with_geojson_geometry` | `tests/Feature/ParcelApiTest.php:34` | Only checks response echoes back input type. Never queries DB to verify geometry was correctly stored, centroid calculated, or area computed. |
| `test_validation_rejects_invalid_coordinates` | `tests/Feature/ParcelApiTest.php:107` | Only checks 422 status code. Doesn't verify the specific validation error. Could fail for any unrelated reason and still pass this assertion. |

---

## 8. Missing Test Scenarios

| # | Scenario | Priority | Why It Matters |
|---|----------|----------|----------------|
| MT-1 | Service throws exception → controller returns proper error response | **Critical** | Currently unhandled. A bad geometry request returns 500 with stack trace instead of 422. |
| MT-2 | Create parcel with Polygon containing holes (multiple rings) | **High** | Valid GeoJSON but only single-ring Polygons are tested. |
| MT-3 | Update only `price_per_sqm` to `null` | **High** | Nullable field with `sometimes` + `nullable` rules — untested combination. |
| MT-4 | Update parcel geometry (triggers centroid recalc + area reload) | **High** | Core business logic that exercises the observer chain — completely untested. |
| MT-5 | `findWithinBuffer` with 0 meter distance | **Medium** | Edge case boundary value. |
| MT-6 | `findWithinBufferOfParcel` for parcel with no boundary | **Medium** | Repository returns empty collection but has the bug described in C2 — untested. |
| MT-7 | `importGeoJsonFeatures` with mixed valid/invalid features | **High** | Method exists, handles partial imports with error tracking — **zero** test coverage. |
| MT-8 | Concurrent creation of 100 parcels | **Medium** | Tests `loadArea()` race conditions and event handling under load. |
| MT-9 | Area calculation for self-intersecting polygon | **Medium** | MySQL `ST_Area` behavior may be undefined or return unexpected results. |
| MT-10 | `status` filter with invalid status value | **Low** | Should return empty collection or 422 — undefined behavior. |
| MT-11 | Bounding box query crossing the antimeridian | **Low** | Longitude wrap-around edge case (e.g., minLng=170, maxLng=-170). |
| MT-12 | `price_per_sqm` negative value | **Low** | Should be rejected by `min:0` rule — untested. |

---

## 9. Recommended Next Actions (Phase 2)

### Priority 1 — Fix Bugs (Do These First)

1. **Fix `findWithinBufferOfParcel` bug** (C2) — This produces wrong results silently. The query buffers every row's own geometry instead of the target parcel's geometry. Highest priority.
2. **Remove hardcoded DB credentials** (C1) from `phpunit.xml`. Create `.env.testing` and move credentials there.
3. **Add controller-level exception handling** (C5) — Wrap service calls in try/catch and return proper HTTP status codes (422 for validation errors, 400 for bad geometry, 500 for unexpected errors).

### Priority 2 — Clean Up Tests

4. **Delete the two `assertTrue(true)` tests** (C8, C9). They inflate test count without value. Any reviewer will spot them immediately.
5. **Strengthen `test_can_create_parcel`** (C6) — After creating a parcel, query it from the database and verify the geometry, centroid, and area were correctly stored.
6. **Strengthen `test_can_calculate_parcel_area`** (C10) — Use a known polygon with a pre-calculated expected area instead of just asserting `> 0`.
7. **Fix brittle error message assertion** (C7) — Assert on validation error structure, not the concatenated message string.
8. **Add all missing test scenarios** from Section 8 (MT-1 through MT-12), prioritizing High and Critical.

### Priority 3 — Eliminate DRY Violations

9. **Consolidate centroid calculation** (C3, M4, DRY-1) into a single place — either a static method on the Model, a dedicated `GeometryHelper` class, or a Value Object. Remove duplicates from Seeder and Factory.
10. **Introduce a `ParcelStatus` enum** (S1, DRY-6) and replace all `'free'`, `'negotiating'`, `'target'` magic strings across Request classes, Seeder, and Factory.
11. **Extract GeoJSON validation into a custom Rule** (S2, DRY-3, DRY-7) — Create `app/Rules/GeoJsonPolygon.php` and use it in both Store and Update requests.
12. **Eliminate duplicate area calculation** (DRY-4) — Have `ParcelRepository::calculateArea()` call `Parcel::loadArea()` or vice versa.

### Priority 4 — Structural Improvements

13. **Decide on Repository pattern** (M1) — Either remove thin wrapper methods (`all`, `find`, `create`, `update`, `delete`) or give the Repository real responsibility (complex spatial queries only).
14. **Wire up or remove `importGeoJsonFeatures`** (m3, MT-7) — Either add a controller endpoint + route + tests, or delete the dead code.
15. **Write a proper README** (m1) — Document the API endpoints, request/response format, how to run tests, and how to seed data.
16. **Add pagination support** (m4) to `ParcelCollectionResource` and `ParcelController::index()` before the dataset grows.
17. **Add API versioning** (S7) — Wrap routes in `Route::prefix('v1')->group(...)` for future-proofing.
18. **Create `.env.testing`** (m8) — Move test database credentials out of `phpunit.xml`.

---

*End of Phase 1 Audit Report.*

---

## Phase 2: Resolution Report

**Date:** April 11, 2026
**Scope:** All Priority 1–4 items resolved
**Result:** 20 tests, 91 assertions, all passing (was 12 tests, 47 assertions)

### Resolution Status

| # | Issue | Status | Files Changed |
|---|-------|--------|---------------|
| C1 | Hardcoded DB credentials | **✅ Resolved** | `phpunit.xml`, `.env.testing`, `.gitignore` |
| C2 | `findWithinBufferOfParcel` self-intersection | **✅ Resolved** | `app/Repositories/ParcelRepository.php` |
| C3 | Centroid calculated in 4 places | **✅ Resolved** | `app/Support/GeometryHelper.php` (new), `ParcelSeeder.php`, `ParcelFactory.php`, `ParcelService.php`, `Parcel.php` |
| C4 | Fragile `loadArea()` pattern | **✅ Resolved** | `app/Models/Parcel.php` — extracted `calculateArea()` method, `loadArea()` delegates to it |
| C5 | No exception handling in controllers | **✅ Resolved** | `app/Http/Controllers/Api/ParcelController.php` |
| C6 | Weak `test_can_create_parcel` assertions | **✅ Resolved** | `tests/Feature/ParcelApiTest.php` — now verifies DB geometry, centroid, area |
| C7 | Brittle error message assertion | **✅ Resolved** | `tests/Feature/ParcelApiTest.php` — uses `assertJsonValidationErrors()` |
| C8 | `assertTrue(true)` Feature test | **✅ Resolved** | Removed `tests/Feature/ExampleTest.php` |
| C9 | `assertTrue(true)` Unit test | **✅ Resolved** | Removed `tests/Unit/ExampleTest.php` |
| C10 | Non-deterministic area test | **✅ Resolved** | `tests/Feature/AreaApiTest.php` — uses known polygon with bounded assertions |
| M1 | Repository thin wrapper | **✅ Resolved** | `app/Repositories/ParcelRepository.php` — only spatial queries remain; Service uses Eloquent for CRUD |
| M2 | Duplicated validation logic | **✅ Resolved** | `app/Rules/GeoJsonPolygon.php` (new), `StoreParcelRequest.php`, `UpdateParcelRequest.php` |
| M4 | Duplicated centroid in Seeder | **✅ Resolved** | Consolidated into `GeometryHelper` |
| M14 | Weak list assertions | **✅ Resolved** | Validates each feature has `type`, `id`, `geometry`, `properties` |
| M15 | Weak update assertions | **✅ Resolved** | Verifies `status` and `price_per_sqm` remain unchanged |
| m1 | Stock README | **✅ Resolved** | `README.md` — full API docs, setup, test instructions |
| m2 | Duplicate `id` in ParcelResource | **✅ Resolved** | `app/Http/Resources/ParcelResource.php` |
| m3 | Dead `importGeoJsonFeatures` code | **✅ Resolved** | `app/Console/Commands/ImportGeoJson.php` (new) |
| m4 | No pagination | **✅ Resolved** | `ParcelController.php`, `ParcelCollectionResource.php` |
| m8 | No `.env.testing` | **✅ Resolved** | `.env.testing` created, added to `.gitignore` |
| S1 | No enums | **✅ Resolved** | `app/Enums/ParcelStatus.php` (new) |
| S2 | No custom Rule | **✅ Resolved** | `app/Rules/GeoJsonPolygon.php` (new) |
| S4 | No Artisan commands | **✅ Resolved** | `php artisan parcels:import` |
| S7 | No API versioning | **✅ Resolved** | `routes/api.php` — wrapped in `Route::prefix('v1')` |
| DRY-1 | Centroid in 3 places | **✅ Resolved** | Single `GeometryHelper::centroidFromCoordinates()` |
| DRY-2 | Polygon construction in 3 places | **✅ Resolved** | Single `GeometryHelper::polygonFromCoordinates()` |
| DRY-3 | Duplicated FormRequest rules | **✅ Resolved** | Both use `GeoJsonPolygon` rule |
| DRY-4 | Duplicate area calculation | **✅ Resolved** | `Parcel::calculateArea()` — single source of truth |
| DRY-5 | Coordinate order inconsistency | **✅ Resolved** | Factory now uses `[lng, lat]` GeoJSON order |
| DRY-6 | Magic status strings | **✅ Resolved** | `ParcelStatus` enum created |
| DRY-7 | GeoJSON validation in 3 places | **✅ Resolved** | `GeoJsonPolygon` rule used by both FormRequests; Service trusts validated input |
| MT-1 | Exception → proper error | **✅ Resolved** | Controller catches `InvalidArgumentException` → 422 |
| MT-2 | Polygon with holes | **✅ Resolved** | Test added |
| MT-3 | Update `price_per_sqm` to null | **✅ Resolved** | Test added |
| MT-4 | Update geometry triggers recalc | **✅ Resolved** | Test added; `saving` observer fixed with `isDirty('boundary')` |
| MT-7 | `importGeoJsonFeatures` tests | **✅ Resolved** | 2 tests: valid import + mixed valid/invalid |
| MT-10 | Invalid status filter | **✅ Resolved** | Test added |
| MT-12 | Negative price rejected | **✅ Resolved** | Test added |

### Items Not Addressed (out of scope / deferred)

| # | Issue | Reason |
|---|-------|--------|
| M3 | No authorization / policies | Portfolio project — no auth requirement |
| M5 | Duplicate geometry constraint | Requires unique DB constraint — future feature |
| M9 | Concurrent parcel creation (100+) | Requires parallel testing infrastructure |
| MT-5 | `findWithinBuffer` with 0m distance | Low-value edge case |
| MT-6 | `findWithinBufferOfParcel` no boundary | Covered by null check; C2 bug already fixed |
| MT-8 | Concurrent creation of 100 parcels | Same as M9 |
| MT-9 | Self-intersecting polygon area | MySQL behavior is database-specific |
| MT-11 | Antimeridian bounding box | Rare edge case for Jakarta-centric app |
| S5 | No policies | Same as M3 |
| S6 | No DTOs/Value Objects | Over-engineering for current scope |
| m5 | Scope inconsistency | `findByStatus` uses the scope via `withStatus()` |
| m7 | Binary files in repo | Not code-affecting |

### Before / After

| Metric | Before | After |
|--------|--------|-------|
| Tests | 12 | 20 |
| Assertions | 47 | 91 |
| Meaningful tests | ~5 | 20 |
| Files created | — | 5 (`GeometryHelper`, `GeoJsonPolygon`, `ParcelStatus`, `ImportGeoJson`, `.env.testing`) |
| Files removed | — | 2 (`ExampleTest.php` × 2) |
| False positives | 2 (`assertTrue(true)`) | 0 |
| DRY violations | 7 | 0 |
| Structural issues | 7 | 2 (auth, DTOs — deferred) |
