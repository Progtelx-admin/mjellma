# Development Guide

How to extend **this** tree, matching existing patterns.

Related: [Architecture](./03-ARCHITECTURE.md) · [Modules](./24-MODULES.md) · [Backend](./09-BACKEND.md)

---

## Mental model

- Bookable/CMS features live in `modules/{Name}/` with `ModuleProvider` + `RouterServiceProvider`.
- Site-specific extras that are not Booking Core modules go in `custom/{Name}/` (see `CarRent`).
- Optional payments: `plugins/`.
- Paid Booking Core add-ons: `pro/`.
- Blade overrides: `themes/BC/...` (same view names as `modules/*/Views`).
- ETG hotel HA logic is concentrated in **one controller**: `modules/Hotel/Controllers/HotelHController.php`. Prefer extracting a client class if you grow it; currently there is no `EtgClient`.

---

## Where to add frontend functionality

| Kind | Where |
|---|---|
| Public Blade | `themes/BC/{Module}/Views/frontend/` so it wins over module views |
| Shared layout | `modules/Layout/` or `themes/BC` layout overrides |
| Admin Blade | `modules/{Module}/Views/admin/` |
| Admin Vue | `public_html/themes/admin/js/` then Mix watch |
| Public Sass | `public_html/sass/` or `public_html/module/{service}/scss/` and Mix in `public_html/themes/bc` |
| Page-builder blocks | `modules/{Module}/Blocks/` + `getTemplateBlocks()` on the ModuleProvider |

Hotel HA pages are named `*-ha.blade.php` to distinguish from legacy hotel views.

---

## Where to add backend endpoints

1. Add a route in the module `Routes/web.php`, `admin.php`, or `api.php`.
2. Implement a method on an existing controller or a new class under `modules/{Module}/Controllers` or `Admin`.
3. Admin routes: prefix is already `{admin}/module/{module}` from that module’s `RouterServiceProvider`.
4. Root-only routes (installer, PCB tests) go in `routes/web.php`.

Follow existing names: `hotel.search`, `car.do_search`, `admin.index`.

---

## Where services belong

- Cross-cutting HTTP (PCB): `app/Services/`
- Booking payments: `modules/Booking/Gateways/` + `config/payment.php`
- ETG: today inside `HotelHController`; a new `app/Services` or `modules/Hotel/Services` class would match Laravel style but does not exist yet
- Helpers: `app/Helpers/AppHelper.php` (global functions). `custom/Helpers/CustomHelper.php` is empty.

---

## Where API clients belong

- Laravel HTTP: `Illuminate\Support\Facades\Http` (hotel, car, PCB)
- Omnipay: gateway classes
- Do not put secrets in PHP; use `env()` / settings. Do not copy the Python hardcoded-credential pattern.

---

## Where database models belong

- Booking Core models: `modules/{Module}/Models/`
- App-level: `app/Models/` (`Invoice`, `User` subclass)
- User canonical class: `app/User.php`
- Migrations: module `Migrations/` or `database/migrations/` for app-wide tables (`mjellma_bookings`, `invoices`)
- ETG `hotels` table is owned by Python; add a Laravel migration if you need schema in `migrate` for new environments

---

## How to add environment variables

1. Add to `.env` locally (never commit secrets).
2. Read via `env('NAME')` in `config/*.php` preferred over scattering `env()` in controllers (hotel/car currently call `env()` directly).
3. Document in [Environment Variables](./06-ENVIRONMENT-VARIABLES.md).
4. `.env.example` should get a **placeholder** key with empty value.

---

## How to add a new module

Pattern from existing modules:

1. `modules/NewThing/ModuleProvider.php` extending `Modules\ModuleServiceProvider`
2. Register `RouterServiceProvider`
3. Add to `Themes\Base\ThemeProvider::$modules` **or** `config/app.php` providers (Offers is registered in `config/app.php` because it is not in `$modules`)
4. `getAdminMenu()`, permissions via `PermissionHelper::add`
5. Views folder so `Modules\ServiceProvider` can `loadViewsFrom`

Custom namespace: put it under `custom/` so `Custom\ServiceProvider` auto-registers `ModuleProvider`.

---

## How to add a new script

- PHP CLI: `app/Console/Commands/{Name}.php` with `$signature`; auto-loaded from `app/Console/Kernel.php` `load(__DIR__.'/Commands')`
- Schedule: `app/Console/Kernel.php` `schedule()`
- npm: theme `package.json`, not root Mix unless you add a root `webpack.mix.js`
- Python: root script with `if __name__ == "__main__"`; add a real `requirements.txt` if you keep Python (none exists today)

---

## How to add tests

- Feature: `tests/Feature/YourTest.php` extending `Tests\TestCase`
- Unit: `tests/Unit/`
- Configure `phpunit.xml` DB to sqlite or a test MySQL; do not use production
- Run `php artisan test --filter=YourTest`

There is almost no coverage today ([Testing](./16-TESTING.md)).

---

## Naming / structure conventions visible in the project

- Module folders PascalCase (`Hotel`, `CarRent`)
- Admin controllers in `Admin/` namespace, frontend in `Controllers/`
- Route names dotted: `hotel.admin.room.index`, `user.profile.index`
- Settings keys and `setting_item('hotel_...')`
- Bookable Eloquent models extend `Modules\Booking\Models\Bookable`
- HA (hotel API) views suffix `-ha`
- Config route prefixes `*_ROUTER_PREFIX` / `*_ROUTE_PREFIX` (inconsistent underscore: `FLIGHT_ROUTE_PREFIX` vs `HOTEL_ROUTER_PREFIX`)

---

## Local workflow

1. Laragon + `public_html` docroot
2. `.env` with ETG B2B/B2C keys
3. `hotels` table populated
4. Mix watch only when editing Sass/Vue
5. Logs: `storage/logs`

Do not commit `.env`, PEM certs, or Python passwords.
