# Backend

The backend is **Laravel 10** PHP. Entry points: `public_html/index.php` (HTTP) and `artisan` (CLI).

Related: [Architecture](./03-ARCHITECTURE.md) · [API](./10-API.md) · [Hotel Search](./21-HOTEL-SEARCH.md) · [Modules](./24-MODULES.md)

---

## Framework and bootstrap

1. `public_html/index.php` — PHP ≥ 8.0.2 check, copy `.env` on `/install`, optional `storage/bc.php` / `storage/pro.php`, autoload, `bootstrap/app.php`, bind public path, Kernel.
2. `bootstrap/app.php` — binds HTTP Kernel, Console Kernel, Exception Handler.
3. `config/app.php` registers application and module service providers.

---

## Request lifecycle

```
Request
→ RedirectToInstaller / CORS / maintenance / TrimStrings
→ web|api middleware group
→ Route (root or module RouterServiceProvider)
→ Controller
→ Eloquent / DB::table / Http:: / PcbBankService
→ Blade view or JSON
→ Exception Handler (`app/Exceptions/Handler.php`)
```

---

## Routing

Root: `App\Providers\RouteServiceProvider` loads `routes/web.php`, `routes/api.php` (`prefix api`), and locale-prefixed `routes/language.php`.

Each feature module’s `RouterServiceProvider` typically maps:

- `Routes/web.php` — middleware `web`
- `Routes/language.php` — same + `prefix(app()->getLocale())`
- `Routes/admin.php` — `web` + `dashboard`, prefix `{admin}/module/{name}` (Dashboard admin is `{admin}` only)
- `Routes/api.php` — prefix `api`

Hotel admin prefix: `{admin}/module/hotel`. CarRent: `{admin}/module/carrent`. Core settings: `{admin}/module/core`.

---

## Controllers (major)

| Class | Path | Role |
|---|---|---|
| `HotelHController` | `modules/Hotel/Controllers/HotelHController.php` | ETG search, book, PCB, invoices |
| `HotelController` | `modules/Hotel/Controllers/HotelController.php` | Legacy `bravo_hotels` search/detail/availability |
| `CarController` | `modules/Car/Controllers/CarController.php` | External car API |
| `BookingController` | `modules/Booking/Controllers/BookingController.php` | Cart/checkout |
| `HomeController` | `app/Http/Controllers/HomeController.php` | `/home`, DB check |
| `LoginController` | `app/Http/Controllers/Auth/LoginController.php` | Social login |
| `DashboardController` | `modules/Dashboard/Admin/DashboardController.php` | Admin home |
| `SettingsController` | `modules/Core/Admin/SettingsController.php` | Admin settings |
| `AuthController` | `modules/Api/Controllers/AuthController.php` | Sanctum API auth |
| `CarRentReservationController` | `custom/CarRent/Admin/CarRentReservationController.php` | Admin car reservations |

Hotel admin CRUD: `modules/Hotel/Admin/HotelController.php`, `RoomController.php`, `AttributeController.php`, `AvailabilityController.php`.

---

## Services

| Class | Path |
|---|---|
| `PcbBankService` | `app/Services/PcbBankService.php` |
| `InvoiceService` | `app/Services/InvoiceService.php` |

ETG has **no** dedicated client class; `HotelHController` calls `Http::withBasicAuth` directly.

Booking gateways: `modules/Booking/Gateways/*`.

---

## Models

See [Database](./11-DATABASE.md). Notable:

- `App\User` / `App\Models\User`
- `Modules\Hotel\Models\Hotel` (`bravo_hotels`)
- `Modules\Hotel\Models\HotelH` (`hotels`)
- `Modules\Hotel\Models\MjellmaBooking` (`mjellma_bookings`)
- `Modules\Booking\Models\Booking`, `Payment`
- `Modules\Core\Models\Settings`

---

## Middleware

Aliases in `app/Http/Kernel.php`:

| Alias | Class |
|---|---|
| `auth` | `App\Http\Middleware\Authenticate` |
| `guest` | `RedirectIfAuthenticated` |
| `dashboard` | `Dashboard` |
| `translation_manager` | `TranslationManager` |
| `system_log_view` | `CheckForLogPermission` |
| `set_language_for_api` | `SetLanguageForApi` |
| `pro_plan` | `App\Pro\Middlewares\ProPlan` |
| `verified` | `EnsureEmailIsVerified` |

Global: `TrustProxies`, `RedirectToInstaller`, CORS, maintenance, `TrimStrings`.

---

## Validation

Laravel `$request->validate()` in controllers (hotel search rules in `HotelHController@searchHotels`, prebook `book_hash` + `room_name`, API auth in `AuthController`). Form Request classes are not the dominant pattern in the hotel HA flow.

---

## Error handling

- `app/Exceptions/Handler.php` (framework handler)
- Controllers often `try/catch`, `Log::error`, `back()->withErrors` or JSON `{error: true}`
- Blade error views in `resources/views/errors/`
- ETG “final” finish errors handled inside `HotelHController` (comments around finish/status polling)

---

## Authentication

Fortify + session for web; Sanctum for `/api/auth/*`. Details: [Authentication](./12-AUTHENTICATION.md).

---

## Logging

`LOG_CHANNEL=daily` → `storage/logs/laravel-YYYY-MM-DD.log`. Hotel/car/PCB code uses `Log::info` / `Log::error` extensively.

---

## Example: hotel search lifecycle (filenames)

```
GET /hotels/search
→ modules/Hotel/Routes/web.php (name hotel.search)
→ Modules\Hotel\Controllers\HotelHController::searchHotels
→ DB::table('hotels') + Cache + optional ETG search/serp/region/
→ view Hotel::frontend.results-ha
AJAX chunk:
→ HotelHController::loadHotelChunk
→ Http POST {API_URL}search/serp/hotels
→ JSON html + hotels[]
```

Example: PCB order

```
HotelHController::bookRoom / processPayment
→ App\Services\PcbBankService::createOrder
→ mTLS POST {PCB_BANK_API_URL}/order
→ redirect to hosted payment
→ GET /pcb-return → handlePcbReturn
→ ETG hotel/order/booking/finish/
```
