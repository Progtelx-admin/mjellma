# Car Rental

External fleet API + Booking Core car module + custom admin reservations.

Related: [External Integrations](./13-EXTERNAL-INTEGRATIONS.md) · [Payments](./23-PAYMENTS.md) · [API](./10-API.md)

---

## Purpose

Search cars from `CAR_API_BASE`, show details, checkout, optionally pay with PCB, list reservations in admin (`custom/CarRent`).

---

## User flow

1. `GET /car` — `CarController@index` loads `company-locations` (cached 1 hour) and location tree for the Blade search page.
2. `GET /car/search` — `search` queries `reservations` on the external API with form filters.
3. `GET /car/{slug}` — `detail` / `reservations/details/{carId}`.
4. `POST /car/checkout` — API `checkout`.
5. `GET /car/checkout/form` — customer form.
6. `POST /car/checkout/store` — API `store-checkout`.
7. `GET /car/pcb/return` — PCB return handler `handlePcbReturn`.
8. `GET /car/checkout/confirm` — confirmation.

Vendor CRUD for **legacy** `bravo_cars` remains under `user/car/...` (`ManageCarController`). That is separate from the API search.

---

## Frontend files

- `themes/BC/Car/Views/frontend/search.blade.php` (and layouts)
- `themes/BC/Car/Views/frontend/detail-api.blade.php`
- `themes/BC/Car/Views/frontend/layouts/search/api-car-item.blade.php` — image base URL env

---

## Backend files

| File | Role |
|---|---|
| `modules/Car/Routes/web.php` | Public and vendor routes |
| `modules/Car/Controllers/CarController.php` | API proxy + pages |
| `modules/Car/Controllers/ManageCarController.php` | Vendor inventory |
| `modules/Car/Models/Car.php` | Eloquent car (`isEnable()` gate on `callAction`) |
| `custom/CarRent/Admin/CarRentReservationController.php` | Admin index/show/PDF |
| `custom/CarRent/Routes/admin.php` | `/admin/module/carrent/reservations` |
| `app/Mail/CarBookingConfirmationEmail.php` | Confirmation mail (used from `CarController`) |

---

## API calls (`makeApiRequest`)

Relative to `rtrim(CAR_API_BASE)`:

| External path | Used when |
|---|---|
| `GET company-locations` | Locations + debug + cache |
| `GET reservations` | Search, load more, listing |
| `GET reservations/details/{id}` | Detail |
| `GET car-manufacturers` | Filters |
| `GET car-categories` | Filters |
| `GET car-transmissions` | Filters |
| `POST checkout` | Checkout step 1 |
| `POST store-checkout` | Store booking |

Headers: `Authorization: {CAR_API_TOKEN}`, `Accept`/`Content-Type` JSON. `CAR_API_REFERER` added as `referer`.

---

## State / env

- Cache key `car_company_locations` (3600s)
- `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER`, `CAR_IMAGE_BASE_URL`

If `Car::isEnable()` is false, `callAction` redirects to `/`.

---

## Database

Legacy `bravo_cars` for vendor listings/seeders. Live search does **not** require those rows.

## Admin CarRent reservations

`Custom\CarRent\Admin\CarRentReservationController` does **not** use `CAR_API_BASE`. It calls a separate agency API base hardcoded on the class (`https://dev.rentacar-orange.com/api/agency`) with a **hardcoded Bearer token in source** (treat as a secret; rotate and move to env). Methods: `index` (list `/reservations` with search/date filters), `show`, `downloadInvoicePdf` (DomPDF).

Do not copy the token from the PHP file into git comments or docs.

---

## Route ordering caveat

In `modules/Car/Routes/web.php`, `GET /car/{slug}` is registered **before** `GET /car/api/...`. Laravel may treat `api` as a slug. Verify with `php artisan route:list`. Not inventing a workaround here.

---

## File-level: CarController

**File:** `modules/Car/Controllers/CarController.php`

**Purpose:** Front-office car search/checkout against HTTP API.

**Important methods:** `index`, `search`, `detail`, `checkout`, `checkoutForm`, `storeCheckout`, `handlePcbReturn`, `getCompanyLocations`, `makeApiRequest`, filter/debug helpers.

**Depends on:** `CAR_API_*`, `Modules\Car\Models\Car`, `Location`, Cache, Http, Mail.

**Used by:** `modules/Car/Routes/web.php`.
