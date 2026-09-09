# Troubleshooting

Practical issues tied to this codebase. Related: [Start Here](./00-START-HERE.md) · [Environment Variables](./06-ENVIRONMENT-VARIABLES.md)

---

## Dependencies not installed

**Symptom:** `Class "Illuminate\..." not found` or blank 500.

**Cause:** `vendor/` missing or incomplete.

**Diagnose:** `vendor/autoload.php` exists? Run `composer install` from repo root.

**Fix:** `composer install`. Ensure PHP 8.1 CLI matches the web SAPI.

---

## Wrong PHP version

**Symptom:** Page text “You must upgrade PHP version 8.0.2 and later”.

**Cause:** `public_html/index.php` version_compare.

**Fix:** Switch Laragon/Apache to PHP 8.1+. Composer also requires `^8.1.0`.

---

## Wrong Node version / Mix fails

**Symptom:** `mix: not found` or webpack errors.

**Cause:** Node not installed, or Mix run from repo root without `webpack.mix.js`.

**Diagnose:** `npm run admin-watch` at root. Check for `webpack.mix.js` in cwd.

**Fix:** `cd public_html\themes\admin` then `npm install` and `npm run watch`. Node version is not pinned in the repo.

---

## Missing environment variables

**Symptom:** Hotel search empty/errors; car search fails; PCB `isConfigured()` false.

**Diagnose:**

- `GET /hotel/test-api-credentials` — shows which `API_*` are set (not the secrets).
- `GET /pcb-test` — cert paths and existence flags.
- `GET /car/api/debug` — dumps whether `CAR_API_BASE` is set (sensitive; restrict in production).

**Fix:** Add variables from [Environment Variables](./06-ENVIRONMENT-VARIABLES.md). `.env.example` is incomplete for ETG/cars/PCB.

---

## Redirected to `/install`

**Symptom:** Cannot open `/`.

**Cause:** `storage/installed` missing (`RedirectToInstaller`).

**Fix:** Finish installer or restore the `installed` file after a valid DB. Do not create it on an empty database unless you know the schema exists.

---

## Backend / site not starting

**Symptom:** 500, empty response.

**Cause:** Missing `APP_KEY`, syntax error, unreadable `.env`, exception in `AppServiceProvider` when `is_installed()` and DB is down (`initConfigFromDB`).

**Diagnose:** `storage/logs/laravel-*.log`; temporarily `APP_DEBUG=true` locally. Admin logs: `/{admin}/logs` if you can log in.

**Fix:** `php artisan key:generate`; fix DB; `php artisan config:clear`.

---

## `php artisan serve` / `server.php` broken

**Symptom:** 404 or “No input file specified”.

**Cause:** `server.php` requires `public/index.php` (absent). Artisan serve uses Laravel `public/` path.

**Fix:** Use Apache/Laragon with document root `public_html`.

---

## Database connection errors

**Symptom:** SQLSTATE[HY000] [1045] or [2002].

**Cause:** Wrong `DB_*`; MySQL not running.

**Diagnose:** `POST /install/check-db` during install; `php artisan migrate` locally.

**Fix:** Match MySQL user/password/host. Default example DB name is `booking_core`, not necessarily `mjellma`.

---

## Port already in use

**Symptom:** `artisan serve` address in use.

**Cause:** Something bound to 8000 (Mix BrowserSync also mentions 8000).

**Fix:** `php artisan serve --port=8001` (framework flag) or stop the other process. Application has no custom port config.

---

## ETG API failures / `not_allowed_host`

**Symptom:** Suggestions `{error:true}`; no prices; logs “ETG region search failed” / “ETG multicomplete rejected”.

**Cause:** Bad Basic Auth; IPv6 not allowlisted; timeout; empty `hotels` table.

**Diagnose:** Logs in `HotelHController`; confirm IPv4 (`httpOptions` already forces v4); confirm dump imported.

**Fix:** Correct `API_USERNAME_*` / `API_PASSWORD_*`; whitelist server IPv4 with ETG; run `hotels_data.py`.

---

## Empty hotel results for a city

**Symptom:** Form submits, zero hotels.

**Cause:** `getHotelIdsForRegion` returned [] (API fail) then `whereRaw('1 = 0')`; or dump lacks those IDs.

**Diagnose:** Cache key `etg_region_hotel_ids_{regionId}_{b2b|b2c}`; Laravel log.

**Fix:** Fix ETG auth; wait 15 minutes or `php artisan cache:clear`; re-import dump.

---

## Python import / venv problems

**Symptom:** `ModuleNotFoundError: requests` / `zstandard` / `mysql.connector`.

**Cause:** No deps; wrong interpreter.

**Fix:** Activate `.venv`, `pip install requests zstandard mysql-connector-python`. There is no project `requirements.txt`.

**Symptom:** Access denied for MySQL user in script.

**Cause:** Hardcoded DB user in the `.py` file differs from Laravel `.env`.

**Fix:** Edit the script constants locally (do not commit secrets).

---

## CORS

**Symptom:** Browser blocks `/api` from another origin.

**Cause:** `config/cors.php` allows `*` on `api/*` but `supports_credentials` is false.

**Fix:** Adjust CORS if you need cookies; Sanctum SPA needs `SANCTUM_STATEFUL_DOMAINS`.

---

## CSRF 419

**Symptom:** POST from Blade fails.

**Cause:** Missing `@csrf` or expired session.

**Fix:** Ensure forms include CSRF; session driver writable (`storage/framework/sessions` if file driver).

---

## PCB payment failures

**Symptom:** `Certificates not configured`; SSL errors.

**Cause:** Missing PEM files; wrong `PCB_BANK_API_URL`.

**Diagnose:** `/pcb-test` JSON `cert_exists` / `key_exists` / `ca_exists`. `php artisan pcb:status`.

**Fix:** Install certs; `php artisan pcb:enable` to turn on gateway setting.

---

## Build errors (Mix)

**Symptom:** Sass/Vue compile fail.

**Cause:** Missing `npm install` in the theme folder; Vue 2 loader versions.

**Fix:** Use the theme `package.json` (`vue-loader` 15.x, `vue-template-compiler` 2.7.x). Do not use Vite until `resources/js/app.js` exists.

---

## Docker

**Symptom:** Looking for compose file.

**Cause:** None in repo.

**Fix:** Use Laragon/native PHP. Sail is not configured here.

---

## Admin 403 / redirect home

**Cause:** `dashboard` middleware; user lacks permission or `status` not `publish`.

**Fix:** Use a seeded admin or grant `dashboard_access` via User admin roles.
