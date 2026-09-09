# Payments and Invoices

Related: [External Integrations](./13-EXTERNAL-INTEGRATIONS.md) · [Hotel Search](./21-HOTEL-SEARCH.md) · [Configuration](./05-CONFIGURATION.md)

---

## Gateway map

`config/payment.php` class list: offline, paypal, stripe, payrexx, paystack, pcb_bank.

Enable flags live in `core_settings` (e.g. `g_pcb_bank_enable` via `php artisan pcb:enable`). Admin booking settings views: `modules/Booking/Views/admin/settings/`.

Plugin TwoCheckout is separate (`plugins/PaymentTwoCheckout`).

Hotel HA checkout comments state **only PCB Bank** is offered on confirmation (RateHawk payment types commented in `booking-confirmation-ha.blade.php`).

**Step-by-step hotel PCB flow (session keys, return query, ETG deposit finish):** [PCB payment flow](./27-PCB-PAYMENT-FLOW.md).

**B2B vs B2C amounts charged to PCB:** [B2B / B2C credentials](./28-B2B-B2C-CREDENTIALS.md).

---

## PCB Bank

**Config file:** `config/pcb_bank.php`

**Service:** `app/Services/PcbBankService.php`

**Gateway:** `modules/Booking/Gateways/PcbBankGateway.php` (`$id = 'pcb_bank'`)

**mTLS:** `cert.pem`, `key.pem`, `ca.pem` (default `storage/certs/`).

**Default API (config):** `https://3dss2test.quipu.de:8000` — test host. Override with `PCB_BANK_API_URL` for live.

**createOrder payload (service):** `order.typeRid` `"225"`, amount, currency (default EUR), description, language, `hppRedirectUrl`, browser device info from the current request.

**Return URLs in this app:**

- Hotels: `GET /pcb-return` → `HotelHController@handlePcbReturn`
- Cars: `GET /car/pcb/return` → `CarController@handlePcbReturn`
- Booking Core: gateway confirm/cancel routes under `/booking` and `/gateway`

**Supported currencies in config:** EUR, USD, ALL. Languages: en, sq, de.

**CLI:** `pcb:enable`, `pcb:status`, `pcb:test`, `pcb:test-simple`, `pcb:test-curl`.

**HTTP tests:** `/pcb-test`, `/pcb-test-order` (creates a 10.00 order if configured), `/pcb-test-redirect`. Remove or protect in production.

---

## Other gateways (Booking Core cart)

| Gateway | Class | Notes |
|---|---|---|
| PayPal | `PaypalGateway` | Omnipay; settings-based |
| Stripe | `StripeGateway` | Also `StripeCheckoutGateway`; `STRIPE_*` env |
| Paystack | `PaystackGateway` | `PAYSTACK_*` env |
| Payrexx | `PayrexxGateway` | Package `payrexx/payrexx` |
| Offline | `OfflinePaymentGateway` | Manual |

Webhook: `BookingController@callbackPayment` at `/gateway/gateway_callback/{gateway}`.

---

## Invoices

**Config:** `config/invoice.php` (merchant name/address/email; override with `INVOICE_MERCHANT_*`).

**Service:** `app/Services/InvoiceService.php` — `createInvoice(Payment $payment)` using `PcbBankGateway::getInvoiceData`. PDF method currently writes **HTML** to `storage/app/invoices/` (`generatePdf` comments mention DomPDF for production; `barryvdh/laravel-dompdf` is in Composer).

**Model:** `App\Models\Invoice` (table `invoices`).

**Hotel path:** `HotelHController` `createPaymentRecordAndInvoice`, `showBookingInvoice`.

**CarRent admin:** `downloadInvoicePdf` on `CarRentReservationController`.

---

## Wallet

`config/wallet.php` + `App\User` `HasWallet`. User routes `/user/wallet`. Separate from PCB hotel flow.

---

## File-level

**File:** `app/Services/PcbBankService.php`

**Purpose:** Authenticated HTTPS client to PCB order API.

**Main methods:** `createOrder`, `getOrderDetails`, `isConfigured`, `makeAuthenticatedRequest` (private/protected in class).

**Depends on:** `config('pcb_bank.*')`, Laravel Http, local PEM files.

**Used by:** `HotelHController`, `CarController`, `PcbBankGateway`, PCB Artisan commands, `/pcb-test` closures.

**File:** `modules/Booking/Gateways/PcbBankGateway.php`

**Purpose:** Booking Core gateway adapter (`enable`, name, logo, HTML) plus invoice helpers for PCB responses.

**Used by:** checkout for `bravo_bookings` and `InvoiceService`.
