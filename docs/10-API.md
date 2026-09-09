# Application API

This document covers **this application’s HTTP endpoints** (Laravel routes). External ETG/PCB/car URLs are in [External Integrations](./13-EXTERNAL-INTEGRATIONS.md).

Two surfaces:

1. **Web routes** (session, CSRF) — used by Blade, including JSON from hotel suggestions/chunks.
2. **`/api` JSON API** — `modules/Api/Routes/api.php` (plus empty/other module `Routes/api.php` files).

Root `routes/api.php` only defines `GET /api/user` with `auth:sanctum`.

---

## Web: Hotel HA (no `/api` prefix)

Handled by `Modules\Hotel\Controllers\HotelHController` unless noted. File: `modules/Hotel/Routes/web.php`.

### GET `/`

Purpose: Hotel search form (site homepage).

Handled by: `HotelHController@showHotels`

Auth: No

Response: Blade `Hotel::frontend.form-search-ha`

---

### GET `/hotels/search`

Purpose: Search hotels by dates/guests and optional `hid` / `etg_hotel_id` / `region_id` / lat-lng.

Handled by: `HotelHController@searchHotels`

Query (validated): `checkin` (required date), `checkout` (required, after checkin), `rooms`, `adults`, `children_count`, `children[]`, optional `hotel_name`, `hid`, `etg_hotel_id`, `hotel_region_id`, `location`, `region_id`, `region_type`, `region_country_code`, `latitude`, `longitude`, `radius`, `min_price`, `max_price`, `star_rating[]`, `breakfast_included`, `chunk`.

Auth: No

Response: Blade `results-ha` **or** JSON when AJAX + `chunk` (`html`, `hasMore`, `totalCount`, `loadedCount`, `hotels`). Error JSON `{error: 'Search expired'}` if cache missing.

Service: none (inline HTTP/DB). ETG `search/serp/region/` (city), `search/serp/hotels` (prices). See [ETG B2B API](./29-ETG-B2B-API.md).

---

### GET `/hotel-suggestions`

Purpose: Autocomplete hotels and City regions.

Handled by: `HotelHController@getHotelSuggestions`

Query: `query` (string), `type` (`hotels` | `cities` | `regions` | empty)

Auth: No

Response JSON (from controller):

```json
{ "hotels": [ { "hid": 0, "id": "...", "name": "...", "region_id": 0 } ], "regions": [ { "id": 0, "name": "...", "type": "City", "country_code": "XX" } ], "error": false }
```

On failure: `hotels`/`regions` empty, `error: true`. Empty query: empty lists, `error: false`.

ETG: `POST search/multicomplete/`.

---

### GET `/hotel/{id}`

Purpose: Hotel page + live rates. `{id}` is ETG `hotel_id` (not `bravo_hotels` id). Constraint `[^\\.]+`.

Handled by: `HotelHController@hotelInfo`

Query: `checkin`, `checkout`, `residency` (default `gb`), `language` (default `en`), `currency` (default `EUR`), `adults`, `children[]`

Auth: No

Response: Blade `info-ha` or redirect with errors if hotel/HID missing.

ETG: `search/hp/`, `hotel/info/`.

---

### POST `/hotel/prebook`

Purpose: Hold rate (`book_hash`).

Handled by: `HotelHController@prebookRoom`

Body: `book_hash`, `room_name`, optional `children`, `price_increase_percent` (default 20), `display_final_price`, `display_currency`, `checkin`, `checkout`, `adults`

Auth: No (session)

Response: redirect `hotel.prebook.result` or back with errors.

ETG: `hotel/prebook/`.

---

### GET `/hotel/prebook/result`

Purpose: Show prebook session data.

Handled by: `HotelHController@prebookResult`

---

### POST `/hotel/book`

Purpose: Create ETG booking form + start payment.

Handled by: `HotelHController@bookRoom`

ETG: `hotel/order/booking/form/`.

---

### GET `/hotel/booking/confirmation/{book_hash}`

