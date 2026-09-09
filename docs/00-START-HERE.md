# Start Here

This is the practical onboarding guide for a new developer opening this repository.

The application is a **Laravel 10** booking platform (Booking Core, application version `3.4.2` in `config/app.php`) customized as **Mjellma**. The live hotel search homepage is **not** the original Booking Core hotel catalog. It searches a local `hotels` table and calls the **Emerging Travel Group (ETG) / RateHawk / WorldOTA** B2B API. Car rental is similarly proxied to an external car API.

Related docs: [Project Overview](./01-PROJECT-OVERVIEW.md) · [Installation](./04-INSTALLATION.md) · [Environment Variables](./06-ENVIRONMENT-VARIABLES.md) · [Running](./07-RUNNING-THE-PROJECT.md) · [Troubleshooting](./18-TROUBLESHOOTING.md)

---

## 1. Required software

From `composer.json`, `config/installer.php`, and `public_html/index.php`:

| Software | Why |
|---|---|
| PHP | `composer.json` requires `php: ^8.1.0`. `public_html/index.php` dies unless PHP is greater than `8.0.2`. The installer config minimum is `8.0`. Use **PHP 8.1+**. |
| Composer | PHP dependency manager |
| MySQL | Default `DB_CONNECTION` in `.env.example` is `mysql` |
| Node.js + npm (or yarn) | Frontend asset builds (`package.json`, `public_html/themes/*/package.json`). A Node version is **not** pinned in the repository. |
| Web server | Apache with `mod_rewrite` is listed in `config/installer.php`. Laragon is a typical local stack for this tree. |
| OpenSSL, PDO, mbstring, tokenizer, JSON, cURL, fileinfo | PHP extensions required by `config/installer.php` |

Optional, depending on features you enable:

- Redis (`REDIS_*` in `.env.example`)
- Python 3 plus `requests`, `zstandard`, and `mysql-connector-python` — only if you run the hotel dump / meal-type scripts. See [Python](./14-PYTHON.md). No `requirements.txt` exists.
- PCB Bank TLS certificates under `storage/certs/` — only if you use PCB payments. See [Payments](./23-PAYMENTS.md).

No `Dockerfile` or `docker-compose.yml` is present. `laravel/sail` is a Composer **dev** dependency, but this repository does not contain Sail compose files.

---

## 2. Required runtime versions

| Runtime | Source | Value |
|---|---|---|
| PHP | `composer.json` | `^8.1.0` |
| Laravel | `composer.json` | `^10.0` |
| Application version string | `config/app.php` `'version'` | `3.4.2` |
| Active theme version | `themes/BC/ThemeProvider.php` | `3.6.0` |
| Node | — | Not determinable from the current repository |

---

## 3. Dependencies

PHP packages are declared in `composer.json` (Laravel 10, Fortify, Sanctum, Socialite, Guzzle, Omnipay, Stripe, Paystack, Payrexx, Flutterwave, Intervention Image, Chatify, DomPDF, Maatwebsite Excel, GeoIP2, and others).

Install PHP dependencies from the **repository root**:

```powershell
composer install
```

JavaScript packages:

```powershell
npm install
```

A `yarn.lock` also exists. Either npm or yarn can be used; the repository does not document a preferred client.

Theme-specific Node packages (admin Vue/Sass build):

```powershell
cd public_html\themes\admin
npm install
```

Frontend Sass build:

```powershell
cd public_html\themes\bc
npm install
```

Python dump scripts have **no** `requirements.txt`. Inferred imports: `requests`, `zstandard`, `mysql.connector`. See [Python](./14-PYTHON.md).

---

## 4. Environment setup

1. Copy the example env file (Composer also copies it on `post-root-package-install` if `.env` is missing):

```powershell
copy .env.example .env
```

2. Generate an application key:

```powershell
php artisan key:generate
```

3. Edit `.env` with your database, `APP_URL`, and integration credentials. Do not commit secrets. Full reference: [Environment Variables](./06-ENVIRONMENT-VARIABLES.md).

4. If you visit `/install` and `.env` is missing, `public_html/index.php` and `App\Http\Middleware\RedirectToInstaller` copy `.env.example` automatically.

---

## 5. Environment variables you must set first

