# Routes Reference

Organized by area. Prefixes may change via env (`BOOKING_ROUTER_PREFIX`, `CAR_ROUTER_PREFIX`, admin prefix). Do not treat diagnostic routes as public production API.

## Public / Core Web (`routes/web.php`)

| Method | Path | Handler | Auth | Purpose |
| --- | --- | --- | --- | --- |
| GET | `/intro` | LandingpageController@index | Public | Landing intro |
| GET | `/home` | HomeController@index | Public | Classic home (named `home`) |
| GET | `/` | *(module)* HotelHController@showHotels | Public | Hotel homepage |
| POST | `/install/check-db` | HomeController@checkConnectDatabase | Public | Installer DB check |
| GET | `social-login/{provider}` | Auth\LoginController@socialLogin | Public | OAuth start |
| GET | `social-callback/{provider}` | Auth\LoginController@socialCallBack | Public | OAuth callback |
| GET | `{admin}/logs` | LogViewerController | auth+dashboard+system_log_view | Log UI |
| GET | `/install` … | InstallerController | Public | Installer |
| GET | `/pcb-test` | Closure | Public | PCB config diagnostic |
| GET | `/pcb-test-order` | Closure | Public | PCB test order |
| GET | `/pcb-test-redirect` | Closure | Public | PCB test return |
| * | fallback | FallbackController | — | 404-style fallback |

Note: classic `HomeController@index` on `/` is commented out.

## Hotel HA (`modules/Hotel/Routes/web.php`)

| Method | Path | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/` | `hotel.show` | Homepage hotels |
| GET | `/hotels/search` | `hotel.search` | Search |
| GET | `/hotels/map` | `hotel.map` | Map |
| GET | `/hotel-suggestions` | `hotel.suggestions` | Autocomplete |
| POST | `/hotel/prebook` | `hotel.prebook` | Prebook |
| GET | `/hotel/prebook/result` | `hotel.prebook.result` | Prebook result |
| POST | `/hotel/book` | `hotel.book` | Book |
| GET | `/hotel/booking/confirmation/{book_hash}` | `hotel.booking.confirmation` | Confirmation |
| POST | `/hotel/booking/handle` | `hotel.booking.handle` | Start payment |
| GET | `/pcb-return` | `pcb.booking.return` | PCB return |
| GET | `/hotel/payment/confirm` | `hotel.payment.confirm` | After PCB |
| POST | `/hotel/payment` | `hotel.payment` | Process payment |
| POST | `/hotel/booking/finish` | `hotel.booking.finish` | Finish ETG |
| POST | `/hotel/booking/complete` | `hotel.booking.complete` | Complete |
| GET | `/hotel/{id}` | `hotel.info` | Detail |
| GET | `/hotel/test-api-credentials` | `hotel.test.api.credentials` | Credential diagnostics |
| GET | `/booking` | `hotel.admin.booking.index` | Booking list UI |
| GET | `/booking-history` | `hotel.booking.index` | History |
| GET/POST | invoice/details/cancel routes | various | Ops |

Vendor hotel CRUD under `user/{hotel_prefix}` with `auth`+`verified`.

## Car (`modules/Car/Routes/web.php`)

Prefix: `config('car.car_route_prefix')` (env `CAR_ROUTER_PREFIX`, default `car`).

| Method | Path (under prefix) | Name | Purpose |
| --- | --- | --- | --- |
| GET | `/` | `car.search` | Search page |
| GET | `/search` | `car.do_search` | Perform search |
| POST | `/checkout` | `car.checkout` | Reservation details |
| GET | `/checkout/form` | `car.checkout.form` | Customer form |
| POST | `/checkout/store` | `car.checkout.store` | Submit booking |
| GET | `/checkout/confirm` | `car.checkout.confirm` | Confirmation |
| GET | `/pcb/return` | `car.pcb.return` | PCB return |
| GET | `/{slug}` | `car.detail` | Detail |
| GET | `/api/locations` | `car.api.locations` | Locations |
| GET | `/api/reservations` | `car.api.reservations` | Reservations |
| GET | `/api/manufacturers` | `car.api.manufacturers` | Manufacturers |
| GET | `/api/categories` | `car.api.categories` | Categories |
| GET | `/api/transmissions` | `car.api.transmissions` | Transmissions |
| GET | `/api/load-more` | `car.api.load_more` | Pagination |
| GET | `/api/debug` | `car.api.debug` | Debug (protect in production) |
| GET | `/api/refresh-locations` | `car.api.refresh_locations` | Refresh cache |

Vendor manage under `user/{car_prefix}` with `auth`+`verified`.

## Booking (`modules/Booking/Routes/web.php`)

Cart/checkout/confirm/cancel/detail under booking prefix; gateway confirm/cancel/callback under `/gateway`.

## API (`modules/Api/Routes/api.php`)

Prefix `/api`. See [API](../11-api.md) for endpoint tables (configs, services, auth, user, locations, booking, gateways, news, media).

Root `routes/api.php`: `GET /api/user` (Sanctum).

## Language

`language/set-lang/{locale}`, `set-admin-lang/{locale}`.

## Offers

Public `/offers`, `/offers/section/{slug}` plus admin CRUD routes when Offers provider is registered.

## Admin

Each module contributes `Routes/admin.php` under the admin prefix with `auth` + `dashboard` patterns. Use `php artisan route:list` for the live inventory.

## Related Documentation

- [API](../11-api.md)
- [Workflows](../14-workflows.md)
- [Controllers](./controllers.md)
