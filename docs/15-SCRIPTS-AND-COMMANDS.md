# Scripts and Commands

Commands actually declared in this repository. Laravel’s global `artisan` list is larger (`migrate`, `tinker`, …); those are framework commands available because this is Laravel 10.

Related: [Running](./07-RUNNING-THE-PROJECT.md) · [Testing](./16-TESTING.md) · [Python](./14-PYTHON.md)

---

## Composer (`composer.json`)

| Command | Where | Purpose | Result |
|---|---|---|---|
| `composer install` | repo root | Install PHP deps | `vendor/` |
| `composer update` | repo root | Update deps + publish laravel-assets | lockfile + published assets |
| `php artisan package:discover` | post-autoload-dump | Discover packages | cached packages |
| copy `.env.example` → `.env` | post-root-package-install | If `.env` missing | new env file |
| `php artisan key:generate` | post-create-project-cmd | APP_KEY | updated `.env` |

---

## npm (`package.json` root)

| Command | Where | Purpose | Prerequisites |
|---|---|---|---|
| `npm install` | repo root | JS deps for Mix/Vue | Node |
| `npm run admin-watch` | repo root | `mix watch` | **Needs `webpack.mix.js` at root (missing)** |
| `npm run admin-prod` | repo root | `mix --production` | same |

Lockfiles: `package-lock.json`, `yarn.lock`.

---

## npm (`public_html/themes/admin/package.json`)

| Command | Where | Purpose |
|---|---|---|
| `npm run watch` | `public_html/themes/admin` | Mix watch → `public_html/dist/admin` |
| `npm run prod` | same | Production Mix |

---

## npm (`public_html/themes/bc/package.json`)

Present (`public_html/themes/bc/package.json`). Scripts are Mix-based (file exists). Run Mix from that directory so `webpack.mix.js` resolves. BrowserSync proxy `http://localhost:8000`, host `booking.test`.

---

## Artisan — project commands

| Command | File | Purpose | Prerequisites |
|---|---|---|---|
| `php artisan pcb:enable` | `EnablePcbBankCommand` | Sets `core_settings` `g_pcb_bank_enable` | DB |
| `php artisan pcb:status` | `CheckPcbBankStatusCommand` | Check PCB config/status | PCB env/certs |
| `php artisan pcb:test` | `TestPcbBankCommand` | Create test order; `--amount` `--description` | PCB |
| `php artisan pcb:test-simple` | `TestPcbBankSimpleCommand` | Simpler PCB test | PCB |
| `php artisan pcb:test-curl` | `TestPcbBankCurlCommand` | cURL-based PCB test | PCB |
| `php artisan user_plan:expired` | `ScanUserPlanExpiredCommand` | Deactivate extra vendor services when plan capacity exceeded | DB; scheduled daily |
| `php artisan language:scan` | `scanLanguage.php` | Scan language strings | codebase |
| `php artisan inspire` | `routes/console.php` | Quote | none |

---

## Artisan — framework (used in this project’s docs/flow)

| Command | Purpose |
|---|---|
| `php artisan serve` | Built-in server (public path mismatch; see [Running](./07-RUNNING-THE-PROJECT.md)) |
| `php artisan migrate` | Run migrations |
| `php artisan db:seed` | Seed |
| `php artisan key:generate` | APP_KEY |
| `php artisan schedule:run` | Run due scheduled tasks |
| `php artisan queue:work` | Worker if queue ≠ sync |
| `php artisan cache:clear` | Clear cache |
| `php artisan test` | PHPUnit via Artisan |
| `php artisan storage:link` | Public storage symlink |

---

## Python

| Command | Where | Purpose |
|---|---|---|
| `python hotels_data.py` | repo root | ETG hotel dump → MySQL |
| `python meal_data.py` | repo root | ETG meals → `meal_types` |

---

## Gulp (`gulpfile.js`)

| Command | Purpose | Prerequisites |
|---|---|---|
| `npx gulp` / `npx gulp test` | Clean `../builds/booking-core`, copy project (excludes `.env`, node_modules, git, logs, caches) | gulp plugins; parent `builds` dir |
| `npx gulp zip` | Zip `../builds/booking-core` to `booking-core.zip` | copy already done |

`makeEnv` copies `.env.example` to the build as `.env` but is **not** in the exported `default` series.

---

## PHP one-off

`test.php` at repo root copies theme view folders. Run only if you intend that filesystem copy:

```powershell
php test.php
```

Not part of normal startup.

---

## CI

GitHub Actions (`.github/workflows/*.yml`) run **FTP deploy** on push; they do not run test or build scripts. See [Deployment](./17-DEPLOYMENT.md).
