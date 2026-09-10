# Hotel Search (ETG / RateHawk)

Complete HA (hotel API) flow as implemented. Legacy `bravo_hotels` search is out of scope here (routes commented out).

Related: [ETG B2B API](./29-ETG-B2B-API.md) · [API](./10-API.md) · [External Integrations](./13-EXTERNAL-INTEGRATIONS.md) · [Database](./11-DATABASE.md) · [Payments](./23-PAYMENTS.md)

---

## Purpose

Let travelers find ETG hotels by **city region** or **hotel name**, load live EUR rates, prebook a `book_hash`, pay via PCB Bank, and finish the ETG order. Static hotel rows come from `hotels_data.py`.

---

## User flow

1. Open `/` — search form (`showHotels`).
2. Type in hotel or city field — `GET /hotel-suggestions` (ETG `search/multicomplete/`).
3. Selecting a hotel sets hidden `hid` / `etg_hotel_id` / `hotel_region_id` (see `form-search-ha.blade.php` JS `bindEtgAutocomplete`).
4. Selecting a city sets `region_id`, `region_type`, `region_country_code`.
5. Submit GET `/hotels/search` with `checkin`, `checkout`, `adults`, `rooms`, `children_count`, `children[]`.
6. First response: up to 10 hotels from MySQL (no prices yet).
7. If the user picked a **city**, `getHotelIdsForRegion` calls ETG `search/serp/region/` then filters local `hotels`. Browser AJAX `chunk` loads more; each chunk calls ETG `search/serp/hotels` for prices/breakfast.
8. Open `/hotel/{hotel_id}` — ETG `search/hp/` + `hotel/info/`.
9. POST `/hotel/prebook` with `book_hash` — ETG `hotel/prebook/`.
10. POST `/hotel/book` — ETG booking form + PCB `createOrder`.
11. User pays on PCB hosted page; return `/pcb-return`.
12. Finish ETG `hotel/order/booking/finish/`; confirmation / email.

Default `breakfast_included` is merged `true` if omitted (`searchHotels`).

---

## Frontend files

| File | Role |
|---|---|
| `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php` | Form + autocomplete (`route('hotel.suggestions')`) |
| `themes/BC/Hotel/Views/frontend/results-ha.blade.php` | Results; preserves `etg_hotel_id` |
| `themes/BC/Hotel/Views/frontend/info-ha.blade.php` | Detail |
| `themes/BC/Hotel/Views/frontend/prebook-result-ha.blade.php` | Prebook |
| `themes/BC/Hotel/Views/frontend/booking-confirmation-ha.blade.php` | Confirmation (RateHawk payment types commented; PCB only) |
| `themes/BC/Hotel/Views/frontend/payment-ha.blade.php` | Payment |
| `modules/Hotel/Blocks/FormSearchHotel.php` | Page-builder block (not the HA homepage) |

---

## Backend files

| File | Role |
|---|---|
| `modules/Hotel/Routes/web.php` | All HA routes |
| `modules/Hotel/Controllers/HotelHController.php` | Entire orchestration |
| `modules/Hotel/Models/HotelH.php` | `hotels` model (little used vs query builder) |
| `modules/Hotel/Models/HotelImage.php` | `hotel_images` |
| `modules/Hotel/Models/MjellmaBooking.php` | Local order row |
| `modules/Hotel/Events/MjellmaBookingCreatedEvent.php` | After create |
| `modules/Hotel/Listeners/MjellmaBookingCreatedListen.php` | Customer email |
| `app/Mail/BookingConfirmationEmail.php` | Mail class |
| `app/Services/PcbBankService.php` | Pay |

---

## Important IDs

| ID | Meaning |
|---|---|
| `hotel_id` | ETG string id; URL `/hotel/{id}`; column `hotels.hotel_id` |
| `hid` | ETG numeric hotel hid; `hotels.hid`; autocomplete `hid` |
| `region_id` | ETG city region; `search/serp/region/` |
| `etg_hotel_id` | Same as hotel id, from autocomplete `item.id` |
| `book_hash` | Rate identifier from SERP/HP; prebook `hash` |
| `partner_order_id` | Partner order for finish/cancel/info |
| `order_id` | PCB / local `mjellma_bookings.order_id` |

---

## Search implementation details

`haSearchParamsFromRequest` collects hotel_name, hid, etg_hotel_id, region fields, lat/lng/radius, dates, occupancy.

`applyHaSearchConstraints`:

- `hid` → `where hid`
- else `etg_hotel_id` → `where hotel_id`
- else `hotel_name` LIKE
- optional `star_rating` IN
- `region_id` → `getHotelIdsForRegion` then `whereIn hotel_id`, optional MySQL `FIELD()` order
- else bounding box from lat/lng/`radius` (default radius 4)

`getHotelIdsForRegion` POSTs `search/serp/region/` (checkin/checkout/guests/currency) and caches IDs 15 minutes (`etg_region_search_*`). This branch does **not** call `search/hotelsort/`. Geo search is a local lat/lng box on `hotels`, not ETG `search/serp/geo/`.

Chunk size: 10. Initial page: 10 of a 50-row prefetch. Search params cached 30 minutes under `search_params_{md5}`.

SERP body includes `residency: gb`, `language: en`, `currency: EUR`, `ids` and `hid` arrays.

---

## Suggestions

`getHotelSuggestions`:

- POST `search/multicomplete/` `{ query, language: en }`
- Hotels: unique by `hid`; fields hid, id, name, region_id
- Regions: **only `type === 'City'`**
- `type=hotels` drops regions; `type=cities|regions` drops hotels

---

## Hotel page

`hotelInfo`:

- Loads `hotels` by `hotel_id`; requires `hid`
- Images from `hotel_images`
- POST `search/hp/` with `id` = hotel_id
- POST `hotel/info/` with integer `hid`
- Room image matching: `findClosestImageMatch` / `normalizeRoomName`

---

## Prebook and book

Prebook validates `book_hash`, `room_name`; sends `price_increase_percent` default 20. Stores session `prebookData`, occupancy, `display_final_price`.

`bookRoom` uses session + `hotel/order/booking/form/`. Display price may differ by B2B vs B2C credentials (comment in controller).

---

## Database involvement

Read: `hotels`, `hotel_images`. Write: `mjellma_bookings`, payments/invoices via PCB helpers (`createPaymentRecordAndInvoice`). Cache driver from `.env` (file by default).

---

## Auth impact

Guest uses B2C ETG keys. Admin/vendor/B2B meta uses B2B keys — **different prices**. `?type=b2b|b2c` can override credential pair in `getManualApiCredentials()`.

---

## File-level: HotelHController

**File:** `modules/Hotel/Controllers/HotelHController.php`

**Purpose:** ETG hotel product + PCB for hotels.

**Main methods:** `showHotels`, `searchHotels`, `loadHotelChunk`, `getHotelSuggestions`, `hotelInfo`, `prebookRoom`, `bookRoom`, `processPayment`, `handlePcbReturn`, `finishBooking`, `completeBooking`, `cancelBooking`, `bookingHistory`, `testApiCredentials`, plus private ETG/PCB helpers.

**Depends on:** `env('API_*')`, Laravel Cache/Http/Auth/Mail, `MjellmaBooking`, `PcbBankService`, Carbon, DB.

**Used by:** `modules/Hotel/Routes/web.php` only (api.php empty).