Handled by: `bookingConfirmation`

---

### POST `/hotel/payment`

Handled by: `processPayment` — PCB + ETG finish.

---

### GET `/pcb-return`

Handled by: `handlePcbReturn` — PCB hosted page return.

---

### GET `/hotel/payment/confirm`

Handled by: `confirmAfterPcb`

---

### POST `/hotel/booking/handle`

Handled by: `handleBookingSubmission`

---

### POST `/hotel/booking/complete` · POST `/hotel/booking/finish`

Handled by: `completeBooking`, `finishBooking` — ETG `hotel/order/booking/finish/`.

---

### GET `/hotel/payment/success`

Closure → `Hotel::frontend.payment-success`

---

### GET `/hotel/booking/failed`

Closure → `Hotel::frontend.booking-failed`

---

### GET `/booking` · GET `/booking-history` · GET `/booking/{orderId}/invoice` · GET `/bookings/{orderId}/details` · POST `/bookings/{partnerOrderId}/cancel`

Admin/history style hotel order pages on `HotelHController` (`index`, `bookingHistory`, `showBookingInvoice`, `showBookingDetails`, `cancelBooking`). ETG `hotel/order/info/`, `hotel/order/cancel/`. Middleware is **not** applied on these routes in `web.php` (no `dashboard` group). Treat as sensitive.

---

### GET `/hotel/test-api-credentials`

Handled by: `testApiCredentials`

Response JSON: user type, masked username, whether `API_*` env vars are set. Comment in routes: remove in production.

---

### POST `{hotel_prefix}/checkAvailability`

Handled by: `HotelController@checkAvailability` (legacy rooms).

---

## Web: Booking Core checkout

`modules/Booking/Routes/web.php`, prefix `booking` (configurable):

| Method | Path | Controller |
|---|---|---|
| POST | `/booking/addToCart` | `BookingController@addToCart` |
| POST | `/booking/doCheckout` | `doCheckout` |
| GET | `/booking/confirm/{gateway}` | `confirmPayment` |
| GET | `/booking/cancel/{gateway}` | `cancelPayment` |
| GET | `/booking/{code}` | `detail` |
| GET | `/booking/{code}/checkout` | `checkout` |
| GET | `/booking/{code}/check-status` | `checkStatusCheckout` |
| POST | `/booking/addEnquiry` | `addEnquiry` |
| POST | `/booking/setPaidAmount` | `setPaidAmount` (`auth`) |
| GET | `/booking/modal/{booking}` | `modal` |
| GET | `/booking/export-ical/{type}/{id}` | `exportIcal` |

Gateways: `/gateway/confirm/{gateway}`, `/cancel/{gateway}`, `/info`, `/gateway_callback/{gateway}`.

Exact JSON bodies are built in `Modules\Booking\Controllers\BookingController` (not duplicated here). Responses are Blade or redirects unless the client sends AJAX as that controller implements.

---

## Web: Cars

Prefix `car` (`modules/Car/Routes/web.php`):

| Method | Path | Method on `CarController` |
|---|---|---|
| GET | `/car` | `index` |
| GET | `/car/search` | `search` |
| POST | `/car/checkout` | `checkout` |
| GET | `/car/checkout/form` | `checkoutForm` |
| POST | `/car/checkout/store` | `storeCheckout` |
| GET | `/car/checkout/confirm` | `checkoutConfirm` |
| GET | `/car/pcb/return` | `handlePcbReturn` |
| GET | `/car/{slug}` | `detail` |
| GET | `/car/api/locations` | `apiGetLocations` |
| GET | `/car/api/reservations` | `apiGetReservations` |
| GET | `/car/api/refresh-locations` | `refreshLocations` |
| GET | `/car/api/debug` | `debugApi` |
| GET | `/car/api/manufacturers` | `apiGetManufacturers` |
| GET | `/car/api/categories` | `apiGetCategories` |
| GET | `/car/api/transmissions` | `apiGetTransmissions` |
| GET | `/car/api/load-more` | `loadMore` |

