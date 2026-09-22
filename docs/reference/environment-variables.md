# Environment Variables

Documented names only. **Never commit real values.** Examples are placeholders.

Variables appear in `.env.example` and/or are read directly in code/config.

## Application

| Variable | Purpose | Required | Example |
| --- | --- | --- | --- |
| `APP_NAME` | Display name | No | `Booking Core` |
| `APP_ENV` | Environment | Yes | `local` / `production` |
| `APP_KEY` | Encryption key | Yes | output of `key:generate` |
| `APP_DEBUG` | Debug mode | Yes | `false` in production |
| `APP_URL` | Public base URL | Yes | `<application-url>` |
| `APP_ASSET_VERSION` | Asset busting version | No | `3.4.2` |
| `ASSET_URL` | CDN/asset host | No | `<asset-url>` |
| `APP_HTTPS` | HTTPS-related flag | No | `true` / `false` |
| `APP_RESIZE_SIMPLE` | Media resize behavior | No | `true` |
| `APP_PREVIEW_MEDIA_LINK` | Media preview links | No | `false` |

## Database

| Variable | Purpose | Required | Example |
| --- | --- | --- | --- |
| `DB_CONNECTION` | Driver | Yes | `mysql` |
| `DB_HOST` | Host | Yes | `<database-host>` |
| `DB_PORT` | Port | Yes | `3306` |
| `DB_DATABASE` | Database name | Yes | `<database-name>` |
| `DB_USERNAME` | User | Yes | `<database-user>` |
| `DB_PASSWORD` | Password | Yes | `<database-password>` |

## Cache / Session / Queue / Broadcast

| Variable | Purpose | Required | Example |
| --- | --- | --- | --- |
| `CACHE_DRIVER` | Cache store | Yes | `file` |
| `SESSION_DRIVER` | Session store | Yes | `file` |
| `SESSION_LIFETIME` | Minutes | No | `120` |
| `QUEUE_CONNECTION` | Queue | Yes | `sync` |
| `BROADCAST_DRIVER` | Broadcast | No | `log` |
| `FILESYSTEM_DISK` | Default disk | Yes | `local` |
| `MEMCACHED_HOST` | Memcached | No | `<memcached-host>` |
| `REDIS_HOST` | Redis host | No | `<redis-host>` |
| `REDIS_PASSWORD` | Redis password | No | `<redis-password>` |
| `REDIS_PORT` | Redis port | No | `6379` |

## Mail

| Variable | Purpose | Required | Example |
| --- | --- | --- | --- |
| `MAIL_MAILER` | Mailer | Yes | `smtp` |
| `MAIL_HOST` | SMTP host | If SMTP | `<smtp-host>` |
| `MAIL_PORT` | Port | If SMTP | `587` |
| `MAIL_USERNAME` | User | If SMTP | `<smtp-user>` |
| `MAIL_PASSWORD` | Password | If SMTP | `<smtp-password>` |
| `MAIL_ENCRYPTION` | TLS/SSL | No | `tls` |
| `MAIL_FROM_ADDRESS` | From address | Yes | `<from-address>` |
| `MAIL_FROM_NAME` | From name | Yes | `<from-name>` |

## Logging

| Variable | Purpose | Required | Example |
| --- | --- | --- | --- |
| `LOG_CHANNEL` | Default channel | Yes | `daily` |
| `LOG_LEVEL` | Level | Yes | `debug` |
| `LOG_DEPRECATIONS_CHANNEL` | Deprecations | No | `null` |

## AWS / Pusher / Vite-Mix mirrors

| Variable | Purpose | Example |
| --- | --- | --- |
| `AWS_ACCESS_KEY_ID` | S3 key | `<aws-access-key-id>` |
| `AWS_SECRET_ACCESS_KEY` | S3 secret | `<aws-secret-access-key>` |
| `AWS_DEFAULT_REGION` | Region | `<aws-region>` |
| `AWS_BUCKET` | Bucket | `<aws-bucket>` |
| `PUSHER_APP_ID` / `KEY` / `SECRET` / host/port/scheme/cluster | Broadcasting | placeholders |
| `MIX_PUSHER_*` / `VITE_PUSHER_*` | Frontend mirrors | `${PUSHER_*}` |

## reCAPTCHA / Stripe / Services

| Variable | Purpose | Example |
| --- | --- | --- |
| `RECAPTCHA_SITE_KEY` | Captcha site | `<recaptcha-site-key>` |
| `RECAPTCHA_SECRET_KEY` | Captcha secret | `<recaptcha-secret-key>` |
| `RECAPTCHA_LOCAL_*` / `LARATEST_*` / `LIVE_*` | Env-specific overrides | placeholders |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | Stripe | placeholders |
| `MAILGUN_*` / `POSTMARK_TOKEN` | Alternate mail | placeholders |

## Route prefixes

| Variable | Purpose | Default |
| --- | --- | --- |
| `BOOKING_ROUTER_PREFIX` | Booking URL prefix | `booking` |
| `CAR_ROUTER_PREFIX` | Car URL prefix | `car` |
| `FLIGHT_ROUTE_PREFIX` | Flight prefix | `flight` |
| `LOCATION_ROUTER_PREFIX` | Location prefix | `location` |
| `PAGE_ROUTER_PREFIX` | Page prefix | `page` |
| `ADMIN_ROUTER_PREFIX` | Admin prefix | `admin` |
| `BC_ACTIVE_THEME` | Active theme | `BC` |

## ETG Hotel API (code; not all in `.env.example`)

| Variable | Purpose | Example |
| --- | --- | --- |
| `API_URL` | ETG base URL | `<etg-api-base-url>` |
| `API_USERNAME` | Fallback username | `<etg-username>` |
| `API_PASSWORD` | Fallback password | `<etg-password>` |
| `API_USERNAME_B2B` / `API_PASSWORD_B2B` | B2B pair | placeholders |
| `API_USERNAME_B2C` / `API_PASSWORD_B2C` | B2C pair | placeholders |

## Car API (code)

| Variable | Purpose | Example |
| --- | --- | --- |
| `CAR_API_BASE` | API base URL | `<car-api-base-url>` |
| `CAR_API_TOKEN` | Auth token | `<car-api-token>` |
| `CAR_API_REFERER` | Referer parameter | `<car-api-referer>` |

## PCB Bank

| Variable | Purpose | Example |
| --- | --- | --- |
| `PCB_BANK_MERCHANT_ID` | Merchant id | `<pcb-merchant-id>` |
| `PCB_BANK_API_URL` | API base | `<pcb-api-url>` |
| `PCB_BANK_PORTAL_URL` | HPP portal | `<pcb-portal-url>` |
| `PCB_BANK_CERT_PATH` | Client cert PEM | `<path-to-cert.pem>` |
| `PCB_BANK_KEY_PATH` | Client key PEM | `<path-to-key.pem>` |
| `PCB_BANK_CA_PATH` | CA PEM | `<path-to-ca.pem>` |
| `PCB_BANK_TIMEOUT` | Timeout seconds | `30` |
| `PCB_BANK_DEFAULT_CURRENCY` | Currency | `EUR` |
| `PCB_BANK_DEFAULT_LANGUAGE` | Language | `en` |

## Legacy / unused for primary API auth

| Variable | Notes |
| --- | --- |
| `JWT_SECRET` | Present in `.env.example`; API auth is Sanctum—do not treat as active JWT setup |
| `DISABLE_REQUIRE_CHANGE_PW` | Related to require-change-password middleware |

## Related Documentation

- [Configuration](../06-configuration.md)
- [Security](../25-security.md)
- [Integrations](../17-integrations.md)