Minimum to boot the site:

| Variable | Purpose | Example (placeholder) |
|---|---|---|
| `APP_KEY` | Laravel encryption key | output of `php artisan key:generate` |
| `APP_URL` | Base URL | `http://localhost` (value in `.env.example`) |
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | Database host | `127.0.0.1` |
| `DB_PORT` | Database port | `3306` |
| `DB_DATABASE` | Database name | `booking_core` (`.env.example` default) |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | empty or `YOUR_DB_PASSWORD` |

For ETG hotel search (the homepage):

| Variable | Purpose |
|---|---|
| `API_URL` | ETG base URL. Code default: `https://api.worldota.net/api/b2b/v3/` |
| `API_USERNAME` / `API_PASSWORD` | Fallback ETG Basic Auth |
| `API_USERNAME_B2B` / `API_PASSWORD_B2B` | Used when the logged-in user is classified as B2B |
| `API_USERNAME_B2C` / `API_PASSWORD_B2C` | Used for guests and B2C users |

These `API_*` keys are **not** in `.env.example`. They are read in `modules/Hotel/Controllers/HotelHController.php`.

For car search: `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER` (used in `modules/Car/Controllers/CarController.php`). Not in `.env.example`.

---

## 6. Database setup

1. Create an empty MySQL database matching `DB_DATABASE`.
2. Point the web server document root at `public_html/` (see [Installation](./04-INSTALLATION.md)).
3. Open `/install` in the browser. `RedirectToInstaller` sends every request there until `storage/installed` exists.
4. Complete the Booking Core installer wizard (requirements, environment, database).

Alternatively, after `.env` is valid:

```powershell
php artisan migrate
php artisan db:seed
```

`Database\Seeders\DatabaseSeeder` delegates to the active theme seeder when `Themes\{Theme}\Database\Seeders\DatabaseSeeder` exists. Otherwise it seeds roles, users, media, locations, and demo content for tour/space/hotel/car/event/flight/boat.

Hotel **RateHawk static data** (`hotels`, `hotel_images`) is **not** created by Laravel seeders. It is loaded by `hotels_data.py`, which also `CREATE TABLE IF NOT EXISTS` those tables. Meal types are loaded by `meal_data.py`. See [Python](./14-PYTHON.md) and [Database](./11-DATABASE.md).

---

## 7. Backend startup

This is a PHP Laravel app. There is no separate Node backend.

**Laragon / Apache:** set the site document root to `public_html/`. `public_html/index.php` binds Laravel's public path to that directory.

**PHP built-in server:** `server.php` in the repository root requires `public/index.php`. There is **no** `public/index.php` in this repository. The real front controller is `public_html/index.php`. Prefer Apache/Laragon, or:

```powershell
php artisan serve
```

Laravel's `artisan serve` uses the framework default (typically `http://127.0.0.1:8000`). This project does not override that port in source.

**Queues:** `.env.example` sets `QUEUE_CONNECTION=sync`. No worker is required unless you change that.

**Scheduler:** `app/Console/Kernel.php` schedules `user_plan:expired` daily. To run the scheduler you need a system cron/task that executes:

```powershell
php artisan schedule:run
```

---

## 8. Frontend startup

The UI is **Blade + jQuery/Vue 2 + Sass**, compiled with Laravel Mix into `public_html/dist/`. Compiled assets are already present under `public_html/dist/` and `public_html/libs/`.

You only need a watch/build when you change Sass/JS:

Admin (Vue 2 app, Mix output `public_html/dist/admin`):

```powershell
cd public_html\themes\admin
npm run watch
```

Production admin build:

```powershell
cd public_html\themes\admin
npm run prod
```

Frontend Sass (`public_html/themes/bc/webpack.mix.js` outputs to `public_html/dist/frontend`):

```powershell
cd public_html\themes\bc
npx mix
```

Root `package.json` scripts `admin-watch` / `admin-prod` run `mix` from the repository root. **No `webpack.mix.js` exists at the repository root**, so those root scripts are not usable as written.

Root `vite.config.js` points at `resources/css/app.css` and `resources/js/app.js`. Those files were **not found**. Vite is not the active frontend pipeline.

