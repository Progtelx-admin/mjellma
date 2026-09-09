# Features

Major product areas as implemented in source. Deep dives: [Hotel Search](./21-HOTEL-SEARCH.md), [Car Rental](./22-CAR-RENTAL.md), [Payments](./23-PAYMENTS.md).

---

## Hotel search and booking (ETG)

**Purpose:** Sell RateHawk/ETG hotels on the homepage.

**User flow:** search form → suggestions → results (chunked live rates) → hotel page → prebook → PCB pay → ETG finish.

**Frontend:** `themes/BC/Hotel/Views/frontend/*-ha.blade.php`

**Backend:** `modules/Hotel/Controllers/HotelHController.php`, `modules/Hotel/Routes/web.php`

**Data:** `hotels`, `hotel_images`, `mjellma_bookings`

**IDs:** ETG string `hotel_id`, numeric `hid`, city `region_id`, rate `book_hash`, `partner_order_id`

**External:** WorldOTA B2B v3, PCB Bank

---

## Legacy hotels (Booking Core)

**Purpose:** Vendor-listed hotels in `bravo_hotels`.

**Status:** Public `HotelController@index/detail` routes are **commented out**. Admin/vendor room CRUD still routed.

**Files:** `modules/Hotel/Admin/*`, `modules/Hotel/Models/Hotel.php`, `modules/Hotel/Controllers/VendorController.php`

---

## Car rental

**Purpose:** Search/book cars from an external fleet API; pay with PCB; admin reservation list.

See [Car Rental](./22-CAR-RENTAL.md).

---

## Tours, spaces, events, flights, boats

**Purpose:** Classic Booking Core inventory (Eloquent + availability calendars).

**User flow:** `{prefix}/` search → `{prefix}/{slug}` detail → `POST /booking/addToCart` → checkout.

**Prefixes:** `config/tour.php` etc. (`tour`, `space`, `event`, `flight`, `boat`).

**Backend:** `modules/{Tour,Space,Event,Flight,Boat}/`

**Database:** `bravo_*` tables from theme/module migrations.

**Frontend:** `themes/BC/{Module}/Views/frontend/`

These are independent of ETG. Enable flags via settings / `isEnable()` on each model.

---

## Booking checkout (legacy services)

**Purpose:** Cart, enquiry, gateways for Bookable models.

**Files:** `modules/Booking/Controllers/BookingController.php`, `Gateways/*`, `Models/Booking.php`

**API:** `/api/booking/*`

**Statuses:** `config/booking.php` (`completed`, `processing`, `confirmed`, `cancelled`, `paid`, `unpaid`, `partial_payment`)

---

## Users, vendors, plans, wallet

**Purpose:** Register, profile, vendor upgrade, subscription plans, wallet, 2FA, wishlist.

**Files:** `modules/User/`, `app/User.php`

**Chat:** Chatify + `User/Controllers/ChatController.php`

---

## CMS: pages, news, templates, popups, media, menus

**Pages:** `modules/Page` — `PAGE_ROUTER_PREFIX`

**News:** `modules/News`

**Templates:** drag-drop blocks (`modules/Template`, Vue admin)

**Popups:** `modules/Popup`

**Media:** `modules/Media` browser

**Menus:** `modules/Core/Admin/MenuController`

**Homepage CMS:** historically `HomeController` + page builder; **current `/` is hotel HA**, `/home` still uses `HomeController@index`.

---

## Offers

**Purpose:** Public offers listing and section pages; admin sections.

**Routes:** `/offers`, `/offers/section/{slug}`, `/offers/ping`

**Files:** `modules/Offers/` (`ModuleProvider` registered in `config/app.php`)

**Permissions:** `offers_view`, `offers_manage`

---

## Locations and language

**Locations:** nested set (`kalnoy/nestedset`), `modules/Location`

**Language:** `modules/Language`; URL prefix when multi-lang enabled (`is_enable_language_route()`)

---

## Coupons, reviews, reports, contact

`modules/Coupon`, `Review`, `Report`, `Contact` — standard Booking Core.

---

## Admin dashboard and settings

**Dashboard:** `/admin` — recent bookings and charts (`DashboardController` uses `Modules\Booking\Models\Booking`)

**Settings:** `/admin/module/core/settings/index/{group}`

**Modules on/off:** `/admin/module/core/module`

**Logs:** `/{admin}/logs`

---

## Pro / AI / Support

`pro/Ai`, `pro/Booking`, `pro/Support` loaded when classes exist. `PRO_ENABLE` in `app/Pro/Config/config.php`. Support topic routes use `TOPIC_ROUTE_PREFIX`. AI title/content helpers use `AI_PROVIDER` / `AI_MAIN_API_KEY` / `AI_MAIN_MODEL` (`pro/Ai/Configs/config.php`).

---

## Email and SMS

Mail via Laravel mailers + settings. SMS drivers Nexmo/Twilio. Booking confirmation: `app/Mail/BookingConfirmationEmail.php` for Mjellma hotel bookings (`MjellmaBookingCreatedEvent`).

---

## Feature flags

Many modules check `Model::isEnable()` (settings). Hotel admin menu still shows “Hotel Booking” if hotel module enabled, pointing at ETG booking index rather than `bravo_hotels` list (children commented out in `Hotel\ModuleProvider::getAdminMenu`).
