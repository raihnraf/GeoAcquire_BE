# GeoAcquire Deployment Guide

**Backend API:** Laravel 12 | **Database:** MySQL 8.0 | **Target:** Free Tier Deployment

> **Note:** PlanetScale free tier was discontinued in April 2024. This guide uses **Aiven** as the MySQL free tier alternative.

---

## Architecture Overview

```
┌─────────────┐         ┌─────────────┐         ┌──────────────┐
│   Vercel    │ ────>   │   Render    │ ────>   │    Aiven     │
│  (Frontend) │  HTTP   │  (Laravel)  │  MySQL  │   (Database) │
│   Free      │         │   Free      │         │     Free     │
└─────────────┘         └─────────────┘         └──────────────┘
```

| Platform | Purpose | Free Tier Limits |
|----------|---------|------------------|
| **Render** | Laravel API Backend | 750 hours/month, sleeps after 15min inactivity |
| **Aiven** | MySQL Database | 1GB storage, 50GB traffic/month |
| **Vercel** | React Frontend (separate repo) | 100GB bandwidth, 100k invocations/month |

---

## Free MySQL Database Options (2025)

| Provider | Storage | Limit | Notes |
|----------|---------|-------|-------|
| **Aiven** | 1GB | 50GB traffic | ⭐ **Recommended** - Stable free tier |
| **SkySQL** | 500MB | Serverless | MariaDB-based, good for testing |
| **Railway** | 1GB | $5 credit one-time | Good alternative |
| **Neon** | 0.5GB | PostgreSQL only | Not MySQL, no spatial functions |

**For GeoAcquire (requires MySQL spatial functions):** Use **Aiven** — it provides real MySQL 8.0 with ST_* spatial functions support.

---

## Prerequisites

