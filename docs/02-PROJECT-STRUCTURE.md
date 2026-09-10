# Project Structure

Important directories only. Vendor, `node_modules`, compiled libs, and editor caches are omitted.

```
mjellma/
├── app/                    Laravel application code
├── bootstrap/              Laravel bootstrap (app.php)
├── config/                 PHP configuration
├── custom/                 Site-specific modules (CarRent)
├── database/               Framework migrations, factories, seeders
├── docs/                   Documentation (this tree lives in project-documentation/)
├── lang/                   Laravel lang files (`lang/en`: auth, validation, passwords, pagination)
├── modules/                Booking Core feature modules
├── plugins/                Optional plugins (PaymentTwoCheckout)
├── pro/                    Pro add-on modules (Ai, Booking, Support)
├── public_html/            HTTP document root (assets + index.php)
├── resources/              Lang, error views, CKEditor, GeoLite DB
├── routes/                 Root web/api/console routes
├── storage/                Logs, cache, installed flag, certs
├── tests/                  PHPUnit
├── themes/                 Base + BC theme overlays
├── artisan                 CLI entry
├── composer.json
├── package.json
├── hotels_data.py          ETG hotel dump importer
├── meal_data.py            ETG meal-type importer
├── gulpfile.js             Release zip helper
├── server.php              Built-in PHP server (expects public/index.php)
├── vite.config.js          Unused Vite stub
└── phpunit.xml
```

A top-level `public/` directory may exist as an empty/legacy Laravel public folder; **the front controller used in production is `public_html/index.php`**, which binds `path.public` to `public_html`.

A top-level `mjellma/` folder exists in the working tree. Its purpose is not determinable from application bootstrap (it is not referenced by `composer.json` autoload).

---

## `app/`

Laravel core application.

| Path | Purpose |
|---|---|
| `app/Http/Kernel.php` | Global and route middleware |
| `app/Http/Controllers/` | Home, installer, Auth login/register |
| `app/Http/Middleware/` | Installer redirect, dashboard, CSRF, locale, currency, password change |
| `app/Providers/` | App, Auth, Fortify, Event, Route, AdminRoute |
| `app/Console/Commands/` | PCB test/enable commands, language scan, user plan expiry |
| `app/Services/PcbBankService.php` | PCB Bank mTLS HTTP client |
| `app/Services/InvoiceService.php` | Invoice records from PCB payments |
| `app/User.php` | Canonical user model (roles, wallet, Sanctum, Fortify 2FA) |
| `app/Models/User.php` | Thin subclass of `App\User` used by `config/auth.php` |
| `app/Helpers/AppHelper.php` | Global helpers (`setting_item`, `is_installed`, …) |
| `app/Pro/` | Pro plan middleware/controllers/config |

Connects to: `modules/` (business logic), `config/`, `themes/` (views).

---

## `modules/`

Each module typically has `ModuleProvider.php`, `RouterServiceProvider.php`, `Routes/`, `Controllers/`, `Admin/`, `Models/`, `Views/`, sometimes `Migrations/`.

Activated by `Themes\Base\ThemeProvider::$modules` (plus Offers registered in `config/app.php`).

| Module | Role |
|---|---|
| `Api` | JSON API for apps (`/api`) |
| `Hotel` | ETG hotel search/booking + legacy `bravo_hotels` admin |
| `Car` | External car API + vendor car CRUD |
| `Tour` `Space` `Event` `Flight` `Boat` | Bookable services |
| `Booking` | Cart, checkout, gateways, `bravo_bookings` |
| `User` | Profile, register, roles, wallet, chat |
| `Vendor` | Payouts, vendor requests |
| `Core` | Settings, menus, modules admin, cookies, sitemap |
| `Dashboard` | `/admin` home + `admin_route_prefix` config |
| `Media` | Uploads / media browser |
| `Page` `News` `Template` `Popup` | CMS |
| `Location` | Destinations tree |
| `Language` | Locales and translations |
| `Email` `Sms` | Notification channels |
| `Coupon` `Review` `Report` `Contact` | Supporting features |
| `Offers` | Public offers pages + admin sections |
| `Theme` | Theme manager |
| `Layout` | Shared Blade layouts |

