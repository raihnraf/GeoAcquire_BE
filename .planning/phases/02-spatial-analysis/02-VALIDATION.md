---
phase: 2
slug: spatial-analysis
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-04-11
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 11.x |
| **Config file** | phpunit.xml |
| **Quick run command** | `php artisan test --parallel` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --parallel`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 02-01-01 | 01 | 1 | SPAT-01 | T-2-01 | Bounding box injection prevented | api | `php artisan test --filter BoundingBoxTest` | ✅ W0 | ⬜ pending |
| 02-01-02 | 01 | 1 | SPAT-02 | T-2-02 | Buffer distance validated | api | `php artisan test --filter BufferAnalysisTest` | ✅ W0 | ⬜ pending |
| 02-01-03 | 01 | 1 | FOUND-05 | — | Status enum validated | api | `php artisan test --filter StatusFilterTest` | ✅ W0 | ⬜ pending |
| 02-02-01 | 02 | 1 | SPAT-03 | T-2-03 | Parcel buffer ACL enforced | api | `php artisan test --filter ParcelBufferTest` | ✅ W0 | ⬜ pending |
| 02-02-02 | 02 | 1 | ANAL-02 | — | Aggregate query optimized | api | `php artisan test --filter AggregateAreaTest` | ✅ W0 | ⬜ pending |
| 02-03-01 | 03 | 2 | DATA-06 | T-2-04 | Import size limited | api | `php artisan test --filter ImportGeoJsonTest` | ✅ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/SpatialQueryTest.php` — stubs for SPAT-01, SPAT-02, SPAT-03
- [ ] `tests/Feature/ParcelAggregateTest.php` — stubs for ANAL-02
- [ ] `tests/Feature/ParcelImportTest.php` — stubs for DATA-06
- [ ] `tests/Feature/ParcelStatusFilterTest.php` — stubs for FOUND-05
- [ ] Existing `tests/Feature/ParcelApiTest.php` covers spatial repository methods

*Note: Phase 1 test infrastructure exists. Wave 0 adds new test files for Phase 2 endpoints.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| GeoJSON import with 100+ features | DATA-06 | Timeout behavior depends on server load | Use curl to POST a 100-feature FeatureCollection, verify response < 30s |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
