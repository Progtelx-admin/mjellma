# Database

Default technology: **MySQL** (`DB_CONNECTION=mysql` in `.env.example`). Laravel also has sqlite/pgsql/sqlsrv configs unused by default.

Related: [Installation](./04-INSTALLATION.md) · [Python](./14-PYTHON.md)

---

## Connection

`config/database.php` reads `DB_HOST`, `DB_PORT` (3306), `DB_DATABASE` (example `booking_core`), `DB_USERNAME`, `DB_PASSWORD`.

No Redis is required for a default file cache/session install.

---

## Initialization from a fresh environment

1. Create empty MySQL database.
2. Configure `.env`.
3. Either complete `/install` or run:

```powershell
php artisan migrate
php artisan db:seed
```

4. Import ETG static hotels:

```powershell
python hotels_data.py
```

Optional meals:

```powershell
python meal_data.py
```

`is_installed()` is `file_exists(storage_path('installed'))`, not a database flag.

---

## Migration locations

| Location | Examples |
|---|---|
| `database/migrations/` | `users`, `core_settings`, pages, media, languages, reviews, `mjellma_bookings`, `invoices`, PCB column on bookings, wallets, plans |
| `themes/Base/Database/Migrations/` | tours, bookings, vendor, chatify-style tables, version upgrades |
| `modules/Hotel/Migrations/` | `bravo_hotels`, rooms, translations |
| `modules/Booking/Database/Migrations/` | passengers, rates, enquiry replies |
| `modules/Coupon/Migrations/` | coupons |
| `modules/Offers/Migrations/` | offers (if present) |

`Hotel\ModuleProvider::boot` calls `loadMigrationsFrom(__DIR__ . '/Migrations')`. Theme Base provider loads theme migrations.

---

## Two hotel datasets

### A. Booking Core hotels — `bravo_hotels`

Created by `modules/Hotel/Migrations/2019_09_20_072809_create_hotel_table.php`.

Model: `Modules\Hotel\Models\Hotel`. Soft deletes, translations `bravo_hotel_translations`, terms `bravo_hotel_term`, rooms `bravo_hotel_rooms`, room dates, etc.

Used by admin/vendor hotel CRUD and `HotelController` (public routes commented out).

### B. ETG dump — `hotels` + `hotel_images`

Created by `hotels_data.py` (`CREATE TABLE IF NOT EXISTS`), not by a Laravel migration in this repo.

Columns written by the script:

`hotels`: `hotel_id` (unique), `hid` (unique), `name`, `address`, `latitude`, `longitude`, `star_rating`, `metapolicy_struct`, `metapolicy_extra_info`

`hotel_images`: `hotel_id`, `image_url`, `category_slug` (FK to `hotels.hotel_id`)

Model: `Modules\Hotel\Models\HotelH` (`$table = 'hotels'`), `HotelImage` (`hotel_images`).

`HotelHController` queries these with `DB::table('hotels')`.

### C. Meal types — `meal_types`

Created by `meal_data.py`: `name` unique, `locale` JSON.

---

## Mjellma hotel bookings — `mjellma_bookings`

Migration: `database/migrations/2025_04_23_105258_create_mjellma_bookings_table.php`

| Column | Notes |
|---|---|
| `order_id` | unique |
| `partner_order_id` | ETG partner order |
| `booked_by` | enum admin/agent/user/guest |
| `admin_id` `agent_id` `user_id` | nullable |
| `user_email` `user_phone` | |
| `payment_type` `payment_amount` `currency_code` | |
| `pcb_status` `api_status` `api_error` | |
| `create_user` | |

Model fillable also includes `pcb_bank_response` and `payment_successful`. JSON column `pcb_bank_response` is added by `database/migrations/2025_09_26_135636_add_pcb_response_to_mjellma_bookings_table.php`. `MjellmaBooking` casts `pcb_bank_response` to array. If `payment_successful` is missing on a given database, that fillable field is not backed by the create + PCB-response migrations found in this repo.

---

## Core booking tables

Theme/module migrations create `bravo_bookings`, booking meta, payments (`bravo_booking_payments` or similar — see Booking models), passengers, enquiry.

`database/migrations/2025_09_26_135548_add_pcb_response_to_bravo_bookings_table.php` adds PCB fields to Booking Core bookings.

`invoices` table: `database/migrations/2025_09_26_000000_create_invoices_table.php` — used by `App\Models\Invoice` + `InvoiceService`.

---

## Settings — `core_settings`

Key/value used by `setting_item()`. Gateway flags such as `g_pcb_bank_enable` (`pcb:enable` command).

---

## Users and auth

- `users` (+ Fortify 2FA columns migration)
- `user_meta` (`user_type` b2b/b2c among other keys)
- Roles/permissions (User module models)
- `user_plan` / wallet tables

---

## Other bookable modules

Tours, spaces, cars (`bravo_cars` etc.), events, flights, boats — created by theme/module migrations and seeders (`HotelSeeder`, `CarSeeder`, …). Car **search** at runtime uses the external API, not only `bravo_cars`.

---

## Seeders

`database/seeders/DatabaseSeeder.php` calls, if no theme seeder:

`RolesAndPermissionsSeeder`, `Language`, `UsersTableSeeder`, `MediaFileSeeder`, `General`, `LocationSeeder`, `News`, `Tour`, `SpaceSeeder`, `HotelSeeder`, `CarSeeder`, `EventSeeder`, `SocialSeeder`, `DemoSeeder`, `FlightSeeder`, `BoatSeeder`.

Do not copy seeded passwords into documentation.

Factories: `database/factories/`.

---

## Backup / restore

No backup/restore scripts were found in the repository. Use MySQL dump/restore outside the app.

---

## Relationships (ETG path)

```
hotels.hotel_id 1──* hotel_images.hotel_id
hotels.hotel_id referenced by search and /hotel/{id}
mjellma_bookings.partner_order_id ↔ ETG order (not an SQL FK)
mjellma_bookings.user_id → users.id (nullable, no FK in the create migration)
```

`HotelImage::hotel()` belongsTo `Hotel` (bravo model) on `hotel_id` — that association does not match `bravo_hotels` primary keys. Runtime hotel HA code uses query builder, not this relation.
