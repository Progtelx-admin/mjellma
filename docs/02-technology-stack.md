# Technology Stack

## Overview

Only technologies verified from project configuration and source are listed.

## Languages & Runtimes

| Technology | Version / constraint | Purpose | Evidence |
| --- | --- | --- | --- |
| PHP | `^8.1.0` | Application runtime | `composer.json`; `public_html/index.php` requires PHP &gt; 8.0.2 |
| JavaScript | ES5/ES6 (not TypeScript) | Admin Vue, frontend scripts | `package.json`, `public_html/js` |
| Python | 3.x (unpinned) | ETG static dump scripts only | `hotels_data.py`, `meal_data.py` |
| SQL / MySQL | Default driver | Primary database | `.env.example` `DB_CONNECTION=mysql` |
| Blade | Laravel views | Server-rendered UI | `modules/*/Views`, `themes/*/Views` |
| Sass / SCSS | via Laravel Mix | Stylesheets | `public_html/themes/*/webpack.mix.js` |

## Backend

| Technology | Version | Purpose | Where used |
| --- | --- | --- | --- |
| Laravel Framework | `^10.0` | HTTP, Eloquent, Artisan | Entire app |
| Laravel Fortify | `^1.10` | Web login, 2FA, password reset, email verification | `app/Providers/FortifyServiceProvider.php`, `config/fortify.php` |
| Laravel Sanctum | `^3.2` | API personal access tokens | `modules/Api`, `app/User.php` |
| Laravel Socialite | `^5.0` | Social OAuth login | `routes/web.php` social routes |
| Laravel UI | `^4.3` | Auth scaffolding support | Composer dependency |
| Guzzle | `^7.2` | HTTP client (via Laravel HTTP) | ETG, car API, PCB |
| Intervention Image | `^2.4` | Image processing | Media |
| Omnipay + PayPal/Stripe/MIGS | various | Payment abstractions | Booking gateways |
| Stripe PHP | `^7.113` | Stripe payments | Gateways / services config |
| Paystack, Flutterwave, Payrexx | various | Additional gateways | Composer + `config/payment.php` / plugins |
| DomPDF | `^3.1` | PDF generation | Invoices / exports |
| Maatwebsite Excel | `^3.1` | Spreadsheet import/export | Admin tooling |
| Chatify | `^1.3.4` | Messaging package | `config/chatify.php` |
| GeoIP2, ICS, QR, Purifier | various | Geo, calendars, QR, HTML sanitize | Modules / helpers |
| AWS S3 Flysystem / GCS | various | Cloud storage options | `composer.json`, filesystems |
| Pusher PHP | `^7.0` | Broadcast notifications | Events |
| rachidlaasri/laravel-installer | `^4.0` | Web installer | `/install` |
| rap2hpoutre/laravel-log-viewer | `^2.2` | Admin log UI | `/admin/logs` |

**Not used as primary API auth:** despite `config/jwt.php` and `JWT_SECRET` in `.env.example`, runtime API auth is **Sanctum**, not `tymon/jwt-auth` (package absent).

## Frontend

| Technology | Version | Purpose | Where used |
| --- | --- | --- | --- |
| Vue | `^2.7.8` | Admin SPA widgets / template editor | `public_html/themes/admin` |
| Bootstrap | `^4.6.2` (npm); HA pages may load Bootstrap 5 from CDN | Layout/components | Themes / HA blades |
| jQuery | `^3.5.1` | Classic frontend interactions | `public_html/js`, module JS |
| Laravel Mix | `^6.0.49` | Asset compilation | Theme `webpack.mix.js` files |
| Axios | `^0.21.4` | HTTP from JS | Admin / Mix deps |
| Font Awesome / Ionicons | 4.x / 4.x | Icons | Layouts |
| Leaflet (+ markercluster) | CDN on HA results | Hotel map UI | `results-ha.blade.php` |

Root `vite.config.js` exists but active UI assets are built with **Mix**, not Vite (`resources/css/app.css` / `resources/js/app.js` are not the live pipeline).

## Data & Infrastructure Defaults

| Concern | Default in `.env.example` | Notes |
| --- | --- | --- |
| Database | MySQL | Required for app |
| Cache | `file` | Redis optional via env |
| Queue | `sync` | No worker required unless changed |
| Session | `file` | — |
| Broadcast | `log` | Pusher optional |
| Filesystem | `local` | S3/GCS optional |

## Package Managers

| Tool | Lockfile | Use |
| --- | --- | --- |
| Composer | `composer.lock` | PHP deps |
| npm | `package-lock.json` | Root + theme JS |
| yarn | `yarn.lock` | Also present; either client works |

## Testing

| Tool | Version | Purpose |
| --- | --- | --- |
| PHPUnit | `^10.1` | Unit/Feature tests under `tests/` |

Current coverage is essentially Laravel stubs (`ExampleTest`). See [Testing](./22-testing.md).

## External Services (conceptual)

| Service | Role | Client code |
| --- | --- | --- |
| ETG / WorldOTA B2B | Hotel rates & booking | `HotelHController` |
| External car API | Car search/checkout | `CarController` |
| PCB Bank | Card HPP + mTLS API | `PcbBankService`, `PcbBankGateway` |
| PayPal / Stripe / Paystack / Payrexx | Alternate gateways | Booking gateways |
| Social OAuth providers | Social login | Socialite |
| reCAPTCHA | Bot protection | `config/services.php`, `ReCaptchaEngine` |

## Related Documentation

- [Project Architecture](./03-project-architecture.md)
- [Build & Development Tools](./23-build-and-development-tools.md)
- [Integrations](./17-integrations.md)
