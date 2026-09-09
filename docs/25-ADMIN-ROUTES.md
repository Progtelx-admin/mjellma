# Admin routes

Prefix `{admin}` is `config('admin.admin_route_prefix')`, default **`admin`** (`ADMIN_ROUTER_PREFIX`).

Almost all of these use middleware `web` + `dashboard` (`App\Http\Middleware\Dashboard`): must be logged in **and** have permission `dashboard_access`, otherwise redirect to `/` or login.

Exception: `POST /admin/module/media/getLists` is registered `withoutMiddleware('dashboard')` in `modules/Media/Routes/admin.php`.

`routes/admin.php` is **empty** (commented). Real admin routes come from each module `RouterServiceProvider`.

Related: [Authentication](./12-AUTHENTICATION.md) · [Route list](./26-ROUTE-LIST.md)

---

## Access rule

```
GET/POST /admin...
→ Auth::check()
→ Auth::user()->hasPermission('dashboard_access')
```

---

## Prefix map

| Prefix (default) | Source | Admin route file |
|---|---|---|
| `/admin` | `modules/Dashboard/RouterServiceProvider.php` | `modules/Dashboard/Routes/admin.php` |
| `/admin/module/core` | Core | `modules/Core/Routes/admin.php` |
| `/admin/module/user` | User | `modules/User/Routes/admin.php` |
| `/admin/module/hotel` | Hotel | `modules/Hotel/Routes/admin.php` |
| `/admin/module/car` | Car | `modules/Car/Routes/admin.php` |
| `/admin/module/carrent` | Custom CarRent | `custom/CarRent/Routes/admin.php` |
| `/admin/module/tour` | Tour | `modules/Tour/Routes/admin.php` |
| `/admin/module/space` | Space | `modules/Space/Routes/admin.php` |
| `/admin/module/event` | Event | `modules/Event/Routes/admin.php` |
| `/admin/module/flight` | Flight | `modules/Flight/Routes/admin.php` |
| `/admin/module/boat` | Boat | `modules/Boat/Routes/admin.php` |
| `/admin/module/booking` | Booking | `modules/Booking/Routes/admin.php` (**empty file**) |
| `/admin/module/report` | Report (`RouteServiceProvider.php`) | `modules/Report/Routes/admin.php` |
| `/admin/module/news` | News | `modules/News/Routes/admin.php` |
| `/admin/module/page` | Page | `modules/Page/Routes/admin.php` |
| `/admin/module/location` | Location | `modules/Location/Routes/admin.php` |
| `/admin/module/language` | Language | `modules/Language/Routes/admin.php` |
| `/admin/module/media` | Media | `modules/Media/Routes/admin.php` |
| `/admin/module/template` | Template | `modules/Template/Routes/admin.php` |
| `/admin/module/popup` | Popup | `modules/Popup/Routes/admin.php` |
| `/admin/module/coupon` | Coupon | `modules/Coupon/Routes/admin.php` |
| `/admin/module/contact` | Contact | `modules/Contact/Routes/admin.php` |
| `/admin/module/review` | Review | `modules/Review/Routes/admin.php` |
| `/admin/module/vendor` | Vendor | `modules/Vendor/Routes/admin.php` |
| `/admin/module/email` | Email | `modules/Email/Routes/admin.php` |
| `/admin/module/sms` | Sms | `modules/Sms/Routes/admin.php` |
| `/admin/module/offers` | Offers | `modules/Offers/Routes/admin.php` |
| `/admin/module/theme` | Theme — **hardcoded** `admin/module/theme` (does **not** use `ADMIN_ROUTER_PREFIX`) | `modules/Theme/Routes/admin.php` |

Pro Support admin routes exist in `pro/Support/Routes/admin.php` (`topic`, `ticket`, middleware `pro_plan`), but `pro/Support/RouterServiceProvider.php` has `map()` **commented out**, so those routes are **not registered** unless another provider loads them. Not determinable from that file that they are live.

TwoCheckout `plugins/PaymentTwoCheckout/Routes/admin.php` is **empty**.

---

## Extra admin URL (root web)

| Method | URI | Name | Handler |
|---|---|---|---|
| GET | `{admin}/logs` | `admin.logs` | Log viewer; middleware `auth`, `dashboard`, `system_log_view` (`routes/web.php`) |

---

## Dashboard

Base: `/admin`

| Method | Path | Name | Controller |
|---|---|---|---|
| GET | `/admin` | `admin.index` | `Modules\Dashboard\Admin\DashboardController@index` |

---

## Core (`/admin/module/core`)

| Method | Path | Name |
|---|---|---|
| GET | `/term/getForSelect2` | `core.admin.term.getForSelect2` |
| POST | `/markAsRead` | `core.admin.notification.markAsRead` |
| POST | `/markAllAsRead` | `core.admin.notification.markAllAsRead` |
| GET | `/notifications` | `core.admin.notification.loadNotify` |
| GET | `/updater` | `core.admin.updater.index` |
| POST | `/updater/store_license` | `core.admin.updater.store_license` |
| POST | `/updater/check_update` | `core.admin.updater.check_update` |
| POST | `/updater/do_update` | `core.admin.updater.do_update` |
| GET | `/settings/index/{group}` | `core.admin.settings.index` |
| POST | `/settings/store/{group}` | `core.admin.settings.store` |
| GET | `/tools` | `core.admin.tool.index` |
| GET | `/menu` | `core.admin.menu.index` |
| GET | `/menu/create` | `core.admin.menu.create` |
| GET | `/menu/edit/{id}` | `core.admin.menu.edit` |
| POST | `/menu/store` | `core.admin.menu.store` |
| POST | `/menu/getTypes` | `core.admin.menu.getTypes` |
| POST | `/menu/searchTypeItems` | `core.admin.menu.searchTypeItems` |
| POST | `/menu/bulkEdit` | `core.admin.menu.bulkEdit` |
| GET | `/module` | `core.admin.module.index` |
| POST | `/module/bulkEdit` | `core.admin.module.bulkEdit` |

