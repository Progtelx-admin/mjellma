# Route list

This file is the closest equivalent to `php artisan route:list` that can be produced **from source**.

On this machine, `php` was **not on PATH**, so a live Artisan dump could not be captured. On a working Laragon PHP CLI:

```powershell
cd C:\laragon\www\mjellma
php artisan route:list
```

Useful filters:

```powershell
php artisan route:list --path=hotel
php artisan route:list --path=admin
php artisan route:list --name=hotel.
php artisan route:list --columns=method,uri,name,action
```

Output is large: each module registers **web + locale-prefixed `language.php` copies** when multi-language routes are enabled (`is_enable_language_route()`). Hotel `language.php` is `include 'web.php'`, so HA routes appear twice (`/` and `/{locale}/`).

Related: [API](./10-API.md) · [Admin routes](./25-ADMIN-ROUTES.md)

---

## How routes are registered

| Loader | Files |
|---|---|
| `App\Providers\RouteServiceProvider` | `routes/web.php`, `routes/api.php` (`/api`), `routes/language.php` |
| `App\Providers\AdminRouteServiceProvider` | `routes/admin.php` (commented / empty) |
| Each `*\RouterServiceProvider` | `modules/*/Routes/{web,admin,api,language}.php` |
| Fortify | `/login`, password reset, 2FA, verify (prefix `''` in `config/fortify.php`) |
| Chatify | prefix `CHATIFY_ROUTES_PREFIX` default `chatify` |

---

## Root `routes/web.php`

| Method | URI | Name | Action |
|---|---|---|---|
| GET | `/intro` | | `LandingpageController@index` |
| GET | `/home` | `home` | `HomeController@index` |
| POST | `/install/check-db` | | `HomeController@checkConnectDatabase` |
| GET | `social-login/{provider}` | | `Auth\LoginController@socialLogin` |
| GET | `social-callback/{provider}` | | `Auth\LoginController@socialCallBack` |
| GET | `{admin}/logs` | `admin.logs` | Log viewer |
| GET | `/install` | `LaravelInstaller::welcome` | `InstallerController@redirectToRequirement` |
| GET | `/install/environment` | `LaravelInstaller::environment` | `InstallerController@redirectToWizard` |
| GET | `/update` `/update/overview` `/update/database` | | `InstallerController@redirectToHome` |
| GET | `/pcb-test` | `pcb.test` | closure → `PcbBankService` JSON |
| GET | `/pcb-test-order` | `pcb.test.order` | closure createOrder 10.00 |
| GET | `/pcb-test-redirect` | `pcb.test.redirect` | HTML stub |
| fallback | * | | `Modules\Core\Controllers\FallbackController@FallBack` |

Homepage `/` is **not** here; it is `hotel.show` in Hotel `web.php`.

---

## Root `routes/api.php`

| Method | URI | Middleware | Action |
|---|---|---|---|
| GET | `/api/user` | `auth:sanctum` | current user JSON |

Full `/api/*` app API: [API](./10-API.md) (`modules/Api/Routes/api.php`).

---

## Hotel HA (web, no `/admin` prefix)

From `modules/Hotel/Routes/web.php`. Names as registered.

| Method | URI | Name |
|---|---|---|
| GET | `/` | `hotel.show` |
| GET | `/hotels/search` | `hotel.search` |
| GET | `/hotel-suggestions` | `hotel.suggestions` |
| GET | `/hotel/{id}` | `hotel.info` |
| POST | `/hotel/prebook` | `hotel.prebook` |
| GET | `/hotel/prebook/result` | `hotel.prebook.result` |
| GET | `/hotel/booking/failed` | `hotel.booking.failed` |
| POST | `/hotel/book` | `hotel.book` |
| GET | `/hotel/booking/confirmation/{book_hash}` | `hotel.booking.confirmation` |
| POST | `/hotel/payment` | `hotel.payment` |
| POST | `/hotel/booking/complete` | `hotel.booking.complete` |
| POST | `/hotel/booking/finish` | `hotel.booking.finish` |
| GET | `/hotel/payment/success` | `hotel.payment.success` |
| POST | `/hotel/booking/handle` | `hotel.booking.handle` |
| GET | `/pcb-return` | `pcb.booking.return` |
| GET | `/hotel/payment/confirm` | `hotel.payment.confirm` |
| GET | `/hotel/test-api-credentials` | `hotel.test.api.credentials` |
| GET | `/booking` | `hotel.admin.booking.index` |
| GET | `/booking-history` | `hotel.booking.index` |
| GET | `/booking/{orderId}/invoice` | `hotel.booking.invoice` |
| GET | `/bookings/{orderId}/details` | `booking.admin.details` |
| POST | `/bookings/{partnerOrderId}/cancel` | `booking.admin.cancel` |
| POST | `{hotel_prefix}/checkAvailability` | `hotel.checkAvailability` |

