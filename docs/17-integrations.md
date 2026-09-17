# Integrations

## Overview

Outbound integrations used by the current implementation. Credentials must stay in environment/secure storage—never in documentation.

## ETG / RateHawk / WorldOTA (Hotels)

| Item | Detail |
| --- | --- |
| Client | `modules/Hotel/Controllers/HotelHController.php` |
| Base URL env | `API_URL` (code default points at WorldOTA B2B v3 path) |
| Auth | HTTP Basic — username/password env pairs |
| Credential sets | Shared fallback + `*_B2B` + `*_B2C` |
| Networking | cURL options force IPv4 to avoid IPv6 allowlist issues |
| Static dump | `hotels_data.py`, `meal_data.py` |

Official supplier docs (external): [Emerging Travel B2B API](https://docs.emergingtravel.com/docs/b2b-api/).

Typical operations: region/hotel search, hotelpage, prebook, order booking finish/status, cancel.

Diagnostics: `GET /hotel/test-api-credentials` returns whether env vars are **set** (not secret values)—remove or protect in production.

## External Car API

| Item | Detail |
| --- | --- |
| Client | `modules/Car/Controllers/CarController.php` |
| Env | `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER` |
| Auth header | `Authorization: {token}` (no `Bearer` prefix in code) |
| Endpoints used | company-locations, reservations, details, manufacturers, categories, transmissions, checkout/store-checkout |
| Cache | Metadata responses ~1 hour |

## PCB Bank

| Item | Detail |
| --- | --- |
| Service | `app/Services/PcbBankService.php` |
| Gateway | `Modules\Booking\Gateways\PcbBankGateway` |
| Config | `config/pcb_bank.php` |
| Env | `PCB_BANK_MERCHANT_ID`, `PCB_BANK_API_URL`, `PCB_BANK_PORTAL_URL`, cert paths, timeout, currency/language |
| Auth | Mutual TLS client certificate |
| Flow | `createOrder` → redirect portal HPP → return URL → status check |

Artisan helpers exist for PCB testing/enablement under `app/Console/Commands`.

## Other Payment Providers

Omnipay PayPal/Stripe, Paystack, Payrexx, offline, plus TwoCheckout plugin. Keys via settings/`config/services.php` env bindings (`STRIPE_*`, etc.).

## Social OAuth

Socialite providers configured through Laravel services/env; routes in `routes/web.php`.

## reCAPTCHA

`config/services.php` supports site/secret keys plus local/laratest/live overrides. Admin advanced settings may also store live keys.

## Cloud Storage

Optional AWS S3 and Google Cloud Storage packages for media disks.

## Mail Providers

Laravel mailers: SMTP, Sendmail, Mailgun, Postmark, etc., via `MAIL_*` / service secrets.

## Broadcasting

Pusher env vars present; broadcast driver defaults to `log` in `.env.example`.

## Python ETL

Not a runtime web dependency. Scripts download/decompress supplier dumps and write MySQL tables. Configure DB/API locally; **do not commit credentials** that may appear in script copies.

## Related Documentation

- [Workflows](./14-workflows.md)
- [Environment Variables](./reference/environment-variables.md)
- [Security](./25-security.md)