Paths above are relative to `/admin/module/core`.

---

## User (`/admin/module/user`)

Users: `/`, `/create`, `/edit/{id}`, `POST /store/{id}`, `POST /bulkEdit`, `/password/{id}`, `POST /changepass/{id}`, `/verify-email/{id}`, `/getForSelect2`, `/export`, `/userUpgradeRequest`, `/upgrade/{id}`, `POST /userUpgradeRequestApproved`

Roles: `/role`, `/role/create`, `/role/edit/{id}`, `POST /role/store/{id}`, permission matrix, verify fields, Select2, bulkEdit, `POST /role/save_permissions`

Verification: `/verification`, `/verification/detail/{id}`, `POST store/{id}`, bulkEdit

Wallet: `/wallet/add-credit/{id}` GET/POST, `/wallet/report`, `POST /wallet/reportBulkEdit`

Subscribers: `/subscriber` CRUD + export

Plans: `/plan`, `/plan/edit/{id}`, `POST /plan/store/{id}`, bulkEdit, Select2

Plan request/report: `/plan-request`, `/plan-report` + bulkEdit

Controllers: `modules/User/Admin/*`

---

## Hotel Bravo CRUD (`/admin/module/hotel`)

From `modules/Hotel/Routes/admin.php` (legacy `bravo_hotels`, **not** ETG HA):

- Hotels: `/`, `/create`, `/edit/{id}`, `POST /store/{id}`, `POST /bulkEdit`, `/recovery`, `/getForSelect2`
- Attributes + terms under `/attribute/...`
- Rooms under `/room/...` including room attributes
- Availability: `/{hotel_id}/availability`, `loadDates`, `POST store`

ETG booking list used by the admin menu (`hotel.admin.booking.index`) is **`GET /booking`** on the **web** router (`HotelHController@index`), **not** under `/admin/module/hotel`. That URL has **no** `dashboard` middleware in `modules/Hotel/Routes/web.php`.

---

## Car (`/admin/module/car`)

Index/create/edit/store/bulkEdit/recovery/Select2; `/attribute` + terms; `/availability` + loadDates/store.

## CarRent (`/admin/module/carrent`)

| Method | Path | Name |
|---|---|---|
| GET | `/reservations` | `carrent.admin.reservations.index` |
| GET | `/reservations/{id}` | `carrent.admin.reservations.show` |
| GET | `/reservations/{id}/invoice/download` | `carrent.admin.reservations.invoice.download` |

---

## Tour / Space / Event / Boat (`/admin/module/{name}`)

Same pattern: list/create/edit/store/bulkEdit/recovery/Select2; `attribute` + terms; `availability`. Tour also has `/category` and `/booking`.

## Flight (`/admin/module/flight`)

Flights CRUD + recovery; `/{flight_id}/flight-seat`; `/airline`; `/airport` (+ signed `/airport/import-iata`); `/seat-type`; `/attribute` + terms.

---

## Report (`/admin/module/report`)

| Path | Name |
|---|---|
| `/booking` | `report.admin.booking` |
| `/booking/email_preview/{id}` | `report.admin.booking.email_preview` |
| `POST /booking/bulkEdit` | `report.admin.booking.bulkEdit` |
| `/enquiry` | `report.admin.enquiry.index` |
| `/enquiry/{enquiry}/reply` | `report.admin.enquiry.reply` |
| `POST .../reply/store` | `report.admin.enquiry.replyStore` |
| `/statistic` | `report.admin.statistic.index` |
| GET/POST `/statistic/reloadChart` | `report.admin.statistic.reloadChart` |

---

## CMS and others (relative to their prefix)

**News:** `/`, create/edit/store/bulkEdit; `/category`; `/tag`

**Page:** `/`, create, `/edit/{id}`, `/builder/{id}`, store, Select2, bulkEdit

**Location:** CRUD + `/category`

**Language:** GET/POST `/`, `edit/{id}`, bulkEdit; `/translation` detail/store/build/load JSON/strings/find

**Media:** `/`; `POST /getLists`; `POST /edit_image`

**Template:** `/edit/{id}`, Select2, getBlocks, store, import/export, `/live/{template}`, `POST /live/block-preview`

**Popup:** CRUD + recovery

**Coupon:** CRUD + `/get_services`

**Contact:** `/`, bulkEdit, Select2

**Review:** GET/POST `/`, bulkEdit

**Vendor:** `/payout`, `/plan` CRUD/Select2/bulkEdit

**Email:** `/testEmail`

**Sms:** `/testSms`

**Offers:** sections CRUD/delete; `/cards`; `/card/{section_id}/index|create`

**Theme:** `/` index; `POST active/{theme}`; `POST seeding/{theme}`

---

## Booking module admin file

`modules/Booking/Routes/admin.php` contains only `use Route;` — **no routes**. Booking admin UI for Bravo bookings is largely under **Report** `/admin/module/report/booking`.
