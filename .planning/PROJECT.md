# GeoAcquire

## What This Is

A Laravel 12 backend API for land acquisition and spatial analysis. Returns GeoJSON format for interactive mapping, stores spatial data (POLYGON/POINT) in MySQL, and provides area calculation and buffer zone analysis. Frontend lives in a separate repository.

## Core Value

Spatial data must be queryable and analyzable — if a user can't get land parcels within a buffer zone or calculate accurate areas, the system fails.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] REST API returning GeoJSON format (not standard JSON)
- [ ] MySQL spatial data storage (POLYGON for land boundaries, POINT for coordinates)
- [ ] Spatial Index for fast location-based queries
- [ ] Area calculation (total square meters from polygon shapes)
- [ ] Buffer zone analysis (find parcels within X distance from point/line)
- [ ] GeoJSON import/export for bulk data loading
- [ ] Land status tracking (Free: Green, Negotiating: Yellow, Target: Red)
- [ ] Seeder with 10-20 dummy parcels in Gading Serpong area

### Out of Scope

- Frontend UI — separate React repository
- User authentication — v1 is API-first, public read access
- Payment processing — not in scope for land acquisition
- Advanced GIS operations beyond buffer zones (intersection, union) — defer to v2

## Context

**Target:** Portfolio project for Paramount Enterprise (property development company focusing on integrated city development and land acquisition).

**Business Problem:** Land acquisition teams struggle with tabular data. They need visual maps showing:
- Which parcels are freed vs negotiating vs target
- Total area calculations automatically
- Spatial queries (e.g., "show all land within 500m of planned toll road")

**Technical Environment:**
- Laravel 12 (backend API only)
- MySQL 8.0 with spatial types (POLYGON, POINT, Spatial Index)
- Deployment: Aiven (MySQL free tier) + Render (Laravel free tier)

**Data Domain:**
- Land parcels as polygons with owner_name, status, price_per_sqm
- Geographic focus: Gading Serpong area
- Status workflow: Target (Red) → Negotiating (Yellow) → Freed (Green)

## Constraints

- **Backend Only**: Frontend is separate React repository — this project builds the API
- **Laravel 12**: Must use latest Laravel, not Laravel 11
- **Spatial Data**: Must use MySQL spatial types (POLYGON, POINT), not store coordinates as text
- **GeoJSON Format**: API responses must be GeoJSON-compliant for Leaflet/Mapbox consumption
- **Free Tier Deployment**: Design for Aiven (MySQL) and Render (Laravel) free tier limitations
- **Demo-Ready**: Must have seeded dummy data for immediate portfolio demonstration

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| GeoJSON response format | Standard GIS format, direct compatibility with Leaflet.js frontend | — Pending |
| MySQL spatial types | Native spatial support, Spatial Index for performance | — Pending |
| Buffer zone analysis | Core business need — finding parcels near infrastructure (roads, facilities) | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-04-11 after initialization*
