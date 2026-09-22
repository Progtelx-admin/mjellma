# Installation and Setup

## Overview

Local setup for the Laravel application with MySQL. Document root must point at `public_html/`.

## Requirements

| Requirement | Notes |
| --- | --- |
| PHP 8.1+ | `composer.json` `^8.1.0`; front controller rejects ≤ 8.0.2 |
| Composer | PHP dependencies |
| MySQL | Default `DB_CONNECTION` |
| Node.js + npm or yarn | Asset builds when changing Sass/JS |
| Apache `mod_rewrite` (or equivalent) | Listed in installer config; Laragon is a common local stack |
| PHP extensions | openssl, PDO, mbstring, tokenizer, JSON, cURL, fileinfo (`config/installer.php`) |

Optional:

- Redis (if you change cache/queue/session drivers)
- Python 3 + `requests`, `zstandard`, `mysql-connector-python` for hotel dump scripts
- PCB PEM certificates under `storage/certs/` for card payments

No project `Dockerfile` / `docker-compose.yml` is present. `laravel/sail` is a Composer **dev** dependency without Sail compose files in this repo.

## Installation Steps

### 1. Install PHP dependencies

From the repository root:

```powershell
composer install
```

### 2. Environment file

```powershell
copy .env.example .env
php artisan key:generate
```

Edit `.env` with database settings and `APP_URL`. See [Environment Variables](./reference/environment-variables.md).

**Never commit real secrets.** Treat values in local `.env` and any hardcoded script credentials as private.

### 3. Create database

Create an empty MySQL database matching `DB_DATABASE`.

### 4. Web server document root

Point the vhost/document root to:

```text
<path-to-repo>/public_html
```

`public_html/index.php` is the front controller. The root `server.php` expects `public/index.php`, which is **not** the live front controller in this tree—prefer Apache/Laragon or `php artisan serve`.

### 5. Installer or migrations

**Option A — web installer**

1. Ensure `storage/installed` is absent for a fresh install.
2. Visit `/install` and complete the wizard (requirements, env, database).
3. Middleware `RedirectToInstaller` sends traffic to the installer until installed.

**Option B — Artisan**

```powershell
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` prefers the active theme seeder when present; otherwise seeds roles, users, media, locations, and demo bookable content.

Hotel RateHawk static rows (`hotels`, `hotel_images`) are **not** filled by Laravel seeders. Run `hotels_data.py` after configuring DB/API locally. See [Integrations](./17-integrations.md).

### 6. Node dependencies (when building assets)

```powershell
npm install
```

Theme builds:

```powershell
cd public_html\themes\admin
npm install

cd ..\bc
npm install
```

Compiled assets already exist under `public_html/dist/` for many environments.

### 7. Storage permissions

Ensure `storage/` and `bootstrap/cache/` are writable by the PHP process.

### 8. Integration credentials

For hotel search to work end-to-end, set ETG variables (`API_URL`, `API_USERNAME_*`, `API_PASSWORD_*`). For cars: `CAR_API_*`. For PCB: merchant id, URLs, and certificate paths.

## Verify Installation

1. `storage/installed` exists (or installer completed).
2. Open `APP_URL` — homepage should render hotel search (`Hotel::frontend.form-search-ha`).
3. Admin: `{APP_URL}/{admin-prefix}` (default prefix `admin`).
4. Optional diagnostics: `GET /hotel/test-api-credentials` (intended for non-production), `GET /pcb-test` (PCB config presence only).

## Related Documentation

- [Configuration](./06-configuration.md)
- [Running & development commands](./23-build-and-development-tools.md)
- [Troubleshooting](./26-troubleshooting.md)
