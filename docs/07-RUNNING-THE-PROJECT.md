# Running the Project

Every command below exists in project files or is a standard Laravel Artisan command used by this Laravel 10 app. Working directory is the **repository root** unless stated.

Related: [Start Here](./00-START-HERE.md) · [Scripts](./15-SCRIPTS-AND-COMMANDS.md) · [Python](./14-PYTHON.md)

---

## Full application (development, no Docker)

Purpose:
Run the complete Laravel app (web + Blade + `/api`).

How:
Point Apache/Laragon at `public_html/`, or:

```powershell
php artisan serve
```

Working directory:

```
(repository root)
```

What it starts:
Laravel HTTP kernel via `artisan serve` (framework default bind `127.0.0.1:8000` unless you pass `--port`). Document root for assets remains `public_html` because of `path.public` binding in `public_html/index.php` when that entry is used. `artisan serve` uses Laravel’s default public path (`public/`), which **does not contain `index.php` in this repo**. Prefer Laragon/Apache with `public_html`.

There is **no** Docker Compose file. `laravel/sail` is installed as a Composer dev package only.

---

## Production mode

Purpose:
Serve the same PHP application behind Apache/Nginx with `APP_ENV=production` and `APP_DEBUG=false`.

Command:
No project-specific production start script. Typical:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Working directory: repository root.

What it starts:
Nothing extra; the web server continues to execute `public_html/index.php`. Deploy is FTP on git push ([Deployment](./17-DEPLOYMENT.md)).

---

## Frontend only (asset watch)

Purpose:
Rebuild admin Vue/Sass while developing the dashboard.

Command:

```powershell
npm run watch
```

Working directory:

```
public_html/themes/admin
```

(`public_html/themes/admin/package.json` script `watch`: `mix watch`)

What it starts:
Laravel Mix watcher writing to `public_html/dist/admin`.

Production frontend/admin CSS/JS:

```powershell
npm run prod
```

Working directory: `public_html/themes/admin` (`mix --production`).

Frontend theme Sass:

```powershell
npx mix
```

or Mix `--production`, working directory `public_html/themes/bc` (`webpack.mix.js` outputs `public_html/dist/frontend`). That Mix file also starts BrowserSync against `http://localhost:8000` / host `booking.test` when Mix is run in watch-compatible mode as configured there.

Root scripts (not usable without a root Mix file):

```powershell
npm run admin-watch
npm run admin-prod
```

Working directory: repository root. `package.json` runs `mix watch` / `mix --production`. **No `webpack.mix.js` at repo root.**

---

## Backend only

The backend **is** the Laravel app. There is no second process except optional queue/scheduler.

---

## Database migrations

Purpose:
Apply PHP migrations from `database/migrations`, `themes/Base/Database/Migrations`, and module `Migrations` folders registered with `loadMigrationsFrom`.

Command:

```powershell
php artisan migrate
```

Working directory: repository root.

Does **not** create ETG `hotels` / `hotel_images` (Python does).

Rollback (framework):

```powershell
php artisan migrate:rollback
```

---

## Database seeding

Purpose:
Demo/core rows (roles, users, locations, services) via `DatabaseSeeder`.

Command:

```powershell
php artisan db:seed
```

---

## Database initialization (installer)

Purpose:
First-time install including `storage/installed`.

Open:

```
{APP_URL}/install
```

---

## ETG hotel dump (Python)

See [Python](./14-PYTHON.md).

```powershell
python hotels_data.py
python meal_data.py
```

Working directory: repository root. Requires network, MySQL, and valid ETG credentials inside those files (currently hardcoded — replace locally).

---

## Queue worker

Purpose:
Process queued jobs.

`.env.example` uses `QUEUE_CONNECTION=sync` (jobs run during the request). If you set `database` or `redis`:

```powershell
php artisan queue:work
```

No custom worker configuration was found.

---

## Scheduled jobs

Purpose:
Run `user_plan:expired` daily (`app/Console/Kernel.php`).

Command (must be invoked by OS scheduler):

```powershell
php artisan schedule:run
```

Direct:

```powershell
php artisan user_plan:expired
```

---

## PCB utilities

```powershell
php artisan pcb:enable
php artisan pcb:status
php artisan pcb:test --amount=10.00 --description="Test Order"
php artisan pcb:test-simple
php artisan pcb:test-curl
```

Working directory: repository root. Requires PCB env/certs. `pcb:test` optional flags from `TestPcbBankCommand`.

---

## Other Artisan commands in this repo

```powershell
php artisan language:scan
php artisan inspire
```

`language:scan` → `app/Console/Commands/scanLanguage.php`.

---

## Gulp package build

Purpose:
Copy the project to `../builds/booking-core` (excludes `.env`, `node_modules`, `.git`, logs, etc.).

```powershell
npx gulp
```

or `npx gulp test` (same series). Zip:

```powershell
npx gulp zip
```

Requires gulp packages (`gulpfile.js` uses `gulp`, `gulp-zip`, `gulp-rename`, `gulp-clean`). Those are **not** listed in root `package.json` dependencies; running gulp may fail until those packages are installed. Documented as present in `gulpfile.js` only.

---

## Tests

```powershell
php artisan test
```

or

```powershell
vendor\bin\phpunit
```

See [Testing](./16-TESTING.md).

---

## Cache / maintenance (framework)

```powershell
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan down
php artisan up
```

Web shortcut: `GET /tools/clear-cache` → `Modules\Core\Controllers\ToolsController@clearCache` (`modules/Core/Routes/web.php`).
