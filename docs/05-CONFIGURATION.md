# Configuration

Laravel config lives in `config/`. After install, many runtime values are stored in `core_settings` and applied in `App\Providers\AppServiceProvider::initConfigFromDB`.

Related: [Environment Variables](./06-ENVIRONMENT-VARIABLES.md) · [Installation](./04-INSTALLATION.md)

---

## How configuration is loaded

1. `.env` → `env()` / `config/*.php`
2. If `storage/installed` exists, `initConfigFromDB()` overwrites:
   - `app.name` from `site_title`
   - `app.timezone` from `site_timezone`
   - Mail driver/host/credentials from `email_*` settings
   - Mailgun / Postmark / SES / Sparkpost from settings
   - Pusher / Chatify from `pusher_*` and `broadcast_driver`
   - Filesystem default, S3, GCS from `filesystem_*` / `gcs_*`
   - Disables Fortify 2FA feature if `user_enable_2fa` is empty
3. Locale: `AppServiceProvider::setLang()` uses the first URL segment if it matches an active `Language` row; otherwise `site_locale`.

Admin UI: `GET /admin/module/core/settings/index/{group}` (`Modules\Core\Admin\SettingsController`).

---

## Config files (application-specific)

| File | Role |
|---|---|
| `config/app.php` | Name, env, url, providers, `'version' => "3.4.2"`, `updater_url` |
| `config/bc.php` | Active theme (`BC_ACTIVE_THEME`), media limits, `DISABLE_REQUIRE_CHANGE_PW` |
| `config/modules.php` | Lists core module keys (`core`, `booking`, …). `'active'=>[]` |
| `config/hotel.php` | `HOTEL_ROUTER_PREFIX` default `hotel` |
| `config/booking.php` | Prefix `booking`; booking statuses |
| `config/car.php` `tour.php` `space.php` `event.php` `boat.php` `flight.php` `page.php` `location.php` | URL prefixes |
| `config/payment.php` | Gateway class map |
| `config/pcb_bank.php` | PCB merchant, API URL, cert paths, currencies |
| `config/invoice.php` | Merchant identity on invoices |
| `config/services.php` | Mailgun, Postmark, SES, Sparkpost, reCAPTCHA, Stripe |
| `config/auth.php` | Guard `web`, provider `App\Models\User` |
| `config/fortify.php` | Fortify guard, home `/` |
| `config/sanctum.php` | SPA stateful domains |
| `config/jwt.php` | JWT secret/keys (legacy jwt-auth style file; API login uses Sanctum) |
| `config/chatify.php` | Messenger routes and Pusher |
| `config/installer.php` | PHP 8.0 min, extensions, writable dirs |
| `config/mapengine.php` | Placeholder script URLs (Facebook links in source — not a working map engine config) |
| `config/permissions.php` | Permission definitions |
| `config/post_types.php` | Post types |
| `config/languages.php` | Language config |
| `config/landing.php` | Landing page |
| `config/wallet.php` | Wallet |
| `config/image.php` `image-optimizer.php` | Intervention / optimizer |
| `config/purifier.php` | HTML purifier |
| `config/paystack.php` | Paystack keys via `getenv` |
| `config/debugbar.php` | `DEBUGBAR_ENABLED` |

Standard Laravel files also present: `database.php`, `mail.php`, `cache.php`, `queue.php`, `session.php`, `filesystems.php`, `logging.php`, `broadcasting.php`, `cors.php`, `hashing.php`, `view.php`.

Dashboard merges `modules/Dashboard/Config/config.php` as `config('admin')` → `admin_route_prefix`.

News module has `modules/News/Config/news.php` (`NEWS_ROUTER_PREFIX`, category/tag prefixes).

SMS: `modules/Sms/Core/Config/sms.php` (`SMS_DRIVER`, Nexmo, Twilio).

Pro: `app/Pro/Config/config.php` (`PRO_ENABLE`).

CarRent: `custom/CarRent/Config/config.php`.

---

## Payment gateway registration

`config/payment.php`:

```php
'gateways' => [
    'offline_payment' => Modules\Booking\Gateways\OfflinePaymentGateway::class,
    'paypal' => Modules\Booking\Gateways\PaypalGateway::class,
    'stripe' => Modules\Booking\Gateways\StripeGateway::class,
    'payrexx' => Modules\Booking\Gateways\PayrexxGateway::class,
    'paystack' => Modules\Booking\Gateways\PaystackGateway::class,
    'pcb_bank' => Modules\Booking\Gateways\PcbBankGateway::class,
],
```

Enable/disable and credentials for most gateways are **settings rows**, not only `.env`. PCB also uses `config/pcb_bank.php` env vars. TwoCheckout is a plugin, not in this array.

---

## Theme

`config/bc.php`:

- Query override: `?_xtheme=`
- Else `BC_ACTIVE_THEME`
- Else constant `BC_INIT_THEME` if defined
- Else `'BC'`

`themes/ThemeServiceProvider` prepends `themes/{Active}` to view paths after install.

---

## CORS

`config/cors.php`: paths `api/*` and `sanctum/csrf-cookie`; methods/origins/headers `*`; `supports_credentials` false.

---

## Logging

Default channel `daily` (`LOG_CHANNEL`). Files under `storage/logs`. Admin log viewer route in `routes/web.php`: `{admin_prefix}/logs` with middleware `auth`, `dashboard`, `system_log_view`.

---

## HTTPS

If `APP_HTTPS` is truthy, `AppServiceProvider` calls `URL::forceScheme('https')`.

---

## Demo flags

- `DEMO_MODE` — `app/Helpers/AppHelper.php`
- `BC_DEMO_SCRIPT` — `resources/views/demo_script.blade.php`
