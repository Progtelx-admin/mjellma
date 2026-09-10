# Modules and Important Files

Grouped file-level notes for major modules. Trivial Blade/SCSS files are omitted.

Related: [Project Structure](./02-PROJECT-STRUCTURE.md) · [Development Guide](./19-DEVELOPMENT-GUIDE.md)

---

## How modules boot

**File:** `modules/ServiceProvider.php`

**Purpose:** Load views from each `modules/*/Views`; register Theme `ModuleProvider`.

**Important methods:** `getActivatedModules()`, `getInstalledModules()`, `getManageableModules()`.

**Depends on:** `Modules\Theme\ThemeManager::currentProvider()`.

**Used by:** `config/app.php`.

**File:** `themes/Base/ThemeProvider.php`

**Purpose:** `$modules` map (core, api, booking, hotel, space, car, event, tour, flight, boat, …); `loadMigrationsFrom` theme DB; `RunUpdater` middleware.

**File:** `themes/BC/ThemeProvider.php`

**Purpose:** Active theme name Booking Core, version 3.6.0, seeder class `DatabaseSeeder`.

---

## Hotel

See [Hotel Search](./21-HOTEL-SEARCH.md). Additional files:

| File | Purpose |
|---|---|
| `modules/Hotel/ModuleProvider.php` | Migrations, permissions, admin menu, template blocks, MjellmaBooking listener |
| `modules/Hotel/RouterServiceProvider.php` | web/admin/language/api route maps (`Routes/api.php` is empty) |
| `modules/Hotel/SettingClass.php` | Admin hotel settings schema |
| `modules/Hotel/Admin/HotelController.php` | `bravo_hotels` CRUD |
| `modules/Hotel/Models/Hotel.php` | Bookable hotel, search, SEO |
| `modules/Hotel/Hook.php` | Hotel hooks |

---

## Booking

| File | Purpose |
|---|---|
| `modules/Booking/Controllers/BookingController.php` | addToCart, checkout, gateways |
| `modules/Booking/Models/Booking.php` | `bravo_bookings`, dashboard charts |
| `modules/Booking/Models/Payment.php` | Payments; `getGatewayObjAttribute` |
| `modules/Booking/Gateways/*.php` | Per-gateway implementations |
| `modules/Booking/Routes/web.php` | `/booking/*` |

---

## Car

See [Car Rental](./22-CAR-RENTAL.md). `modules/Car/ModuleProvider.php` registers routes/permissions like other bookables.

**File:** `custom/CarRent/ModuleProvider.php`

**Purpose:** Admin menu “Car Rent Reservations”, permission `carrent_view`.

**File:** `custom/ServiceProvider.php`

**Purpose:** Auto-register `custom/*` modules and merge their `Config/config.php`.

---

## Api

**File:** `modules/Api/RouterServiceProvider.php`

**Purpose:** Prefix `api`, middleware `api` + `set_language_for_api`.

**Controllers:** `AuthController`, `SearchController`, `BookingController`, `UserController`, `LocationController`, `NewsController`, `ReviewController`, `MediaController`.

---

## User

| File | Purpose |
|---|---|
| `app/User.php` | Canonical user |
| `app/Models/User.php` | Auth provider model |
| `modules/User/Routes/web.php` | profile, wallet, register, plans, chatify |
| `modules/User/Routes/admin.php` | users, roles, verification |
| `app/Http/Controllers/Auth/LoginController.php` | Socialite + redirect rules |
| `app/Providers/FortifyServiceProvider.php` | Fortify wiring |

---

## Core / Dashboard

| File | Purpose |
|---|---|
| `modules/Dashboard/Admin/DashboardController.php` | Admin home |
| `modules/Dashboard/Config/config.php` | `ADMIN_ROUTER_PREFIX` |
| `modules/Core/Admin/SettingsController.php` | Settings groups |
| `modules/Core/Admin/MenuController.php` | Menus |
| `modules/Core/Admin/ModuleController.php` | Enable modules |
| `modules/Core/Admin/UpdaterController.php` | License/update |
| `modules/Core/Models/Settings.php` | `setting_item` backend |
| `app/Helpers/AppHelper.php` | Global helpers |
| `app/Helpers/ReCaptchaEngine.php` | Google reCAPTCHA v2/v3 script/verify helpers |
| `app/Helpers/MapEngine.php` | OSM vs Google Maps script tags from settings |
| `app/Providers/AppServiceProvider.php` | HTTPS, locale, DB config overlay |
| `app/Http/Kernel.php` | Middleware |

---

## Offers

**File:** `modules/Offers/ModuleProvider.php` — admin menu, migrations, Blade components.

**File:** `modules/Offers/Controllers/OfferPublicController.php` — public index/section.

**File:** `modules/Offers/Hook.php` — `OFFERS_SETTING_CONFIG` constant.

---

## Pro / Plugins

**File:** `pro/ServiceProvider.php` — registers `support`, `booking`, `ai` if classes exist.

**File:** `app/Pro/Config/config.php` — `PRO_ENABLE`.

**File:** `plugins/ServiceProvider.php` — auto-loads `plugins/*/ModuleProvider`.

**File:** `plugins/PaymentTwoCheckout/Gateway/TwoCheckoutGateway.php` — 2Checkout.

---

## App HTTP / CLI

| File | Purpose |
|---|---|
| `public_html/index.php` | Front controller |
| `artisan` | CLI; also loads `storage/bc.php` if present |
| `app/Exceptions/Handler.php` | Exceptions |
| `app/Console/Kernel.php` | Schedule `user_plan:expired` |
| `routes/web.php` | Intro, social, installer, PCB tests, fallback `FallbackController` |

**File:** `modules/Core/Controllers/FallbackController.php` — `Route::fallback` in `routes/web.php`.

---

## Tour / Space / Event / Flight / Boat (pattern)

Each has `ModuleProvider`, `RouterServiceProvider`, `Controllers/{Name}Controller`, `Admin/`, `Models/`, `Routes/web.php` with `{prefix}/` search and `{prefix}/{slug}` detail, vendor manage under `user/{prefix}`. Flight adds airport search and seats. Copy that layout when adding a bookable type.

---

## Media, News, Page, Location, Language, Vendor, Coupon, Review, Report, Contact, Email, Sms, Template, Popup, Theme

Standard Booking Core modules under `modules/{Name}/`. Admin routes `{admin}/module/{name}`. Enable via Core module manager / settings / `isEnable()`.

---

## Python and Gulp

See [Python](./14-PYTHON.md) and [Scripts](./15-SCRIPTS-AND-COMMANDS.md).

**File:** `gulpfile.js` — packaging to `../builds/booking-core`.

**File:** `test.php` — one-off theme view copy (not runtime).
