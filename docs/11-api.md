# API

## Overview

Two API surfaces exist:

1. **Module API** — `modules/Api/Routes/api.php` under prefix `/api` with `api` + `set_language_for_api` middleware
2. **Root** — `routes/api.php` exposes `GET /api/user` with `auth:sanctum`

Sample payloads below use fake data only.

## Authentication

| Endpoint | Method | Auth | Purpose |
| --- | --- | --- | --- |
| `/api/auth/login` | POST | Public (throttled) | Issue Sanctum token |
| `/api/auth/register` | POST | Public | Create user if registration enabled |
| `/api/auth/logout` | POST | Sanctum | Revoke token |
| `/api/auth/refresh` | POST | Sanctum | Refresh session/token flow as implemented |
| `/api/auth/me` | GET/POST | Sanctum | Profile read/update |
| `/api/auth/change-password` | POST | Sanctum | Password change |

Example login response shape:

```json
{
  "access_token": "<sanctum-plain-text-token>",
  "user": { "id": 1, "email": "user@example.com" },
  "status": 1
}
```

## Service Search & Detail

| Endpoint | Purpose |
| --- | --- |
| `GET /api/configs` | App configs for clients |
| `GET /api/services` | Multi-service search |
| `GET /api/{type}/search` | Typed search |
| `GET /api/{type}/detail/{id}` | Detail |
| `GET /api/{type}/availability/{id}` | Availability |
| `GET /api/boat/availability-booking/{id}` | Boat-specific availability |
| `GET /api/{type}/filters` | Filters |
| `GET /api/{type}/form-search` | Search form schema |
| `POST /api/{type}/write-review/{id}` | Write review |

`{type}` corresponds to bookable service keys (hotel, tour, car, …) as implemented in `SearchController`.

## Booking API

Under booking route prefix (default `booking`):

| Endpoint | Purpose |
| --- | --- |
| `POST /api/booking/addToCart` | Add to cart |
| `POST /api/booking/addEnquiry` | Enquiry |
| `POST /api/booking/doCheckout` | Checkout |
| `GET /api/booking/confirm/{gateway}` | Confirm payment |
| `GET /api/booking/cancel/{gateway}` | Cancel payment |
| `GET /api/booking/{code}` | Booking detail |
| `GET /api/booking/{code}/checkout` | Checkout page data |
| `GET /api/booking/{code}/check-status` | Status poll |

Also: `GET /api/gateways`, home layout, locations, news, wishlist, booking history, media upload (`auth:sanctum`).

## Web “APIs” Used by the HA Hotel UI

Not under `/api`, but JSON/HTML endpoints used by the browser:

| Route name | Path (typical) | Purpose |
| --- | --- | --- |
| `hotel.search` | `/hotels/search` | Search results |
| `hotel.map` | `/hotels/map` | Map data |
| `hotel.suggestions` | `/hotel-suggestions` | Autocomplete |
| `hotel.prebook` | `POST /hotel/prebook` | ETG prebook |
| `hotel.book` | `POST /hotel/book` | Create booking |
| `hotel.test.api.credentials` | `/hotel/test-api-credentials` | Diagnostics (non-production) |

## External APIs

Documented in [Integrations](./17-integrations.md):

- ETG B2B (`API_URL`, Basic Auth, B2B/B2C credential pairs)
- Car API (`CAR_API_BASE`, token, referer)
- PCB Bank mTLS order API

## Errors

API controllers commonly return structured error payloads via helper methods (`sendError`) with validation error bags and codes such as `invalid_credentials`.

## Related Documentation

- [Routes](./reference/routes.md)
- [Authentication](./10-authentication-and-authorization.md)
- [Integrations](./17-integrations.md)