Note: `/car/{slug}` is registered **after** `/car/api/...` in the file except `api` routes are listed after `{slug}` in the file — **`{slug}` is declared before `/api/*`**, which can make `/car/api/locations` match `detail` with slug `api`. Confirm in a running `php artisan route:list` if car API URLs 404. This documentation does not invent a fix.

---

## Web: Auth, user, CMS, offers

| Method | Path | Handler |
|---|---|---|
| GET | `/login` | Fortify |
| GET/POST | `/register` | `Modules\User\Controllers\Auth\RegisterController` |
| GET | `social-login/{provider}` | `LoginController@socialLogin` |
| GET | `social-callback/{provider}` | `socialCallBack` |
| GET | `/user/profile` | auth+verified |
| GET | `/offers` | `OfferPublicController@index` |
| GET | `/offers/section/{slug}` | `section` |
| GET | `/offers/ping` | string `offers-web-ok` |
| GET | `/pcb-test` | JSON PCB config flags |
| GET | `/intro` | `LandingpageController@index` |
| GET | `/home` | `HomeController@index` |

Admin JSON-ish: `{admin}/logs` log viewer.

---

## JSON API `/api` (`modules/Api`)

Middleware: `api` + `set_language_for_api`. Controllers under `modules/Api/Controllers/`.

Rate limit: `api` limiter 60/min (`RouteServiceProvider`).

### GET `/api/configs`

`BookingController@getConfigs` — site configs for the app.

### GET `/api/services`

`SearchController@searchServices`

### GET `/api/{type}/search`

`SearchController@search` — `{type}` is a bookable type (hotel, tour, …) as implemented in that controller.

### GET `/api/{type}/detail/{id}`

`SearchController@detail`

### GET `/api/{type}/availability/{id}`

`SearchController@checkAvailability`

### GET `/api/boat/availability-booking/{id}`

`checkBoatAvailability`

### GET `/api/{type}/filters` · GET `/api/{type}/form-search`

filters / search form payload

### POST `/api/{type}/write-review/{id}`

`ReviewController@writeReview` (middleware `api`)

### GET `/api/home-page`

`BookingController@getHomeLayout`

### POST `/api/auth/login`

`AuthController@login`

Body: `email`, `password`, `device_name` (all required).

Success (controller): `access_token`, `user` (`UserResource`), `status: 1`.

Errors: validation errors; `invalid_credentials`.

Auth: none. Throttle `login`.

### POST `/api/auth/register`

`first_name`, `last_name`, `email`, `password`, `term`. Disabled if `is_enable_registration()` is false.

### POST `/api/auth/logout` · `refresh` · GET/POST `/api/auth/me` · POST `/api/auth/change-password`

Sanctum (`auth:sanctum` except login/register).

### GET `/api/user/booking-history` · wishlist routes · POST `/api/user/permanently_delete`

`UserController` (middleware `api`)

### GET `/api/locations` · GET `/api/location/{id}`

`LocationController`

### POST `/api/booking/addToCart` · `addEnquiry` · `doCheckout` · GET confirm/cancel/`{code}` / thankyou / checkout / check-status

`BookingController` (prefix from `BOOKING_ROUTER_PREFIX`)

### GET `/api/gateways`

`getGatewaysForApi`

### GET `/api/news` · `news/category` · `news/{id}`

`NewsController`

### POST `/api/media/store`

`MediaController@store` — `auth:sanctum`

### GET `/api/user`

Root `routes/api.php` — current Sanctum user.

---

Do not assume undocumented response shapes. Read the named controller method for exact JSON keys.

Mobile API uses Booking Core **bravo** services; it is not the ETG HA flow unless a type maps to `Hotel` Eloquent search (`Modules\Hotel\Models\Hotel`).

Named public/hotel/car routes compiled from source: [Route list](./26-ROUTE-LIST.md). Admin: [Admin routes](./25-ADMIN-ROUTES.md).
