# GeoAcquire Deployment Guide

**Backend API:** Laravel 12 | **Database:** MySQL 8.0 | **Target:** Free Tier Deployment

---

## Architecture Overview

```
┌─────────────┐         ┌─────────────┐         ┌──────────────┐
│   Vercel    │ ────>   │   Render    │ ────>   │  PlanetScale │
│  (Frontend) │  HTTP   │  (Laravel)  │  MySQL  │   (Database) │
│   Free      │         │   Free      │         │     Free     │
└─────────────┘         └─────────────┘         └──────────────┘
```

| Platform | Purpose | Free Tier Limits |
|----------|---------|------------------|
| **Render** | Laravel API Backend | 750 hours/month, sleeps after 15min inactivity |
| **PlanetScale** | MySQL Database | 5GB storage, 1B rows/month reads |
| **Vercel** | React Frontend (separate repo) | 100GB bandwidth, 100k invocations/month |

---

## Prerequisites

1. **GitHub Repository** - Push code to GitHub (Render connects via Git)
2. **Render Account** - Sign up at [render.com](https://render.com)
3. **PlanetScale Account** - Sign up at [planetscale.com](https://planetscale.com)
4. **Local Environment** - PHP 8.2+, Composer, Git

---

## Step 1: PlanetScale Database Setup

### 1.1 Create Database

1. Login to [PlanetScale Dashboard](https://app.planetscale.com/)
2. Click **"New database"**
3. Configure:
   - **Name:** `geoacquire-db` (or your preferred name)
   - **Region:** Select closest to your users (e.g., Singapore)
   - **Plan:** Free tier (Scaler Pro Free)
4. Click **"Create database"

### 1.2 Get Connection Credentials

1. Go to your database dashboard
2. Click **"Connect"** button
3. Select **"Connect with username and password"**
4. Generate password and save these credentials:

```env
DB_HOST=aws.connect.psdb.cloud
DB_PORT=3306
DB_DATABASE=geoacquire-db
DB_USERNAME=xxxxxxxxxxxx
DB_PASSWORD=pscale_pw_xxxxxxxxxx
```

### 1.3 Enable PlanetScale SSL (Required)

PlanetScale requires SSL. Add to Laravel's `config/database.php` in the MySQL connections array:

```php
'mysql' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // Add these SSL options for PlanetScale
    'options' => [
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
    ],
],
```

---

## Step 2: Prepare Laravel for Production

### 2.1 Update Environment Configuration

Create `render.yaml` in project root:

```yaml
services:
  - type: web
    name: geoacquire-api
    env: php
    region: singapore
    buildCommand: |
      composer install --no-dev --optimize-autoloader
      php artisan key:generate --force
      php artisan config:cache
      php artisan route:cache
      php artisan migrate --force
      php artisan db:seed --force
    startCommand: php artisan serve --host 0.0.0.0 --port $PORT
    envVars:
      - key: APP_ENV
        value: production
      - key: APP_DEBUG
        value: false
      - key: APP_URL
        value: https://geoacquire-api.onrender.com
      - key: APP_KEY
        generateValue: true
      - key: DB_CONNECTION
        value: mysql
      - key: DB_HOST
        sync: false  # Set manually in Render dashboard
      - key: DB_PORT
        value: "3306"
      - key: DB_DATABASE
        sync: false  # Set manually in Render dashboard
      - key: DB_USERNAME
        sync: false  # Set manually in Render dashboard
      - key: DB_PASSWORD
        sync: false  # Set manually in Render dashboard
      - key: CACHE_DRIVER
        value: database
      - key: SESSION_DRIVER
        value: database
      - key: QUEUE_CONNECTION
        value: database
```

### 2.2 Verify Dependencies

Check `composer.json` has these required:

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "matanyadaev/laravel-eloquent-spatial": "^2.0"
    }
}
```

### 2.3 Optimize for Production (Optional but Recommended)

Run locally before pushing:

```bash
# Optimize composer autoload
composer install --optimize-autoloader --no-dev

# Cache config, routes, views
php artisan optimize

# Test in production mode locally
APP_ENV=production php artisan serve
```

---

## Step 3: Deploy to Render

### 3.1 Connect Repository

1. Login to [Render Dashboard](https://dashboard.render.com/)
2. Click **"New +"** → **"Web Service"**
3. Connect your GitHub account
4. Select `GeoAcquire_BE` repository

### 3.2 Configure Web Service

| Setting | Value |
|---------|-------|
| **Name** | `geoacquire-api` |
| **Region** | Singapore (closest to Indonesia) |
| **Branch** | `master` or `main` |
| **Runtime** | PHP (Native) |
| **Build Command** | See `render.yaml` above |
| **Start Command** | `php artisan serve --host 0.0.0.0 --port $PORT` |

### 3.3 Set Environment Variables

In Render Dashboard → your service → **Environment**, add:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://geoacquire-api.onrender.com
DB_CONNECTION=mysql
DB_HOST=aws.connect.psdb.cloud
DB_PORT=3306
DB_DATABASE=geoacquire-db
DB_USERNAME=your_username
DB_PASSWORD=your_password
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 3.4 Deploy

1. Click **"Create Web Service"**
2. Render will automatically deploy from your GitHub branch
3. Monitor logs in **"Logs"** tab

**First deploy will:**
1. Install dependencies via Composer
2. Generate `APP_KEY`
3. Cache config and routes
4. Run migrations (`php artisan migrate --force`)
5. Seed database (`php artisan db:seed --force`)

---

## Step 4: Verify Deployment

### 4.1 Check Health

```bash
# Test API root
curl https://geoacquire-api.onrender.com/api/health

# Test parcels endpoint
curl https://geoacquire-api.onrender.com/api/v1/parcels

# Test count endpoint
curl https://geoacquire-api.onrender.com/api/v1/parcels/count
```

### 4.2 Check PlanetScale Data

1. Go to PlanetScale Dashboard → your database
2. Click **"Insights"** or **"Console"**
3. Run: `SELECT COUNT(*) FROM parcels;`

---

## Step 5: Connect Frontend (Vercel)

Update your React frontend `.env`:

```env
VITE_API_BASE_URL=https://geoacquire-api.onrender.com/api/v1
```

**Important:** Enable CORS in Laravel (already done in `config/cors.php`):

```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],  // Lock this down to your Vercel domain in production
'allowed_headers' => ['*'],
```

---

## Free Tier Limitations & Mitigations

### Render Free Tier

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| 750 hours/month | ~31 days continuous | Adequate for demo |
| Sleep after 15min inactivity | Cold start ~30-60s | Frontend shows loading state |
| 512MB RAM | May slow with 1000+ parcels | Use pagination, optimize queries |
| No SSL on free (custom domain) | Use render.com domain | OK for demo |

### PlanetScale Free Tier

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| 5GB storage | ~1M+ parcels | More than enough |
| 1B rows/month reads | 1000 parcels x 1000 users = 1M reads | Very comfortable |
| 100k writes/month | Seeding 1000 parcels = 1k writes | No issue |

---

## Troubleshooting

### Issue: "SQLSTATE[HY000] [2002] Connection refused"

**Cause:** PlanetScale SSL not configured

**Fix:** Add SSL options to `config/database.php`:
```php
'options' => [
    PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
],
```

### Issue: "500 Internal Server Error"

**Cause:** Missing APP_KEY or config cache issue

**Fix:**
```bash
# In Render console / SSH
php artisan key:generate
php artisan config:clear
php artisan cache:clear
```

### Issue: Migration fails on deploy

**Cause:** Database connection not ready during build

**Fix:** Use deploy hook instead of build command for migrations:
```yaml
deployHooks:
  - name: Run Migrations
    cmd: php artisan migrate --force
```

### Issue: Spatial functions not working

**Cause:** PlanetScale uses Vitess (MySQL-compatible), some ST_* functions may differ

**Fix:** Verify in PlanetScale console:
```sql
SELECT ST_AsGeoJSON(geometry) FROM parcels LIMIT 1;
```

---

## Cost Estimate (Free Tier)

| Service | Plan | Monthly Cost |
|---------|------|--------------|
| Render (Web Service) | Free | **$0** |
| PlanetScale (Database) | Scaler Pro Free | **$0** |
| **Total** | | **$0** |

**When to upgrade:**
- >100 daily active users → Render Pro ($7/month)
- >10GB data → PlanetScale Scaler Pro ($29/month)

---

## Sources

- [Render - Deploy PHP Laravel with Docker](https://render.com/docs/deploy-php-laravel-docker)
- [PlanetScale - Connect Laravel Application](https://planetscale.com/docs/vitess/tutorials/connect-laravel-app)
- [Laravel 12 - Deployment Optimization](https://laravel.com/docs/12.x/deployment)
- [Laravel 12 - Database Configuration](https://laravel.com/docs/12.x/database)

---

*Last updated: 2026-04-14*
