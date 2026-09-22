# Error Handling and Logging

## Overview

Laravel’s exception handler plus module/controller-level error responses drive user feedback. Logs go to the configured channel (default **daily** files under `storage/logs`).

## Backend Exceptions

- `app/Exceptions/Handler.php` — framework reporting/rendering
- Controllers catch HTTP/API failures (ETG/car/PCB) and return redirects, error views, or JSON
- Validation failures: redirect back with errors or API error bags

## Frontend Errors

- Session flash / `$errors` in Blade
- AJAX search error handling in jQuery scripts
- HA booking failed route/view
- Payment pending/failed blades

## API Errors

`AuthController` and other API controllers return structured `sendError` payloads with optional `errors` and `code` fields (e.g. `invalid_credentials`).

## Logging

| Item | Detail |
| --- | --- |
| Config | `config/logging.php` |
| Env | `LOG_CHANNEL`, `LOG_LEVEL`, deprecations channel |
| Admin viewer | `rap2hpoutre/laravel-log-viewer` at admin logs route with `system_log_view` |

Never paste production log snippets containing tokens, cards, or personal data into tickets or docs.

## External Integration Failures

| Integration | Typical handling |
| --- | --- |
| ETG | Empty results / API error fields on booking (`api_status`, `api_error` on mjellma bookings) |
| Car API | HTTP client errors surfaced in controller/debug routes |
| PCB | `isConfigured()` checks; order create failures; status not in success list |

## Related Documentation

- [Troubleshooting](./26-troubleshooting.md)
- [Security](./25-security.md)
