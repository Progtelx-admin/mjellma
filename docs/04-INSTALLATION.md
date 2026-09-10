# Installation

Follow this for a new clone. Commands are taken from Composer scripts, Artisan, npm package files, and the installer config. Do not invent extra tooling.

Related: [Start Here](./00-START-HERE.md) · [Configuration](./05-CONFIGURATION.md) · [Running](./07-RUNNING-THE-PROJECT.md)

---

## Prerequisites

- PHP **8.1+** (`composer.json`: `"php": "^8.1.0"`)
- Extensions: openssl, pdo, mbstring, tokenizer, JSON, cURL, fileinfo (`config/installer.php`)
- Apache `mod_rewrite` (installer apache requirements) **or** another server that routes all requests to `public_html/index.php`
- Composer
- MySQL
- Node.js + npm if you will rebuild assets
- Write access to `storage/` and `bootstrap/cache/`

---

## 1. Install PHP dependencies

From the repository root:

```powershell
composer install
```

Composer scripts (`composer.json`):

- `post-autoload-dump` — `php artisan package:discover --ansi`
- `post-root-package-install` — copy `.env.example` → `.env` if missing
- `post-create-project-cmd` — `php artisan key:generate --ansi`
- `post-update-cmd` — `php artisan vendor:publish --tag=laravel-assets --ansi`

---

## 2. Environment file

```powershell
copy .env.example .env
php artisan key:generate
```

Set at least `APP_URL`, `DB_*`. Add `API_*` for hotels and `CAR_API_*` for cars. See [Environment Variables](./06-ENVIRONMENT-VARIABLES.md).

Never commit real passwords or API keys.

---

## 3. Web server document root

Point the vhost document root to **`public_html`**, not the repository root.

`public_html/index.php` contains:

```php
$app->bind('path.public', function () {
    return __DIR__;
});
```

`server.php` (repo root) requires `public/index.php`, which is **not** present. Do not rely on `php -S` + `server.php` unless you add that file yourself (out of scope of this documentation).

### Laragon

Create a site whose root is this project’s `public_html` folder, or map the existing `www/mjellma` site so Apache uses `public_html` as the public directory. The hostname is a Laragon setting, not defined in this repo. Set `APP_URL` to that hostname.

---

## 4. Web installer (first boot)

Until `storage/installed` exists, `App\Http\Middleware\RedirectToInstaller` redirects all non-install requests to `/install`.

1. Create an empty MySQL database.
2. Open `{APP_URL}/install`.
3. Complete requirements, environment, and database steps (package `rachidlaasri/laravel-installer`).
4. Successful install creates `storage/installed`.

`POST /install/check-db` is handled by `HomeController@checkConnectDatabase` (`routes/web.php`).

`/update` and `/update/*` are redirected home (`InstallerController@redirectToHome`).

---

## 5. Database via Artisan (alternative)

If you skip the wizard after configuring `.env`:

```powershell
php artisan migrate
php artisan db:seed
```

`php artisan db:seed` runs `Database\Seeders\DatabaseSeeder`, which prefers `Themes\{Active}\Database\Seeders\DatabaseSeeder` when that class exists.

This does **not** fill ETG `hotels` / `hotel_images`. Use `hotels_data.py` ([Python](./14-PYTHON.md)).

---

## 6. Storage link

Laravel convention (framework command, not customized here):

```powershell
php artisan storage:link
```

Uploads also use `public_html/uploads` (`config/filesystems.php` disk `uploads`).

---

## 7. JavaScript dependencies (optional for first run)

Compiled files already live under `public_html/dist/` and `public_html/libs/`. To develop assets:

```powershell
npm install
cd public_html\themes\admin
npm install
cd ..\bc
npm install
```

See [Frontend](./08-FRONTEND.md) and [Scripts](./15-SCRIPTS-AND-COMMANDS.md).

---

## 8. PCB Bank certificates (optional)

Default paths in `config/pcb_bank.php`:

- `storage/certs/cert.pem`
- `storage/certs/key.pem`
- `storage/certs/ca.pem`

Override with `PCB_BANK_CERT_PATH`, `PCB_BANK_KEY_PATH`, `PCB_BANK_CA_PATH`.

Enable the gateway in settings or:

```powershell
php artisan pcb:enable
```

(`EnablePcbBankCommand` writes `core_settings.g_pcb_bank_enable`.)

---

## 9. Permissions (non-Windows)

The installer lists writable folders. On Linux, `storage/` and `bootstrap/cache/` must be writable by the web user. On Windows/Laragon this is usually already true.

---

## 10. Verify

- `{APP_URL}/` shows the hotel search form.
- `{APP_URL}/admin` after logging in as a user with dashboard permission.

Default seeded admin credentials are **not** documented here. Inspect `database/seeders/UsersTableSeeder.php` locally if you seed demo users; do not copy passwords into shared docs.
