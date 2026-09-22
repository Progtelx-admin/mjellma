# Backend

## Overview

Backend is Laravel 10 with modular providers. Controllers orchestrate Eloquent models, helpers, and external HTTP/payment services.

## Request Lifecycle

```text
HTTP Request
    ↓
public_html/index.php
    ↓
Global middleware (TrustProxies, RedirectToInstaller, CORS, …)
    ↓
Route middleware group (web | api)
    ↓
Route (routes/* or module Routes)
    ↓
Controller action
    ↓
Validation / authorization
    ↓
Service / Model / External API
    ↓
Blade view | JSON | redirect
```

## Routing

| File / area | Role |
| --- | --- |
| `routes/web.php` | Home alias, installer, social login, PCB test routes, fallback |
| `routes/api.php` | Minimal `GET /api/user` (Sanctum) |
| `routes/admin.php` | Admin route loading patterns |
| `modules/*/Routes/web.php` | Module public + user routes |
| `modules/*/Routes/admin.php` | Admin CRUD |
| `modules/Api/Routes/api.php` | Mobile JSON API |

Admin prefix comes from dashboard config (`ADMIN_ROUTER_PREFIX`, default `admin`).

## Controllers

- App: `app/Http/Controllers` (Home, Installer, Auth social, …)
- Modules: `modules/{Module}/Controllers` and `Admin`
- Critical hotel flow: `modules/Hotel/Controllers/HotelHController.php`
- Car API flow: `modules/Car/Controllers/CarController.php`
- API: `modules/Api/Controllers/*`

See [Controllers](./reference/controllers.md).

## Middleware (aliases)

Defined in `app/Http/Kernel.php`:

| Alias | Purpose |
| --- | --- |
| `auth` | Authenticate |
| `guest` | Redirect if authenticated |
| `verified` | Email verified |
| `dashboard` | Admin dashboard access |
| `translation_manager` | Translation admin |
| `system_log_view` | Log viewer permission |
| `set_language_for_api` | API locale |
| `pro_plan` | Pro plan gate |

Web group also applies CSRF, session, multi-language redirect, currency, admin language, require-change-password.

## Models & Persistence

Eloquent models live primarily under `modules/*/Models` and `app/User.php`. Bookable types often extend `Modules\Booking\Models\Bookable`.

## Services & Gateways

- `App\Services\PcbBankService` — PCB order API with client certificates
- `App\Services\InvoiceService` — invoices
- `Modules\Booking\Gateways\*` — payment gateways registered in `config/payment.php`

## Events & Listeners

Registered in `app/Providers/EventServiceProvider` and module providers. Notable: `MjellmaBookingCreatedEvent` → notification listener; booking created/updated emails/SMS; vendor/user lifecycle mails.

## Console

Commands under `app/Console/Commands` include plan expiry scan and PCB diagnostic/enable helpers. Schedule runs plan expiry daily.

## Validation

Form requests and inline `$request->validate` / Validator facades in controllers (API AuthController is a clear example).

## Responses

- Blade views with theme namespaces (`Hotel::…`, `Layout::…`)
- JSON for API and AJAX search fragments
- Redirects for payment HPP returns

## Related Documentation

- [API](./11-api.md)
- [Authentication](./10-authentication-and-authorization.md)
- [Services](./reference/services.md)
- [Routes](./reference/routes.md)
