# Mjellma

Mjellma is a customized **Booking Core** travel booking platform on **Laravel 10**. It powers hotel search and booking through the ETG / RateHawk / WorldOTA B2B API, car rental via an external partner API, classic bookable services, CMS/admin tools, and multi-gateway payments (with PCB Bank as a primary card flow).

## Overview

The application is a modular Laravel monolith. Domain features live in `modules/`, UI overrides in `themes/BC`, and the web document root is `public_html/`. Guests search live hotel rates against a local hotel catalog plus ETG; operators manage bookings, content, users, and settings in the admin panel. A Sanctum-authenticated JSON API under `/api` supports mobile-style clients.

## Main Features

- ETG hotel search, prebook, PCB payment, and order completion
- External car rental search and checkout
- Tours, spaces, events, flights, boats (Booking Core modules)
- Users, roles/permissions, vendors, wallets/plans
- CMS: pages, news, templates, media, popups, offers
- Payments: PCB Bank, PayPal, Stripe, Payrexx, Paystack, offline (+ TwoCheckout plugin)
- Multi-language UI (English / Albanian packs present)
- Admin dashboard and reporting

## Technology Stack

| Layer | Technology |
| --- | --- |
| Backend | PHP 8.1+, Laravel 10, Fortify, Sanctum, Socialite |
| Database | MySQL |
| Frontend | Blade, jQuery, Vue 2 (admin), Sass, Laravel Mix |
| Integrations | ETG/WorldOTA, external car API, PCB Bank (mTLS) |
| Tooling | Composer, npm/yarn, PHPUnit, optional Python dump scripts |

## Project Architecture

```text
Browser → public_html/index.php → Laravel
        → themes (BC) + modules
        → MySQL
        → ETG / Car API / PCB / Mail
```

See [docs/03-project-architecture.md](docs/03-project-architecture.md) for diagrams and detail.

## Project Structure

| Path | Role |
| --- | --- |
| `app/` | Core application, services, middleware |
| `modules/` | Domain modules |
| `themes/` | Base + BC theme |
| `public_html/` | Web root and compiled assets |
| `config/` | Configuration |
| `database/` | Migrations & seeders |
| `docs/` | Technical handbook |
| `hotels_data.py` | Hotel catalog import (optional ops) |

## Requirements

- PHP 8.1+
- Composer
- MySQL
- Node.js + npm or yarn (for asset builds)
- Web server with document root set to `public_html/`
- Optional: Redis, Python 3 (hotel dumps), PCB TLS certificates

## Installation

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

Create the MySQL database, set `DB_*` and `APP_URL` in `.env`, point the vhost at `public_html/`, then either complete `/install` or run:

```powershell
php artisan migrate
php artisan db:seed
```

Full steps: [docs/05-installation-and-setup.md](docs/05-installation-and-setup.md).

## Configuration

Copy `.env.example` to `.env` and set placeholders only in your private environment:

- Core: `APP_*`, `DB_*`
- Hotels: `API_URL`, `API_USERNAME_*`, `API_PASSWORD_*`
- Cars: `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER`
- PCB: `PCB_BANK_*` and certificate paths

Never commit secrets. Reference: [docs/reference/environment-variables.md](docs/reference/environment-variables.md).

## Running the Project

This is a single Laravel app (no separate Node backend).

1. Serve via Laragon/Apache with docroot `public_html/`, or `php artisan serve`
2. Open `APP_URL` — homepage is hotel search
3. Admin: `{APP_URL}/admin` (prefix configurable)
4. Queues default to `sync` (no worker required unless changed)
5. Scheduler (optional): `php artisan schedule:run` via system cron

Asset watch (when editing Sass/Vue):

```powershell
cd public_html\themes\admin
npm run watch

cd ..\bc
npx mix watch
```

## Development

- Prefer extending modules/themes rather than editing `vendor/`
- Rebuild Mix outputs after frontend changes
- Clear caches after env changes: `php artisan config:clear`
- Details: [docs/23-build-and-development-tools.md](docs/23-build-and-development-tools.md) and [docs/27-maintenance-and-extension-guide.md](docs/27-maintenance-and-extension-guide.md)

## Testing

```powershell
php artisan test
```

PHPUnit stubs exist under `tests/`; domain coverage is currently minimal.

## Build

Production PHP: `composer install --no-dev --optimize-autoloader` plus `config:cache` / `route:cache` as appropriate.

Frontend production Mix builds from `public_html/themes/admin` and `public_html/themes/bc`.

## Documentation

Complete technical documentation:

**[docs/README.md](docs/README.md)**

## Troubleshooting

See **[docs/26-troubleshooting.md](docs/26-troubleshooting.md)** for installer, database, ETG, car API, PCB, and asset issues.

## Security

- Keep `.env`, API passwords, PCB PEMs, and tokens out of git
- Use HTTPS in production; set `APP_DEBUG=false`
- Restrict diagnostic routes (`/pcb-test*`, `/hotel/test-api-credentials`)
- More: [docs/25-security.md](docs/25-security.md)

## Additional Documentation

- [Project overview](docs/01-project-overview.md)
- [Architecture](docs/03-project-architecture.md)
- [Database](docs/07-database.md)
- [Authentication](docs/10-authentication-and-authorization.md)
- [API](docs/11-api.md)
- [Features](docs/13-features.md)
- [Integrations](docs/17-integrations.md)
- [Routes reference](docs/reference/routes.md)