---

## `themes/`

| Path | Purpose |
|---|---|
| `themes/ThemeServiceProvider.php` | Prepends active theme view paths |
| `themes/Base/` | Core theme provider, migrations, default views |
| `themes/BC/` | Active theme (`BC_ACTIVE_THEME` default `BC`), including Hotel HA Blade views |

Frontend Hotel HA templates: `themes/BC/Hotel/Views/frontend/` (`form-search-ha.blade.php`, `results-ha.blade.php`, `info-ha.blade.php`, `prebook-result-ha.blade.php`, …).

---

## `custom/`

| Path | Purpose |
|---|---|
| `custom/ServiceProvider.php` | Auto-registers subfolders with `ModuleProvider` |
| `custom/CarRent/` | Admin list of car reservations + invoice PDF |
| `custom/Helpers/CustomHelper.php` | Empty file; `AppHelper.php` has the include commented out |

---

## `plugins/` and `pro/`

- `plugins/PaymentTwoCheckout/` — extra gateway, registered via `Plugins\ServiceProvider`.
- `pro/Ai`, `pro/Booking`, `pro/Support` — registered via `Pro\ServiceProvider`. Gated by `PRO_ENABLE`.

---

## `public_html/`

Document root.

| Path | Purpose |
|---|---|
| `public_html/index.php` | Front controller |
| `public_html/dist/` | Mix-compiled CSS/JS |
| `public_html/libs/` | Third-party JS/CSS (Bootstrap, CKEditor, Leaflet, …) |
| `public_html/themes/admin/` | Admin Mix project |
| `public_html/themes/bc/` | Frontend Mix project |
| `public_html/module/` | Per-module SCSS sources used by Mix |
| `public_html/uploads/` | Uploaded media (`filesystems.disks.uploads`) |

---

## `config/`

See [Configuration](./05-CONFIGURATION.md). Notable custom files: `bc.php`, `hotel.php`, `booking.php`, `payment.php`, `pcb_bank.php`, `invoice.php`, `modules.php`.

---

## `database/` vs module migrations

Laravel loads `database/migrations/` plus each module/theme `loadMigrationsFrom()`. Theme Base migrations live in `themes/Base/Database/Migrations/`. Hotel `bravo_*` tables come from `modules/Hotel/Migrations/`. ETG `hotels` / `hotel_images` are created by Python, not those PHP migrations.

---

## `routes/`

| File | Role |
|---|---|
| `web.php` | Intro, home, social login, installer, PCB test routes, fallback |
| `api.php` | Only Sanctum `/api/user` |
| `admin.php` | Entirely commented out |
| `language.php` | Locale-prefixed copy of selected web routes |
| `console.php` | `inspire` command |
| `channels.php` | Broadcast channels |

Module routes are registered by each module’s `RouterServiceProvider`.

---

## Root Python and utility files

| File | Purpose |
|---|---|
| `hotels_data.py` | Download ETG hotel dump, insert `hotels` / `hotel_images` |
| `meal_data.py` | Fetch ETG static meals, insert `meal_types` |
| `test.php` | One-off view folder copy script (BC → Base). Not an HTTP entry point. |
| `gulpfile.js` | Copy project to `../builds/booking-core` and zip |

---

## How pieces connect

```
Browser → public_html/index.php → bootstrap/app.php → HTTP Kernel
       → RouteServiceProvider + module RouterServiceProviders
       → Controllers (app/ or modules/)
       → Eloquent / DB + external HTTP APIs
       → Blade views (modules/*/Views then themes/BC overlay)
```

Details: [Architecture](./03-ARCHITECTURE.md).
