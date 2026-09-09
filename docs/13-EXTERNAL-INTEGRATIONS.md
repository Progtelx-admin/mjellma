# External Integrations

Integrations found in PHP/Python source and Composer packages. Credentials: placeholders only.

Related: [Hotel Search](./21-HOTEL-SEARCH.md) · [Car Rental](./22-CAR-RENTAL.md) · [Payments](./23-PAYMENTS.md) · [Environment Variables](./06-ENVIRONMENT-VARIABLES.md)

---

## 1. Emerging Travel Group / RateHawk / WorldOTA (ETG)

**Name:** WorldOTA B2B API v3 (RateHawk / Emerging Travel). Comments in `HotelHController` refer to RateHawk and ETG.

**Configured in:** environment `API_URL`, `API_USERNAME`, `API_PASSWORD`, `API_USERNAME_B2B`, `API_PASSWORD_B2B`, `API_USERNAME_B2C`, `API_PASSWORD_B2C`. Default URL `https://api.worldota.net/api/b2b/v3/`.

**Client:** `modules/Hotel/Controllers/HotelHController.php` (Laravel `Http` + Basic Auth). Python: `hotels_data.py`, `meal_data.py`.

**Auth:** HTTP Basic. Requests force IPv4 (`force_ip_resolve` / `CURLOPT_IPRESOLVE`) to avoid `not_allowed_host` on IPv6.

**Features used (PHP):**

| ETG path | Called from | Purpose |
|---|---|---|
| `search/multicomplete/` | `getHotelSuggestions` | Hotel + region autocomplete |
| `search/serp/region/` | `getHotelIdsForRegion` | Hotel IDs for a city `region_id` (cached 15 min); not `search/hotelsort/` |
| `search/serp/hotels` / `search/serp/hotels/` | `loadHotelChunk`, `fetchApiData` | Live prices for hotel id lists |
| `search/hp/` | `hotelInfo` | Hotel page rates (`id` = hotel_id) |
| `hotel/info/` | `hotelInfo` | Static info by numeric `hid` |
| `hotel/prebook/` | `prebookRoom` | Prebook by `hash` |
| `hotel/order/booking/form/` | `bookRoom`, `sendBookingForm` | Booking form / order start |
| `hotel/order/booking/credit-card/` | `getCardTokenFromPcb` | Card token path |
| `hotel/order/booking/finish/` | `processPayment`, `finishBooking`, `completeBooking` | Complete order |
| `hotel/order/booking/finish/status/` | `pollFinishStatus`, booking details | Poll finish |
| `hotel/order/info/` | several | Order info |
| `hotel/order/cancel/` | `cancelBooking` | Cancel |

**Python:**

| Path | Script |
|---|---|
| `POST .../hotel/info/dump/` | `hotels_data.py` — dump URL then zstd stream |
| `GET .../hotel/static/` | `meal_data.py` — `data.meals` |

**Depends on:** local tables `hotels`, `hotel_images`; B2B/B2C user typing.

**Errors:** HTTP failures logged; suggestions return `{error: true}`; search `back()->with('error')`; prebook `withErrors`. Finish polling skips some documented terminal errors (see comments around line 198–268 in the controller).

Internal feature: entire HA hotel product. Dedicated write-up: [Hotel Search](./21-HOTEL-SEARCH.md). Official ETG pages mapped 1:1: [ETG B2B API](./29-ETG-B2B-API.md).

---

## 2. Car rental HTTP API

**Configured in:** `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER`, `CAR_IMAGE_BASE_URL`.

**Client:** `modules/Car/Controllers/CarController.php` `makeApiRequest()`.

**Auth:** header `Authorization: {token}` **without** `Bearer`. Optional `referer` query/body.

**Endpoints used (relative to `CAR_API_BASE`):**

- `company-locations`
- `reservations`
- `reservations/details/{carId}`
- `car-manufacturers`
- `car-categories`
- `car-transmissions`
- `checkout` (POST)
- `store-checkout` (POST)

Default mentioned in a Blade file if env empty: `https://dev.rentacar-orange.com/api` (`themes/BC/Car/Views/frontend/layouts/search/api-car-item.blade.php`). That is a view fallback, not `CarController` constructor (constructor uses `env('CAR_API_BASE')` with no default).

**Admin agency API (separate):** `CarRentReservationController` uses `https://dev.rentacar-orange.com/api/agency` plus a hardcoded Bearer token (not `CAR_API_*`). Paths include `/reservations`. Treat the token as a secret.

**Feature:** [Car Rental](./22-CAR-RENTAL.md). Admin listing: `Custom\CarRent`.

---

## 3. PCB Bank (Quipu 3DS test host in config defaults)

**Config:** `config/pcb_bank.php`

**Client:** `app/Services/PcbBankService.php` — mTLS using cert/key/ca PEM files.

**Methods:** `createOrder` → `POST /order`; `getOrderDetails` → `GET /order/{id}?password=...`; other helpers in the same class.

**Gateway wrapper:** `Modules\Booking\Gateways\PcbBankGateway`

