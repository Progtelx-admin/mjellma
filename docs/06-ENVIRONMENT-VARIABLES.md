# Environment Variables

Variables below are actually read via `env()`, `getenv()`, or appear in `.env.example`. Secrets are shown as **placeholders only**.

`.env.example` does **not** list ETG, car API, or PCB variables. Those are still required for those features.

Related: [Configuration](./05-CONFIGURATION.md) · [External Integrations](./13-EXTERNAL-INTEGRATIONS.md)

---

## Application

| Variable | Required | Used by | Purpose | Example |
|---|---|---|---|---|
| `APP_NAME` | No | `config/app.php` | App name | `BookingCore` |
| `APP_ENV` | No | `config/app.php` | Environment | `production` |
| `APP_KEY` | Yes | `config/app.php` | Encryption key | `base64:YOUR_APP_KEY` |
| `APP_DEBUG` | No | `config/app.php` | Debug / which Vue build | `true` |
| `APP_URL` | Yes | `config/app.php` | Root URL | `http://localhost` |
| `ASSET_URL` | No | `config/app.php` | CDN for assets | empty |
| `APP_ASSET_VERSION` | No | `config/app.php` | Cache-bust version | `3.4.2` |
| `APP_HTTPS` | No | `AppServiceProvider` | Force HTTPS | `false` |
| `APP_RESIZE_SIMPLE` | — | `.env.example` only | Not referenced in PHP `env()` searches of app/modules/config beyond the example file | `true` |
| `APP_PREVIEW_MEDIA_LINK` | — | `.env.example` only | Same | `false` |
| `APP_BASE_PATH` | No | `bootstrap/app.php` | Override base path | not set |

---

## Database

| Variable | Required | Used by | Example |
|---|---|---|---|
| `DB_CONNECTION` | Yes | `config/database.php` | `mysql` |
| `DB_HOST` | Yes | same | `127.0.0.1` |
| `DB_PORT` | Yes | same | `3306` |
| `DB_DATABASE` | Yes | same | `booking_core` |
| `DB_USERNAME` | Yes | same | `root` |
| `DB_PASSWORD` | Yes | same | `YOUR_DB_PASSWORD` |
| `DB_SOCKET` | No | mysql config | empty |
| `DATABASE_URL` | No | `config/database.php` | empty |
| `DB_FOREIGN_KEYS` | No | sqlite | `true` |
| `MYSQL_ATTR_SSL_CA` | No | mysql SSL | empty |

---

## Logs, cache, session, queue

| Variable | Used by | Example |
|---|---|---|
| `LOG_CHANNEL` | `config/logging.php` | `daily` |
| `LOG_LEVEL` | same | `debug` |
| `LOG_DEPRECATIONS_CHANNEL` | same | `null` |
| `LOG_SLACK_WEBHOOK_URL` | slack log channel | `https://hooks.slack.com/YOUR_WEBHOOK` |
| `LOG_PAPERTRAIL_HANDLER` `PAPERTRAIL_URL` `PAPERTRAIL_PORT` | papertrail | |
| `LOG_STDERR_FORMATTER` | stderr channel | |
| `CACHE_DRIVER` | `config/cache.php` | `file` |
| `CACHE_PREFIX` | same | |
| `MEMCACHED_HOST` `MEMCACHED_PORT` `MEMCACHED_PERSISTENT_ID` `MEMCACHED_USERNAME` `MEMCACHED_PASSWORD` | memcached | |
| `QUEUE_CONNECTION` | `config/queue.php` | `sync` |
| `QUEUE_FAILED_DRIVER` | same | |
| `REDIS_QUEUE` `SQS_*` | queue redis/sqs | |
| `SESSION_DRIVER` | `config/session.php` | `file` |
| `SESSION_LIFETIME` | same | `120` |
| `SESSION_CONNECTION` `SESSION_STORE` `SESSION_DOMAIN` `SESSION_SECURE_COOKIE` `SESSION_COOKIE` | session | |

---

## Redis

| Variable | Used by | Example |
|---|---|---|
| `REDIS_CLIENT` | `config/database.php` | `phpredis` |
| `REDIS_HOST` | same | `127.0.0.1` |
| `REDIS_PASSWORD` | same | `null` |
| `REDIS_PORT` | same | `6379` |
| `REDIS_USERNAME` `REDIS_URL` `REDIS_CLUSTER` `REDIS_PREFIX` `REDIS_DB` `REDIS_CACHE_DB` | redis | |

---

## Mail