`public_html/themes/bc/webpack.mix.js` BrowserSync proxies `http://localhost:8000` and uses host `booking.test`. That is Mix config only; it is not an application runtime setting.

---

## 9. Python setup

Python is **not** required to run the website. It is used only for ETG static dumps.

There is no `pyproject.toml` or `requirements.txt`. A `.venv` directory exists in the tree; how it was created is not determinable from source.

```powershell
python -m venv .venv
.\.venv\Scripts\activate
pip install requests zstandard mysql-connector-python
```

Then see [Python](./14-PYTHON.md) for `hotels_data.py` and `meal_data.py`. Those scripts currently contain **hardcoded** API and database credentials in source. Replace them locally; do not copy real secrets into git or docs.

---

## 10. How to verify the application is running

1. Confirm `storage/installed` exists (otherwise you are redirected to `/install`).
2. Open `APP_URL` in a browser. `GET /` is handled by `HotelHController@showHotels` and renders `Hotel::frontend.form-search-ha` (theme view `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php`).
3. Admin: `{APP_URL}/{ADMIN_ROUTER_PREFIX}` (default `/admin`). Requires an authenticated user with dashboard access (`dashboard` middleware).
4. Optional health-style routes in `routes/web.php`:
   - `GET /pcb-test` — JSON describing PCB Bank configuration (does not expose certificate contents).
5. `GET /hotel/test-api-credentials` — JSON indicating whether ETG env vars are set and which B2B/B2C type the current user maps to. Intended as a test endpoint (comment in routes: “remove in production”).

---

## 11. URLs and ports used by the application

| What | Value | Source |
|---|---|---|
| Application URL | `APP_URL`, default `http://localhost` | `.env.example`, `config/app.php` |
| Database | host `127.0.0.1`, port `3306` | `.env.example` |
| Redis | `127.0.0.1:6379` | `.env.example` |
| Admin prefix | `admin` unless `ADMIN_ROUTER_PREFIX` is set | `modules/Dashboard/Config/config.php` |
| Hotel homepage | `/` | `modules/Hotel/Routes/web.php` |
| Hotel search | `/hotels/search` | same |
| Hotel suggestions | `/hotel-suggestions` | same |
| Car search | `/car` (prefix `CAR_ROUTER_PREFIX`) | `config/car.php` |
| Booking | `/booking` | `config/booking.php` |
| JSON API | `/api/...` | `modules/Api/Routes/api.php` |
| ETG API default | `https://api.worldota.net/api/b2b/v3/` | `HotelHController` |
| PCB Bank API default | `https://3dss2test.quipu.de:8000` | `config/pcb_bank.php` |
| PCB portal default | `https://3dss2test.quipu.de:8004/` | `config/pcb_bank.php` |
| Mix BrowserSync proxy | `http://localhost:8000` | `public_html/themes/bc/webpack.mix.js` |

Laragon virtual-host hostnames are **not** defined in this repository.

---

## 12. Common startup errors

| Symptom | Likely cause | Fix |
|---|---|---|
| “You must upgrade PHP version 8.0.2 and later” | PHP too old | Use PHP 8.1+ (`public_html/index.php`) |
| Redirected to `/install` | Missing `storage/installed` | Complete installer or create that file after a valid install |
| Database connection error | Wrong `DB_*` | Match MySQL credentials; installer also has `POST /install/check-db` |
| Blank hotel search / API errors | Missing `API_USERNAME*` / `API_PASSWORD*` | Set ETG credentials; check `GET /hotel/test-api-credentials` |
| Empty hotel results | `hotels` table not populated | Run `hotels_data.py` after configuring DB/API |
| `server.php` 404 | It includes `public/index.php`, which is absent | Use `public_html/` as document root |
| Mix fails at repo root | No root `webpack.mix.js` | Run Mix from `public_html/themes/admin` or `public_html/themes/bc` |
| PCB payments fail | Missing certs or env | Place PEM files at `storage/certs/` or set `PCB_BANK_*_PATH` |
| `not_allowed_host` from ETG | IPv6 egress not whitelisted | `HotelHController` already forces IPv4 via cURL; confirm the IPv4 is whitelisted with ETG |

More cases: [Troubleshooting](./18-TROUBLESHOOTING.md).