1. **GitHub Repository** - Push code to GitHub (Render connects via Git)
2. **Render Account** - Sign up at [render.com](https://render.com)
3. **Aiven Account** - Sign up at [aiven.io](https://aiven.io)
4. **Local Environment** - PHP 8.2+, Composer, Git

---

## Step 1: Aiven Database Setup

### 1.1 Create Database

1. Login to [Aiven Console](https://console.aiven.io/)
2. Click **"Create service"**
3. Configure:
   - **Service type:** MySQL
   - **Cloud provider:** AWS
   - **Region:** Singapore (closest to Indonesia)
   - **Plan:** **Free tier** (look for "Free-0-3-1" or similar)
   - **Service name:** `geoacquire-db`
4. Click **"Create service"**

### 1.2 Get Connection Credentials

1. Go to your service dashboard
2. Click **"Overview"** → **"Connection information"**
3. You'll see credentials like:

```env
DB_HOST=xxx.aivencloud.com
DB_PORT=24824  # Note: Aiven uses non-standard ports
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=xxxxxxxxxxxx
```

### 1.3 Download SSL Certificate (Required)

1. In the same **Connection information** section
2. Download **CA certificate** file
3. For Render deployment, you'll need to configure SSL path

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
    plan: free
    buildCommand: |
      composer install --no-dev --optimize-autoloader
      php artisan key:generate --force
      php artisan config:cache
      php artisan route:cache
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
        sync: false  # Set from Aiven
      - key: DB_PORT
        sync: false  # Aiven uses non-standard port (e.g., 24824)
      - key: DB_DATABASE
        sync: false
      - key: DB_USERNAME
        sync: false
      - key: DB_PASSWORD
        sync: false
      - key: MYSQL_ATTR_SSL_CA
        value: /etc/ssl/certs/ca-certificates.crt
      - key: CACHE_DRIVER
        value: database
      - key: SESSION_DRIVER
        value: database
      - key: QUEUE_CONNECTION
        value: database
    deployHooks:
      - name: Run Migrations
        cmd: php artisan migrate --force
      - name: Seed Database
        cmd: php artisan db:seed --force
```

### 2.2 Verify Database Config

Check `config/database.php` has SSL configured (already in Laravel 12):

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    // SSL options for Aiven
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### 2.3 Verify Dependencies

Check `composer.json` has:

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "matanyadaev/laravel-eloquent-spatial": "^2.0"
    }
}
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
| **Region** | Singapore |
| **Branch** | `master` or `main` |
| **Runtime** | PHP (Native) |
| **Plan** | Free |

### 3.3 Set Environment Variables

In Render Dashboard → your service → **Environment**:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://geoacquire-api.onrender.com
DB_CONNECTION=mysql
DB_HOST=xxx.aivencloud.com
DB_PORT=24824
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=your_password_here
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 3.4 Deploy

1. Click **"Create Web Service"**
2. Render will automatically deploy from GitHub
3. Monitor logs in **"Logs"** tab

**First deploy will:**
1. Install dependencies via Composer
2. Generate `APP_KEY`
3. Cache config and routes
4. Run migrations (`php artisan migrate --force`)
5. Seed database (`php artisan db:seed --force`)

---

## Step 4: Verify Deployment

### 4.1 Check API Health

```bash
# Test API root
curl https://geoacquire-api.onrender.com/api/v1/parcels

# Test count endpoint
curl https://geoacquire-api.onrender.com/api/v1/parcels/count

# Test with pagination
curl https://geoacquire-api.onrender.com/api/v1/parcels?page=1&per_page=10
```

### 4.2 Check Aiven Data

1. Go to Aiven Console → your service
2. Click **"SQL Editor"** or use connection from your local MySQL client
3. Run: `SELECT COUNT(*) FROM parcels;`

---

## Step 5: Connect Frontend (Vercel)

Update your React frontend `.env`:

```env
VITE_API_BASE_URL=https://geoacquire-api.onrender.com/api/v1
```

**CORS already configured** in `config/cors.php`:

```php
'paths' => ['api/*'],
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],  // Lock down to your Vercel domain in production
'allowed_headers' => ['*'],
```

**For production, update to:**
```php
'allowed_origins' => ['https://your-frontend.vercel.app'],
```

---

## Free Tier Limitations & Mitigations

### Render Free Tier

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| 750 hours/month | ~31 days continuous | Adequate for demo |
| Sleep after 15min inactivity | Cold start ~30-60s | Frontend shows loading |
| 512MB RAM | May slow with 1000+ parcels | Use pagination |
| No custom SSL on free | Use .onrender.com domain | OK for demo |

### Aiven Free Tier

| Limitation | Impact | Mitigation |
|------------|--------|------------|
| 1GB storage | ~100k parcels | More than enough for demo |
| 50GB traffic/month | ~50M API calls | Very comfortable |
| Non-standard port | Need to configure DB_PORT | Already in render.yaml |
| Requires SSL | Must configure SSL | Already configured |

---

## Troubleshooting

### Issue: "SQLSTATE[HY000] [2002] Connection refused"

**Cause:** Wrong port or SSL not configured

**Fix:**
1. Check Aiven uses non-standard port (e.g., 24824, not 3306)
2. Verify `MYSQL_ATTR_SSL_CA` is set in environment

### Issue: "SSL connection error"

**Cause:** Aiven requires SSL

**Fix:** Ensure these are in environment:
```env
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt
```

### Issue: "500 Internal Server Error"

**Cause:** Missing APP_KEY or config cache

**Fix:**
```bash
# In Render console
php artisan key:generate
php artisan config:clear
php artisan cache:clear
```

### Issue: Migration fails on deploy

**Cause:** Database not ready during build

**Fix:** Already using `deployHooks` instead of `buildCommand` for migrations

### Issue: Spatial functions not working

**Cause:** Wrong database or MySQL version

**Fix:** Verify in Aiven SQL Editor:
```sql
SELECT VERSION();  -- Should be 8.0+
SELECT ST_AsGeoJSON(geometry) FROM parcels LIMIT 1;
```

---

## Cost Estimate (Free Tier)

| Service | Plan | Monthly Cost |
|---------|------|--------------|
| Render (Web Service) | Free | **$0** |
| Aiven (MySQL) | Free-0-3-1 | **$0** |
| **Total** | | **$0** |

**When to upgrade:**
- >100 daily active users → Render Pro ($7/month)
- >1GB data → Aiven Startup-1 (~$50/month) or consider Railway

---

## Alternative: Railway (Simpler Option)

If Aiven feels complex, **Railway** has a simpler free tier:

1. Sign up at [railway.app](https://railway.app)
2. Connect GitHub repo
3. Railway auto-detects Laravel
4. Add MySQL service from marketplace
5. **Limit:** $5 one-time credit, then pay-as-you-go

Railway is easier but has less generous free tier for long-term use.

---

## Sources

- [Aiven Free MySQL Database](https://aiven.io/free-mysql-database)
- [Aiven MySQL Getting Started](https://aiven.io/docs/products/mysql/get-started)
- [Render - Deploy PHP Laravel](https://render.com/docs/deploy-php-laravel-docker)
- [Laravel 12 - Deployment](https://laravel.com/docs/12.x/deployment)
- [Laravel 12 - Database Configuration](https://laravel.com/docs/12.x/database)

---

*Last updated: 2026-04-14 (Updated for PlanetScale free tier deprecation)*