| Variable | Used by | Example |
|---|---|---|
| `MAIL_MAILER` | `config/mail.php` | `sendmail` |
| `MAIL_HOST` | same | `smtp.mailtrap.io` |
| `MAIL_PORT` | same | `2525` |
| `MAIL_USERNAME` `MAIL_PASSWORD` | same | `YOUR_SMTP_USER` |
| `MAIL_ENCRYPTION` | same | `ssl` |
| `MAIL_FROM_NAME` `MAIL_FROM_ADDRESS` | same | |
| `MAIL_URL` `MAIL_EHLO_DOMAIN` `MAIL_SENDMAIL_PATH` `MAIL_LOG_CHANNEL` | mailers | |
| `MAILGUN_DOMAIN` `MAILGUN_SECRET` `MAILGUN_ENDPOINT` | `config/services.php` | |
| `POSTMARK_TOKEN` | same | `YOUR_POSTMARK_TOKEN` |
| `SPARKPOST_SECRET` | same | `YOUR_SPARKPOST_SECRET` |

Installed-app mail settings in the database **override** these when set.

---

## AWS / S3 / GCS

| Variable | Used by |
|---|---|
| `AWS_ACCESS_KEY_ID` `AWS_SECRET_ACCESS_KEY` `AWS_DEFAULT_REGION` `AWS_BUCKET` `AWS_URL` `AWS_ENDPOINT` `AWS_USE_PATH_STYLE_ENDPOINT` | `config/filesystems.php`, SES, SQS, DynamoDB cache |
| `GOOGLE_CLOUD_KEY_FILE` `GOOGLE_CLOUD_PROJECT_ID` `GOOGLE_CLOUD_STORAGE_BUCKET` `GOOGLE_CLOUD_STORAGE_PATH_PREFIX` `GOOGLE_CLOUD_STORAGE_API_URI` | GCS disk |
| `FILESYSTEM_DISK` | default disk (`local`) |

---

## Broadcasting / Pusher / Chatify / Vite Mix placeholders

| Variable | Used by |
|---|---|
| `BROADCAST_DRIVER` | `config/broadcasting.php` |
| `PUSHER_APP_ID` `PUSHER_APP_KEY` `PUSHER_APP_SECRET` `PUSHER_HOST` `PUSHER_PORT` `PUSHER_SCHEME` `PUSHER_APP_CLUSTER` `PUSHER_APP_USETLS` | broadcasting + `config/chatify.php` |
| `ABLY_KEY` | broadcasting |
| `VITE_PUSHER_*` `MIX_PUSHER_*` | `.env.example` for frontend; Vite entry files were not found |
| `CHATIFY_NAME` `CHATIFY_PATH` `CHATIFY_ROUTES_PREFIX` `CHATIFY_ROUTES_MIDDLEWARE` `CHATIFY_ROUTES_NAMESPACE` `CHATIFY_API_ROUTES_*` | `config/chatify.php` |

---

## Auth / security

| Variable | Used by |
|---|---|
| `JWT_SECRET` `JWT_PUBLIC_KEY` `JWT_PRIVATE_KEY` `JWT_PASSPHRASE` `JWT_TTL` `JWT_REFRESH_TTL` `JWT_ALGO` `JWT_LEEWAY` `JWT_BLACKLIST_ENABLED` `JWT_BLACKLIST_GRACE_PERIOD` | `config/jwt.php` |
| `SANCTUM_STATEFUL_DOMAINS` `SANCTUM_TOKEN_PREFIX` | `config/sanctum.php` |
| `DISABLE_REQUIRE_CHANGE_PW` | `config/bc.php` |
| `BCRYPT_ROUNDS` | `config/hashing.php` |
| `RECAPTCHA_SITE_KEY` `RECAPTCHA_SECRET_KEY` | `config/services.php` (overrides if set) |
| `RECAPTCHA_LOCAL_*` `RECAPTCHA_LARATEST_*` `RECAPTCHA_LIVE_*` | per-environment reCAPTCHA |

---

## Route prefixes

| Variable | Default | Config |
|---|---|---|
| `ADMIN_ROUTER_PREFIX` | `admin` | `modules/Dashboard/Config/config.php` |
| `HOTEL_ROUTER_PREFIX` | `hotel` | `config/hotel.php` |
| `BOOKING_ROUTER_PREFIX` | `booking` | `config/booking.php` |
| `CAR_ROUTER_PREFIX` | `car` | `config/car.php` |
| `TOUR_ROUTER_PREFIX` | `tour` | `config/tour.php` |
| `SPACE_ROUTER_PREFIX` | `space` | `config/space.php` |
| `EVENT_ROUTER_PREFIX` / `EVENT_ROUTE_PREFIX` | `event` | `config/event.php` and some views/models |
| `BOAT_ROUTER_PREFIX` | `boat` | `config/boat.php` |
| `FLIGHT_ROUTE_PREFIX` | `flight` | `config/flight.php` |
| `PAGE_ROUTER_PREFIX` | `page` | `config/page.php` |
| `LOCATION_ROUTER_PREFIX` | `location` | `config/location.php` |
| `NEWS_ROUTER_PREFIX` `NEWS_CATEGORY_ROUTER_PREFIX` `NEWS_TAG_ROUTER_PREFIX` | news/category/tag | `modules/News/Config/news.php` |
| `TOPIC_ROUTE_PREFIX` `TOPIC_CATEGORY_ROUTE_PREFIX` | topic/cat | `pro/Support/Configs/config.php` |