Vendor hotel routes: `user/{hotel_prefix}/...` with `auth`+`verified` (see same file).

---

## Booking Core checkout

Prefix default `/booking` (`BOOKING_ROUTER_PREFIX`).

| Method | URI | Name |
|---|---|---|
| POST | `/booking/addToCart` | (unnamed in file) |
| POST | `/booking/doCheckout` | `booking.doCheckout` |
| GET | `/booking/confirm/{gateway}` | `booking.confirm-payment` |
| GET | `/booking/cancel/{gateway}` | (unnamed) |
| GET | `/booking/{code}` | (unnamed) |
| GET | `/booking/{code}/checkout` | `booking.checkout` |
| GET | `/booking/{code}/check-status` | (unnamed) |
| GET | `/booking/export-ical/{type}/{id}` | `booking.admin.export-ical` |
| POST | `/booking/addEnquiry` | (unnamed) |
| POST | `/booking/setPaidAmount` | `booking.setPaidAmount` |
| GET | `/booking/modal/{booking}` | `booking.modal` |
| GET | `/gateway/confirm/{gateway}` | `gateway.confirm` |
| GET | `/gateway/cancel/{gateway}` | `gateway.cancel` |
| GET | `/gateway/info` | `gateway.info` |
| GET/POST | `/gateway/gateway_callback/{gateway}` | `gateway.webhook` |

---

## Car

Prefix default `/car`.

| Method | URI | Name |
|---|---|---|
| GET | `/car` | `car.search` |
| GET | `/car/search` | `car.do_search` |
| POST | `/car/checkout` | `car.checkout` |
| GET | `/car/checkout/form` | `car.checkout.form` |
| POST | `/car/checkout/store` | `car.checkout.store` |
| GET | `/car/checkout/confirm` | `car.checkout.confirm` |
| GET | `/car/pcb/return` | `car.pcb.return` |
| GET | `/car/{slug}` | `car.detail` |
| GET | `/car/api/locations` | `car.api.locations` |
| GET | `/car/api/reservations` | `car.api.reservations` |
| GET | `/car/api/refresh-locations` | `car.api.refresh_locations` |
| GET | `/car/api/debug` | `car.api.debug` |
| GET | `/car/api/manufacturers` | `car.api.manufacturers` |
| GET | `/car/api/categories` | `car.api.categories` |
| GET | `/car/api/transmissions` | `car.api.transmissions` |
| GET | `/car/api/load-more` | `car.api.load_more` |

`{slug}` is declared **before** `/car/api/*` in the file — `/car/api/locations` may match `detail`. Confirm with a live `route:list`.

---

## Other public prefixes (default)

| Prefix env | Default URI | Search route name |
|---|---|---|
| `TOUR_ROUTER_PREFIX` | `/tour` | `tour.search` |
| `SPACE_ROUTER_PREFIX` | `/space` | (module web.php) |
| `EVENT_ROUTER_PREFIX` | `/event` | |
| `BOAT_ROUTER_PREFIX` | `/boat` | |
| `FLIGHT_ROUTE_PREFIX` | `/flight` | `flight.search` |
| `PAGE_ROUTER_PREFIX` | `/page` | |
| `LOCATION_ROUTER_PREFIX` | `/location` | |
| `NEWS_ROUTER_PREFIX` | `/news` | |
| Offers (hardcoded) | `/offers` | `offers.public.index` |

User area: `/user/...` (`modules/User/Routes/web.php`). Register: `/register`.

---

## JSON API (`modules/Api/Routes/api.php`)

All under `/api` + `set_language_for_api`. See [API](./10-API.md) for the full table (`auth/login`, `{type}/search`, `/api/booking/...`, news, media, etc.).

---

## Admin

Full tables: [Admin routes](./25-ADMIN-ROUTES.md).

---

## What a live dump would add

Artisan would also list:

- Fortify named routes (`login`, `password.request`, …)
- Chatify package routes
- Locale-prefixed duplicates
- Closure routes without names
- Middleware columns

Regenerate after deploy if `ADMIN_ROUTER_PREFIX` or other prefix env vars change.
