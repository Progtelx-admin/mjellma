# Localization

## Overview

The application supports multiple locales with English as the default/fallback (`config/app.php`: `locale` / `fallback_locale` = `en`). Albanian (`sq`) language files are present.

## Architecture

| Layer | Location |
| --- | --- |
| Root lang files | `lang/en`, `lang/sq` (+ PHP files like `hotel.php`) |
| Resources lang | `resources/lang/en`, `resources/lang/sq`, JSON catalogs |
| Language module | `modules/Language` — models, admin CRUD, switcher, translation UI |
| Middleware | `RedirectForMultiLanguage`, `SetLanguageForAdmin`, `SetLanguageForApi` |

## Runtime Behavior

- Frontend locale stored in session key `website_locale` (Language controller)
- Admin locale via cookie `bc_admin_locale`
- API middleware can set language for `/api` requests

## Routes

- `language/set-lang/{locale}`
- `language/set-admin-lang/{locale}`

## Usage In Code

- PHP/Blade: `__('key')`, `@lang`, trans helpers
- Hotel-specific copy: `lang/*/hotel.php`

## Adding Translations

1. Add keys to the appropriate `lang/{locale}` files or via Admin translation manager (permission-gated).
2. Keep English fallback keys complete.
3. Avoid dumping secrets or PII into translation strings.

## Adding a Language

1. Create language record via Language admin (DB-driven languages).
2. Add `lang/{code}` resources as needed.
3. Test switcher and admin locale cookie behavior.

## Related Documentation

- [Frontend](./09-frontend.md)
- [Configuration](./06-configuration.md)
