# Configuration

## Overview

Configuration is split between `.env`, `config/*.php`, admin settings stored in the database (via Core settings UI), and theme/module config merges.

## Environment File

- Template: `.env.example`
- Runtime: `.env` (not for commit)
- Composer `post-root-package-install` copies the example if `.env` is missing
- Installer / `public_html/index.php` may also copy the example when installing

Use placeholders only when documenting values. Full catalog: [Environment Variables](./reference/environment-variables.md).

## Important Config Files

| File | Responsibility |
| --- | --- |
| `config/app.php` | Name, URL, locale, providers, version `3.4.2` |
| `config/bc.php` | Active theme (`BC_ACTIVE_THEME`) |
| `config/auth.php` | Guards/providers (`web`) |
| `config/fortify.php` | Fortify features and views |
| `config/sanctum.php` | API token settings |
| `config/database.php` | DB connections |
| `config/payment.php` | Payment gateway class map |
| `config/pcb_bank.php` | PCB Bank merchant/API/certs/status maps |
| `config/booking.php` | Booking route prefix |
| `config/hotel.php` | Hotel route prefix / module settings |
| `config/car.php` | Car route prefix |
| `config/permissions.php` | Permission definitions |
| `config/services.php` | Social, reCAPTCHA, Stripe-related env bindings |
| `config/modules.php` | Legacy module lists — **not** used by current theme loader |
| `config/jwt.php` | Legacy JWT config file; API uses Sanctum |

## Admin / Database Settings

Many runtime options (currencies, booking rules, email templates, gateway enablement) are managed in Admin → Settings and persisted in core settings tables, read through helpers such as `setting_item()` in `app/Helpers/AppHelper.php`.

## Theme Configuration

- Active theme: `config('bc.active_theme')` → default `BC`
- Theme providers load module list and view paths

## Route Prefixes

Configurable via env (defaults shown):

| Prefix purpose | Env | Default |
| --- | --- | --- |
| Booking | `BOOKING_ROUTER_PREFIX` | `booking` |
| Car | `CAR_ROUTER_PREFIX` | `car` |
| Hotel | hotel config | typically `hotel` |
| Admin | `ADMIN_ROUTER_PREFIX` | `admin` (via dashboard config) |

## PCB Certificates

Paths default to `storage/certs/{cert,key,ca}.pem` and can be overridden with `PCB_BANK_*_PATH`. Place real certificate materials outside version control.

## Media / HTTPS

- `APP_HTTPS` — HTTPS-related behavior
- `APP_RESIZE_SIMPLE`, `APP_PREVIEW_MEDIA_LINK` — media helpers

## Related Documentation

- [Environment Variables](./reference/environment-variables.md)
- [Security](./25-security.md)
- [Deployment](./24-deployment.md)
