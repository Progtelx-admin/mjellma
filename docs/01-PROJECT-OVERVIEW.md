# Project Overview

## What this project is

Mjellma is a customized **Booking Core** (Laravel) travel-booking site. Composer `name` is `laravel/laravel`. `config/app.php` sets the display name default to `Booking Core` and `'version' => "3.4.2"`. The BC theme reports version `3.6.0` (`themes/BC/ThemeProvider.php`).

It supports:

- **Hotel search and booking via ETG / RateHawk / WorldOTA** (primary public homepage)
- **Legacy Booking Core hotels** stored in `bravo_hotels` (admin/vendor CRUD still exists; public catalog routes are commented out)
- **Car rental** via an external HTTP API (`CAR_API_BASE`)
- **Tour, Space, Event, Flight, Boat** as Booking Core bookable modules (database-backed, vendor-managed)
- **CMS**: pages, news, templates, popups, media, menus, languages
- **Users, roles, vendors, wallets, plans, reviews, coupons, reports**
- **Payments**: PCB Bank (custom), PayPal, Stripe, Payrexx, Paystack, offline, plus a TwoCheckout plugin
- **Offers** (custom module registered in `config/app.php`)
- **Pro** add-on namespace (`pro/`, `PRO_ENABLE`)
- Mobile-oriented **JSON API** under `/api` (`modules/Api`)

## What a user can do

1. Open `/` and search hotels by city (ETG region) or hotel name (ETG suggestion + local `hotels` table).
2. Open a hotel page `/hotel/{id}`, pick a rate (`book_hash`), prebook, pay with PCB Bank, finish the ETG order.
3. Search and book cars at `/car` against the Orange/rent-a-car style API.
4. Register/login (Fortify + custom register routes), manage profile, wishlist, wallet, vendor listings.
5. Admins use `/admin` for bookings, settings, users, CMS, and car-rent reservations.

## Technology summary

| Layer | Technology | Evidence |
|---|---|---|
| Backend | PHP 8.1+, Laravel 10 | `composer.json` |
| HTTP | Laravel routing, Blade views | `routes/`, `modules/*/Routes`, `themes/` |
| Auth | Laravel Fortify, Sanctum, Socialite, custom roles | `config/fortify.php`, `app/User.php`, `modules/Api/Controllers/AuthController.php` |
| Database | MySQL (default) | `.env.example`, `config/database.php` |
| Frontend | Blade, jQuery, Vue 2 (admin), Sass, Laravel Mix | `package.json`, `public_html/themes/*/webpack.mix.js` |
| HTTP clients | Guzzle / Laravel HTTP | ETG and car API calls |
| Payments | Omnipay, Stripe PHP, Paystack, custom PCB mTLS | `composer.json`, `config/payment.php`, `app/Services/PcbBankService.php` |
| Static hotel dump | Python scripts | `hotels_data.py`, `meal_data.py` |

## Product vs Booking Core stock

This fork changes the hotel product:

| Stock Booking Core | This repository |
|---|---|
| `GET /hotel` search on `bravo_hotels` | Commented out in `modules/Hotel/Routes/web.php` |
| Homepage `HomeController@index` | Commented out in `routes/web.php`; `/` is `HotelHController@showHotels` |
| Vendor “Manage Hotel” menu | Commented out in `Hotel\ModuleProvider::getUserMenu` |
| Admin “All Hotels” children | Commented out; admin menu points at `hotel.admin.booking.index` |

Car search still uses Booking Core routes but `CarController` talks to `CAR_API_BASE` instead of only Eloquent inventory.

## Namespaces / autoload

From `composer.json` `autoload.psr-4`:

- `App\` → `app/`
- `Modules\` → `modules/`
- `Themes\` → `themes/`
- `Custom\` → `custom/`
- `Plugins\` → `plugins/`
- `Pro\` → `pro/`
- Helpers: `app/Helpers/AppHelper.php`, `app/Helpers/ProHelper.php`

## Related documentation

- [Project Structure](./02-PROJECT-STRUCTURE.md)
- [Architecture](./03-ARCHITECTURE.md)
- [Features](./20-FEATURES.md)
- [Hotel Search](./21-HOTEL-SEARCH.md)
- [External Integrations](./13-EXTERNAL-INTEGRATIONS.md)