---

## ETG / RateHawk (not in `.env.example`)

| Variable | Required for hotels | Used by | Example |
|---|---|---|---|
| `API_URL` | Yes | `HotelHController` | `https://api.worldota.net/api/b2b/v3/` |
| `API_USERNAME` | Fallback | same | `YOUR_ETG_KEY_ID` |
| `API_PASSWORD` | Fallback | same | `YOUR_ETG_API_KEY` |
| `API_USERNAME_B2B` `API_PASSWORD_B2B` | B2B users | same | |
| `API_USERNAME_B2C` `API_PASSWORD_B2C` | Guests / B2C | same | |

Selection logic: `getUserType()` then `getApiUsername()` / `getApiPassword()`. Query `?type=b2b|b2c` on some requests uses `getManualApiCredentials()`. Copy-paste `.env` block and role rules: [B2B / B2C credentials](./28-B2B-B2C-CREDENTIALS.md).

---

## Car API (not in `.env.example`)

| Variable | Used by | Example |
|---|---|---|
| `CAR_API_BASE` | `CarController`, Blade defaults | `https://dev.example-car-api.com/api` |
| `CAR_API_TOKEN` | `CarController` Authorization header (no Bearer prefix) | `YOUR_CAR_API_TOKEN` |
| `CAR_API_REFERER` | query/body `referer` | your site host |
| `CAR_IMAGE_BASE_URL` | Blade car images | URL prefix |

---

## PCB Bank

| Variable | Used by | Example |
|---|---|---|
| `PCB_BANK_MERCHANT_ID` | `config/pcb_bank.php` | `YOUR_MERCHANT_ID` |
| `PCB_BANK_API_URL` | same | `https://3dss2test.quipu.de:8000` |
| `PCB_BANK_PORTAL_URL` | same | `https://3dss2test.quipu.de:8004/` |
| `PCB_BANK_CERT_PATH` `PCB_BANK_KEY_PATH` `PCB_BANK_CA_PATH` | mTLS | `storage/certs/cert.pem` |
| `PCB_BANK_TIMEOUT` | seconds | `30` |
| `PCB_BANK_DEFAULT_CURRENCY` | | `EUR` |
| `PCB_BANK_DEFAULT_LANGUAGE` | | `en` |

---

## Payments (other)

| Variable | Used by |
|---|---|
| `STRIPE_KEY` `STRIPE_SECRET` `STRIPE_WEBHOOK_SECRET` `STRIPE_WEBHOOK_TOLERANCE` | `config/services.php` |
| `PAYSTACK_PUBLIC_KEY` `PAYSTACK_SECRET_KEY` `PAYSTACK_PAYMENT_URL` `MERCHANT_EMAIL` | `config/paystack.php` (`getenv`) |

PayPal/Payrexx credentials are primarily **admin settings**, not env keys found in config.

---

## SMS

| Variable | Used by |
|---|---|
| `SMS_DRIVER` | `modules/Sms/Core/Config/sms.php` |
| `SMS_NEXMO_FROM` `SMS_NEXMO_KEY` `SMS_NEXMO_SECRET` | Nexmo |
| `SMS_TWILIO_FROM` `SMS_TWILIO_ACCOUNTSID` `SMS_TWILIO_TOKEN` | Twilio |

---

## Media / theme / misc

| Variable | Used by |
|---|---|
| `BC_ACTIVE_THEME` | `config/bc.php` |
| `ALLOW_IMAGE_MAX_WIDTH` `ALLOW_IMAGE_MAX_HEIGHT` | media groups |
| `BC_MEDIA_OPTIMIZE_IMAGE` `BC_MEDIA_PREVIEW_DIRECT` | media |
| `DEMO_MODE` | `AppHelper` |
| `BC_DEMO_SCRIPT` | demo script view |
| `PRO_ENABLE` | `app/Pro/Config/config.php` |
| `AI_PROVIDER` | `pro/Ai/Configs/config.php` — default `main` |
| `AI_MAIN_API_KEY` | same — Pro AI provider API key (`YOUR_AI_API_KEY`) |
| `AI_MAIN_MODEL` | same — model id (`YOUR_AI_MODEL`) |
| `DEBUGBAR_ENABLED` | `config/debugbar.php` |
| `INVOICE_MERCHANT_*` `INVOICE_EMAIL_*` | `config/invoice.php` |

---

## Variables in `.env.example` with little or no PHP usage found

`APP_RESIZE_SIMPLE`, `APP_PREVIEW_MEDIA_LINK` appear only in `.env.example` in this analysis. Treat as unused unless you find new references.

---

## Testing (`phpunit.xml`)

Overrides: `APP_ENV=testing`, `BCRYPT_ROUNDS=4`, `CACHE_DRIVER=array`, `MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `TELESCOPE_ENABLED=false`. SQLite in-memory is commented out.
