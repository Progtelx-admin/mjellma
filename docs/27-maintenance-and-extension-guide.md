# Maintenance and Extension Guide

## Overview

Extend the app using existing Booking Core module/theme patterns. Do not introduce a parallel architecture.

## Adding Backend Functionality

1. Prefer an existing module under `modules/{Name}` or create a module with `ModuleProvider`.
2. Register the provider in `Themes\Base\ThemeProvider::$modules` **or** `config/app.php` providers (Offers pattern).
3. Add routes under `Routes/web.php` / `admin.php`.
4. Implement controllers + Eloquent models + migrations.
5. Register permissions in the module boot/`PermissionHelper` flow.
6. Add tests when practical.

## Adding Frontend Pages

1. Add Blade views under `modules/.../Views` or override in `themes/BC/{Module}/Views`.
2. Keep namespaced calls (`Module::frontend.view-name`).
3. Extend BC Layout parts only when global chrome must change.
4. For admin Vue widgets, add components under `public_html/themes/admin` and register in `app.js` if needed.
5. Rebuild Mix assets after Sass/Vue changes.

## Adding an API Endpoint

1. Add route in `modules/Api/Routes/api.php` (or module api routes if applicable).
2. Implement controller under `modules/Api/Controllers`.
3. Protect with `auth:sanctum` when user-specific.
4. Return consistent JSON success/error shapes.

## Adding a Database Table

1. Create migration in `database/migrations` or the owning module.
2. `php artisan migrate`
3. Add/update Eloquent model
4. Document in `docs/reference/database-tables.md`

## Adding Validation

Use Form Request classes or controller `validate()` consistent with nearby code. Surface errors to Blade or API error bags.

## Adding Permissions

1. Define permission keys
2. Register via User permission helper / module provider
3. Gate admin menu items and controller actions
4. Assign to roles in admin UI

## Adding Translations

Add keys under `lang/{locale}` and/or Language admin tools. Keep `en` complete as fallback.

## Adding Integrations

1. Prefer a dedicated service class under `app/Services` or module Services
2. Read secrets only from `env()` / config
3. Centralize HTTP options (timeouts, IP family) like hotel/car clients
4. Never commit credentials; update `environment-variables.md` with **names only**

## Safe Change Checklist

- Match existing naming (`bravo_*` vs `mjellma_*` vs `hotels`)
- Preserve CSRF on web posts
- Keep PCB certs private
- Avoid editing `vendor/` or compiled `dist/` by hand—rebuild instead
- Update documentation when behavior changes

## Related Documentation

- [Project Architecture](./03-project-architecture.md)
- [Backend](./08-backend.md)
- [Frontend](./09-frontend.md)
- [Important Files](./reference/important-files.md)
