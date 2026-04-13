# GeoAcquire Backend

Land Acquisition & Spatial Analysis Dashboard — REST API built with **Laravel 12** and **MySQL spatial extensions**.

## Features

- **Parcel CRUD** — Create, read, update, delete land parcels with GeoJSON Polygon geometry
- **Spatial queries** — Find parcels within a buffer radius, bounding box, or near another parcel
- **Area calculation** — MySQL `ST_Area` for accurate square-meter measurements
- **Centroid calculation** — Automatic vertex-average centroid on save
- **GeoJSON import** — Bulk import via Artisan command from GeoJSON FeatureCollection files
- **Pagination** — Paginated list endpoint with full pagination metadata

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | MySQL 8.0+ with spatial extensions |
| Spatial Library | [matanyadaev/laravel-eloquent-spatial](https://github.com/matanyadaev/laravel-eloquent-spatial) |
| Testing | PHPUnit |

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+ with spatial support
- Node.js & npm (for Vite asset compilation, if needed)

### Installation

```bash
# 1. Clone and install dependencies
composer install
cp .env.example .env
php artisan key:generate

# 2. Configure your database in .env
DB_CONNECTION=mysql
DB_DATABASE=geoacquire

# 3. Run migrations
php artisan migrate

# 4. (Optional) Seed sample data
php artisan db:seed --class=ParcelSeeder
```

### Running the Server

```bash
php artisan serve
```

The API is available at `http://localhost:8000/api/v1`.

## Running Tests

```bash
# Create a test database and run migrations
php artisan test
# or
./vendor/bin/phpunit
```

Test credentials are configured in `.env.testing`. The test suite uses `RefreshDatabase` so all tests run against a clean database.

## API Endpoints

All endpoints are prefixed with `/api/v1`.

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/v1/parcels` | List all parcels (paginated, GeoJSON FeatureCollection) |
| `POST` | `/api/v1/parcels` | Create a new parcel |
| `GET` | `/api/v1/parcels/{id}` | Get a single parcel as GeoJSON Feature |
| `PUT` | `/api/v1/parcels/{id}` | Update a parcel (partial update) |
| `DELETE` | `/api/v1/parcels/{id}` | Delete a parcel |
| `GET` | `/api/v1/parcels/{id}/area` | Get calculated area for a parcel |

### Query Parameters

- `GET /api/v1/parcels?per_page=10&page=2` — Pagination controls (default: 20 per page)

### Request Body (Create/Update)

```json
{
  "owner_name": "PT Example Land",
  "status": "target",
  "price_per_sqm": 12500000,
  "geometry": {
    "type": "Polygon",
    "coordinates": [[
      [106.6150, -6.2500],
      [106.6170, -6.2500],
      [106.6170, -6.2510],
      [106.6150, -6.2510],
      [106.6150, -6.2500]
    ]]
  }
}
```

- `owner_name` — **required** string, max 255 chars
- `status` — optional, one of: `free`, `negotiating`, `target`
- `price_per_sqm` — optional, numeric, minimum 0
- `geometry` — **required** for create, GeoJSON Polygon with `[longitude, latitude]` coordinates

### Response Format

Responses follow **GeoJSON** conventions:
- Single resource → `Feature`
- List → `FeatureCollection` with pagination `metadata`

### Status Values

| Status | Description |
|--------|-------------|
| `free` | Available for acquisition |
| `negotiating` | Currently under negotiation |
| `target` | Priority target for acquisition |

## Artisan Commands

### Import GeoJSON

```bash
php artisan parcels:import path/to/file.geojson
```

Imports all features from a GeoJSON file. Reports imported count and any failed features with error details.

### Seed Sample Data

```bash
php artisan db:seed --class=ParcelSeeder
```

Creates 15 sample parcels in the Gading Serpong, Tangerang area.

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands (parcels:import)
├── Enums/                # PHP enums (ParcelStatus)
├── Http/
│   ├── Controllers/Api/  # REST controllers (Parcel, Area)
│   ├── Requests/         # Form requests (validation)
│   └── Resources/        # API resources (GeoJSON transformers)
├── Models/               # Eloquent models (Parcel)
├── Repositories/         # Complex spatial queries only
├── Rules/                # Custom validation rules (GeoJsonPolygon)
├── Services/             # Business logic (ParcelService)
└── Support/              # Static helpers (GeometryHelper)
```

## License

MIT
