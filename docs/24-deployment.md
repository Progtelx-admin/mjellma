# Deployment

## Overview

Deployment guidance derived only from repository structure. Use placeholders for hosts and secrets. Do not store production credentials in git.

## Preconditions

- PHP 8.1+ with required extensions
- MySQL
- Web server document root → `public_html/`
- Writable `storage/`, `bootstrap/cache/`
- Composer dependencies installed with `--no-dev` for production when appropriate

## Build Steps (conceptual)

```powershell
composer install --no-dev --optimize-autoloader
copy .env.example .env   # then edit with production placeholders locally
php artisan key:generate
# set APP_ENV=production, APP_DEBUG=false, APP_URL=<application-url>
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Rebuild assets if sources changed:

```powershell
cd public_html\themes\admin
npm ci
npm run prod
cd ..\bc
npm ci
npx mix --production
```

## Configuration Requirements

| Area | Requirement |
| --- | --- |
| App | `APP_KEY`, `APP_URL`, `APP_DEBUG=false` |
| DB | Production `DB_*` |
| ETG | `API_*` credential pairs |
| Car | `CAR_API_*` |
| PCB | Merchant, URLs, cert files on disk |
| Mail | Production mailer settings |
| HTTPS | Terminate TLS; consider `APP_HTTPS` |

Mark installer complete (`storage/installed`) so traffic is not redirected to `/install`.

## Workers & Scheduler

- If `QUEUE_CONNECTION` is not `sync`, run `queue:work` under a process supervisor
- Cron: `* * * * * php /path/to/artisan schedule:run`

## Static Assets

Serve `public_html/dist`, `libs`, `uploads` efficiently; deny web access to `.env`, `storage` (except public links), and certs.

## Post-deploy Checks

1. Homepage hotel search loads
2. Admin login works
3. PCB certs readable by PHP user
4. ETG connectivity from server egress IP (IPv4 allowlisted)
5. Disable or protect `/hotel/test-api-credentials` and `/pcb-test*` routes

## Related Documentation

- [Security](./25-security.md)
- [Troubleshooting](./26-troubleshooting.md)
- [Configuration](./06-configuration.md)