**Used by:** ETG hotel payment, car checkout return, Booking Core checkout if gateway enabled.

**Test routes:** `/pcb-test`, `/pcb-test-order`, `/pcb-test-redirect` in `routes/web.php`.

---

## 4. PayPal (Omnipay)

**Package:** `omnipay/paypal`

**Class:** `Modules\Booking\Gateways\PaypalGateway`

**Config:** admin settings (not dedicated env keys in `config/services.php`).

---

## 5. Stripe

**Packages:** `omnipay/stripe`, `stripe/stripe-php`

**Classes:** `StripeGateway`, `StripeCheckoutGateway`

**Env:** `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `STRIPE_WEBHOOK_TOLERANCE`

---

## 6. Paystack

**Package:** `unicodeveloper/laravel-paystack`

**Class:** `PaystackGateway`

**Env:** `PAYSTACK_PUBLIC_KEY`, `PAYSTACK_SECRET_KEY`, `PAYSTACK_PAYMENT_URL`, `MERCHANT_EMAIL`

---

## 7. Payrexx

**Package:** `payrexx/payrexx`

**Class:** `PayrexxGateway`

---

## 8. Flutterwave

**Package:** `flutterwavedev/flutterwave-v3` in `composer.json`. No dedicated gateway class was found under `modules/Booking/Gateways`. Not determinable how/if it is wired into checkout.

---

## 9. TwoCheckout (plugin)

**Path:** `plugins/PaymentTwoCheckout/Gateway/TwoCheckoutGateway.php`

Registered via `Plugins\ServiceProvider`.

---

## 10. Offline payment

`Modules\Booking\Gateways\OfflinePaymentGateway`

---

## 11. Social login (Google, Facebook, Twitter)

**Package:** `laravel/socialite`

**Controller:** `app/Http/Controllers/Auth/LoginController.php`

**Credentials:** `setting_item('{provider}_client_id')` etc.

---

## 12. reCAPTCHA

**Config:** `config/services.php` `recaptcha` + local/laratest/live key pairs.

**Engine:** `app/Helpers/ReCaptchaEngine.php` (v2 scripts; enable via setting `recaptcha_enable` as used by that helper). Login uses `Modules\User\CustomFortifyAuthenticationProvider`, which binds a custom Fortify `LoginRequest` that can run captcha.

---

## 13. Mail: SMTP, Mailgun, Postmark, SES, Sparkpost

`.env` mail vars and/or `core_settings` email_* (see `AppServiceProvider`). Packages: `symfony/mailgun-mailer`, `symfony/postmark-mailer`.

---

## 14. Pusher + Chatify

**Packages:** `pusher/pusher-php-server`, `munafio/chatify`

**Config:** `config/broadcasting.php`, `config/chatify.php`

User chat: `modules/User` ChatController + Chatify routes prefix `chatify`.

---

## 15. AWS S3 and Google Cloud Storage

`league/flysystem-aws-s3-v3`, `spatie/laravel-google-cloud-storage`. Disks in `config/filesystems.php`. Runtime overlay from settings.

---

## 16. SMS: Nexmo / Twilio

`modules/Sms/Core/Config/sms.php` — `SMS_DRIVER`, Nexmo and Twilio env vars.

---

## 17. GeoIP

**Package:** `geoip2/geoip2`

**Data:** `resources/GeoLite2-City_20210622/` (vendor DB files). How it is invoked is not fully traced here; the database files are present.

---

## 18. Maps

Runtime maps are **not** driven by `config/mapengine.php` (that file contains placeholder Facebook URLs). `app/Helpers/MapEngine.php` loads scripts from **settings**:

- `map_provider` = `gmap` → Google Maps JS with `map_gmap_key` + MarkerClusterer + `libs/infobox.js`
- `map_provider` = `osm` → Leaflet from `public_html/libs/leaflet1.4.0/`

Plus `public_html/module/core/js/map-engine.js`. No `GOOGLE_MAPS_KEY` env var was found; the Google key is the `map_gmap_key` setting.

---

## 19. Booking Core updater

`config/app.php` `'updater_url' => "http://check.bookingcore.co/updater.php"`

Admin: `Modules\Core\Admin\UpdaterController` (`/admin/module/core/updater`).

---

## 20. Pro AI

**Config:** `pro/Ai/Configs/config.php`

**Env:** `AI_PROVIDER` (default `main`), `AI_MAIN_API_KEY`, `AI_MAIN_MODEL`. Provider `driver` string in that file is empty in source. Used for title/content generation data types (`max_tokens` 30 / 150). Gated by Pro being loaded (`pro/Ai`).

---

## 21. iCal

Packages `eluceo/ical`, `johngrogg/ics-parser`. Booking export: `BookingController@exportIcal`. Hotel admin views include ical blades.

---

## Integrations not found

- No Docker-based third-party sidecars
- No dedicated Elasticsearch/Algolia client in composer.json
- ETG dump scripts do **not** use Laravel env; they hardcode credentials in the `.py` files
