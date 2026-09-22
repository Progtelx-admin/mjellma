# Build and Development Tools

## Overview

PHP via Composer/Artisan; frontend assets via **Laravel Mix** in theme directories. Vite config at repo root is not the active UI pipeline.

## Composer Scripts

Defined in `composer.json`:

- `post-autoload-dump` — package discover
- `post-root-package-install` — copy `.env.example` → `.env` if missing
- `post-create-project-cmd` — `key:generate`

Common commands:

```powershell
composer install
composer update
php artisan migrate
php artisan db:seed
php artisan cache:clear
php artisan config:clear
php artisan route:list
php artisan schedule:run
```

## npm / Mix

Root `package.json` scripts:

- `admin-watch` → `mix watch`
- `admin-prod` → `mix --production`

These expect a Mix file at the cwd. **Use theme directories instead:**

### Admin (Vue 2)

```powershell
cd public_html\themes\admin
npm install
npm run watch
npm run prod
```

Output: `public_html/dist/admin`.

### Frontend Sass

```powershell
cd public_html\themes\bc
npm install
npx mix
npx mix watch
npx mix --production
```

Output: `public_html/dist/frontend`.

BrowserSync in BC Mix may proxy a local host—that is Mix-only config.

## Dev PHP Packages

Debugbar, Pint, Sail, Collision, Ignition, Mockery, Faker (see `composer.json` `require-dev`).

## Useful Artisan Domain Commands

- Plan expiry scan
- PCB diagnostic/enable commands
- Language scan

## Python Tooling

```powershell
python -m venv .venv
.\.venv\Scripts\activate
pip install requests zstandard mysql-connector-python
python hotels_data.py
python meal_data.py
```

No `requirements.txt` is committed—install inferred packages manually.

## Related Documentation

- [Installation](./05-installation-and-setup.md)
- [Frontend](./09-frontend.md)
