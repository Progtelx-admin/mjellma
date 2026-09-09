# ETG B2B API — what this project uses

Official documentation: [Emerging Travel Group B2B API](https://docs.emergingtravel.com/docs/b2b-api/).

This file maps **every B2B API group on that site** to **this repository**: used, unused, and where to find the call in source.

Related: [Hotel Search](./21-HOTEL-SEARCH.md) · [Python](./14-PYTHON.md) · [PCB payment flow](./27-PCB-PAYMENT-FLOW.md) · [B2B / B2C credentials](./28-B2B-B2C-CREDENTIALS.md) · [External Integrations](./13-EXTERNAL-INTEGRATIONS.md)

---

## Official docs vs this app

| | Official | This repo |
|---|---|---|
| Docs home | https://docs.emergingtravel.com/docs/b2b-api/ | — |
| Integration guide | https://docs.emergingtravel.com/docs/integration-guide/ | Hotel HA in `HotelHController` |
| Production host in ETG docs | `https://api.ratehawk.com/api/b2b/v3/` | Default `https://api.worldota.net/api/b2b/v3/` (`API_URL`) |
| Sandbox host in ETG docs | `https://api-sandbox.ratehawk.com/api/b2b/v3/` | Not hardcoded; only if you set `API_URL` |
| Auth | HTTP Basic `<KEY_ID>:<API_KEY>` | Laravel `Http::withBasicAuth`; Python `auth=(USERNAME, PASSWORD)` |
| IPv4 | ETG may reject IPv6 (`not_allowed_host`) | `HotelHController` forces IPv4 (`force_ip_resolve` / `CURLOPT_IPRESOLVE`) |

WorldOTA and RateHawk are the same ETG B2B v3 surface. Paths below are **relative to `API_URL`**.

**Single PHP client:** `modules/Hotel/Controllers/HotelHController.php`  
**Python:** `hotels_data.py`, `meal_data.py`  
**Laravel routes:** `modules/Hotel/Routes/web.php`

---

## How to find everything in this repo

From the repository root:

```powershell
# Every ETG path string
Select-String -Path modules\Hotel\Controllers\HotelHController.php,hotels_data.py,meal_data.py -Pattern "search/|hotel/info|hotel/prebook|hotel/order|hotel/static|b2b/v3"

# Autocomplete, search, hotel page, book, cancel
Select-String -Path modules\Hotel\Controllers\HotelHController.php -Pattern "function (getHotelSuggestions|searchHotels|hotelInfo|prebookRoom|bookRoom|finishBooking|cancelBooking|pollFinishStatus)"
```

| What you want | Where |
|---|---|
| All hotel URLs the user hits | `modules/Hotel/Routes/web.php` |
| Every ETG HTTP call | `HotelHController` (`->post($this->getApiUrl() . '...')`) |
| Static hotel dump | `hotels_data.py` |
| Meal catalog dump | `meal_data.py` |
| B2B vs B2C keys | `getUserType()`, `getApiUsername()`, `getApiPassword()` in `HotelHController` |
| Finish as `deposit` after PCB | `handlePcbReturn` sets `payment_type.type = 'deposit'` |

---

## Implemented flow (Mjellma) vs ETG recommended flow

ETG [integration guide](https://docs.emergingtravel.com/docs/integration-guide/) recommends: SERP (region / hotel IDs / geo) → **Retrieve hotelpage** → **Prebook (hotelpage)** → **Create booking (form)** → **Start booking (finish)** → **Check status** (or webhook).

What this app does:

```
User types city/hotel
  → POST search/multicomplete/          (Suggest)
User searches a city
  → POST search/serp/region/            (Search by region — hotel IDs only)
  → MySQL hotels + hotel_images         (from hotels_data.py dump)
  → POST search/serp/hotels             (Search by hotel IDs — live prices, chunks)
User opens a hotel
  → POST search/hp/                     (Retrieve hotelpage — bookable rates)
  → POST hotel/info/                    (Retrieve hotel content — photos/metapolicy)
User picks a rate
  → POST hotel/prebook/                 (Prebook from hotelpage, not serp/prebook)
  → POST hotel/order/booking/form/      (Create booking process)
User pays on PCB Bank (not ETG card / not payment type now)
  → POST hotel/order/booking/finish/    (Start booking, type deposit)
  → POST hotel/order/booking/finish/status/  (poll, no webhook)
Admin can
  → POST hotel/order/info/              (Retrieve bookings)
  → POST hotel/order/cancel/            (Cancel)
```

**Not the same as ETG “search by geo”:** lat/lng/radius filters the **local** `hotels` table (bounding box). The app does **not** call `search/serp/geo/`.

**Not Sort hotels:** `search/hotelsort/` is **not** called on this branch. Region ranking is the order of IDs returned by `search/serp/region/`, then MySQL `FIELD(hotel_id, ...)`.

---

## Official B2B groups — used or not

From the [B2B API index](https://docs.emergingtravel.com/docs/b2b-api/):

| Official group | Used here? |
|---|---|
| [Endpoints](https://docs.emergingtravel.com/docs/b2b-api/endpoints/) | **No** |
| [Static content](https://docs.emergingtravel.com/docs/b2b-api/static-content/) | **Partially** (dump + static meals + hotel content) |
| [Hotel search](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/) | **Mostly** (see table below) |
| [Booking](https://docs.emergingtravel.com/docs/b2b-api/booking/) | **Mostly** (form + finish + status; card token / webhook differ) |
| [Post booking](https://docs.emergingtravel.com/docs/b2b-api/post-booking/) | **Yes** (info + cancel) |
| [Contracts](https://docs.emergingtravel.com/docs/b2b-api/contracts/) | **No** |
| [Documents](https://docs.emergingtravel.com/docs/b2b-api/documents/) | **No** |
| [Order groups](https://docs.emergingtravel.com/docs/b2b-api/order-groups/) | **No** |
| [Profiles](https://docs.emergingtravel.com/docs/b2b-api/profiles/) | **No** |

---

## Hotel search — call by call

Official list: https://docs.emergingtravel.com/docs/b2b-api/hotel-search/

| Official doc | Official path | In this project? | Source | App route / trigger |
|---|---|---|---|---|
| [Suggest hotel and region](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/suggest-hotel-and-region/) | `POST search/multicomplete/` | **Yes** | `getHotelSuggestions` | `GET /hotel-suggestions` |
| [Search by region](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/search-by-region/) | `POST search/serp/region/` | **Yes** | `getHotelIdsForRegion` | City search (`region_id` on `GET /hotels/search`) |
| [Search by hotel IDs](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/search-by-hotel-ids/) | `POST search/serp/hotels/` | **Yes** | `loadHotelChunk`, `fetchApiData` | AJAX `chunk` on `/hotels/search` |
| [Search by geo coordinates](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/search-by-geo-coordinates/) | `POST search/serp/geo/` | **No** | Local lat/lng box on `hotels` | Only if request has `latitude`/`longitude` |
| [Sort hotels](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/sort-hotels/) | `POST search/hotelsort/` | **No** (this branch) | — | — |
| [Retrieve hotelpage](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/retrieve-hotelpage/) | `POST search/hp/` | **Yes** | `hotelInfo` | `GET /hotel/{id}` |
| [Prebook rate from hotelpage](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/prebook-rate-from-hotelpage-step/) | `POST hotel/prebook/` | **Yes** | `prebookRoom` | `POST /hotel/prebook` |
| [Prebook rate from search step](https://docs.emergingtravel.com/docs/b2b-api/hotel-search/prebook-rate-from-search-step/) | `POST serp/prebook/` | **No** | Rates are chosen on hotel page | — |
| Retrieve rate info | (see hotel-search index) | **No** | — | — |

### Suggest — extra behaviour

Request body: `{ query, language: "en" }`.

Response hotels/regions are filtered in PHP:

- hotels unique by `hid`
- regions kept only when `type === 'City'`
- query `type=hotels` drops regions; `type=cities` or `regions` drops hotels

### Search by region — extra behaviour

Used **only to collect hotel string IDs**, then join MySQL. Cache key `etg_region_search_{md5(...)}` for **15 minutes**. Occupancy and dates are sent (required by this call). ETG docs say SERP rates should **not** be shown as the bookable offer; this app follows that: prices for the list come from `search/serp/hotels`, bookable rates from `search/hp/`.

### Search by hotel IDs — extra behaviour

Chunked (10 hotels). Body includes `residency: gb`, `language: en`, `currency: EUR`, hotel `ids`. List prices are for display/filter (breakfast), not the final bookable `book_hash` (that comes from hotelpage).

---

## Static content — call by call

Official list: https://docs.emergingtravel.com/docs/b2b-api/static-content/

| Official doc | Official path | In this project? | Source |
|---|---|---|---|
| [Retrieve hotel dump](https://docs.emergingtravel.com/docs/b2b-api/static-content/retrieve-hotel-dump/) | `POST hotel/info/dump/` | **Yes** | `hotels_data.py` (`language: en`, zstd NDJSON) |
| [Retrieve hotel content](https://docs.emergingtravel.com/docs/b2b-api/static-content/retrieve-hotel-content/) | `POST hotel/info/` | **Yes** | `hotelInfo` (`hid` + `language`) |
| [Retrieve hotel static data](https://docs.emergingtravel.com/docs/b2b-api/static-content/retrieve-hotel-static-data/) | `GET hotel/static/` | **Partial** | `meal_data.py` stores only `data.meals` → `meal_types`. Laravel UI meals come from live rates (`meal` / `meal_data.value`), not this table |
| Retrieve hotel custom dump | | **No** | |
| Retrieve hotel incremental dump | | **No** | Full dump only; re-run `hotels_data.py` |
| Retrieve hotel reviews’ dump / incremental | | **No** | |
| Retrieve regions’ dump | | **No** | Regions from multicomplete at search time |
| Retrieve point of interest dump | | **No** | |

Dump fields written to MySQL: `hotel_id`, `hid`, `name`, `address`, `latitude`, `longitude`, `star_rating`, `metapolicy_struct`, `metapolicy_extra_info`, images (`images` + `images_ext`). Other dump fields are ignored.

---

## Booking — call by call

Official list: https://docs.emergingtravel.com/docs/b2b-api/booking/

| Official doc | Official path | In this project? | Source | App route |
|---|---|---|---|---|
| [Create booking process](https://docs.emergingtravel.com/docs/b2b-api/booking/create-booking-process/) | `POST hotel/order/booking/form/` | **Yes** | `bookRoom`, `sendBookingForm` | `POST /hotel/book` |
| [Start booking process](https://docs.emergingtravel.com/docs/b2b-api/booking/start-booking-process/) | `POST hotel/order/booking/finish/` | **Yes** | `processPayment`, `finishBooking`, `completeBooking` | After PCB: `GET /pcb-return` → `finishBooking` |
| [Check booking process](https://docs.emergingtravel.com/docs/b2b-api/booking/check-booking-process/) | `POST hotel/order/booking/finish/status/` | **Yes** | `pollFinishStatus` (5s, up to remaining booking window / 300s) | Internal after finish |
| [Create credit card token](https://docs.emergingtravel.com/docs/b2b-api/booking/create-credit-card-token/) | Official: `https://api.payota.net/api/public/v1/manage/init_partners` | **Not that URL** | `getCardTokenFromPcb` posts `hotel/order/booking/credit-card/` with `order_id`, `init_uuid`, `pay_uuid` | Not the live PCB hotel path |
| [Receive booking status webhook](https://docs.emergingtravel.com/docs/b2b-api/booking/receive-booking-status-webhook/) | Partner webhook | **No** | Status is polled | — |

### Payment type (important)

ETG form returns `payment_types`: `now` (pay ETG with card), `hotel` (pay at hotel), `deposit` (partner charges the guest; ETG takes from contract deposit).

Hotel confirmation in this app is **PCB Bank**, then finish with **`deposit`**. Guests do not pay RateHawk with `now` / 3-D Secure. See [PCB payment flow](./27-PCB-PAYMENT-FLOW.md).

Form body: `partner_order_id`, `book_hash`, `language: en`, `user_ip`.  
Prebook body: `hash` (from hotelpage `book_hash`), `price_increase_percent` default **20**.

---

## Post booking — call by call

Official list: https://docs.emergingtravel.com/docs/b2b-api/post-booking/

| Official doc | Official path | In this project? | Source | App route |
|---|---|---|---|---|
| [Retrieve bookings](https://docs.emergingtravel.com/docs/b2b-api/post-booking/retrieve-bookings/) | `POST hotel/order/info/` | **Yes** | several methods (`showBookingDetails`, `verifyBookingStatus`, …) | e.g. `GET /bookings/{orderId}/details` |
| [Cancel booking](https://docs.emergingtravel.com/docs/b2b-api/post-booking/cancel-booking/) | `POST hotel/order/cancel/` | **Yes** | `cancelBooking` | `POST /bookings/{partnerOrderId}/cancel` |

---

## IDs you will see in ETG docs and in this code

| Field | Role here |
|---|---|
| `query` | Autocomplete |
| `region_id` | City from suggestions; SERP region |
| `id` / `hotel_id` | String hotel id; URL `/hotel/{id}`; dump + SERP |
| `hid` | Numeric hotel id; dump + `hotel/info/` |
| `book_hash` / `hash` | Rate from `search/hp/`; sent to `hotel/prebook/` |
| `partner_order_id` | Your UUID for form / finish / status / info / cancel |
| `order_id` | ETG order id from form; also PCB / `mjellma_bookings` |
| `search_hash` | ETG SERP rate hash (docs). This app books from **hotelpage** `book_hash`, not `serp/prebook` |

---

## What you did **not** implement (still in official docs)

Do not expect these in `HotelHController` / Python:

- Retrieve endpoints
- Sort hotels (`search/hotelsort/`)
- Search by geo (`search/serp/geo/`)
- Prebook from SERP (`serp/prebook/`)
- Incremental / custom / reviews / regions / POI dumps
- Payota `init_partners` card token (official `now` flow)
- Booking status **webhook**
- Contracts, documents, order groups, profiles
- ETG payment type `now` + 3-D Secure as the hotel checkout (PCB + `deposit` instead)

---

## Quick “where is this official page in our code?”

| Official page title | Grep / file |
|---|---|
| Suggest hotel and region | `search/multicomplete/` |
| Search by region | `search/serp/region/` |
| Search by hotel IDs | `search/serp/hotels` |
| Retrieve hotelpage | `search/hp/` |
| Prebook hotelpage | `hotel/prebook/` |
| Retrieve hotel content | `hotel/info/` (PHP) |
| Retrieve hotel dump | `hotels_data.py` |
| Retrieve hotel static data | `meal_data.py` |
| Create booking process | `hotel/order/booking/form/` |
| Start booking process | `hotel/order/booking/finish/` |
| Check booking process | `hotel/order/booking/finish/status/` |
| Retrieve bookings | `hotel/order/info/` |
| Cancel booking | `hotel/order/cancel/` |
