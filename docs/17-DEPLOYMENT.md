# Deployment

What exists in this repository. No Kubernetes, Terraform, or Dockerfile was found.

Related: [Installation](./04-INSTALLATION.md) · [Running](./07-RUNNING-THE-PROJECT.md)

---

## GitHub Actions FTP

Two workflows:

### `.github/workflows/main.yml`

- Trigger: push to branch `main`
- Job: checkout, then `SamKirkland/FTP-Deploy-Action@v4.3.4`
- Credentials: GitHub secrets `ftp_uresname` and `ftp_password` (names as in the YAML)
- `server-dir`: `/public_html/ratehawk.laratest-app.com/`
- Server hostname is in the workflow file; treat it as infrastructure and rotate FTP secrets if exposed

### `.github/workflows/deploy-master.yml`

- Trigger: push to branch `master`
- Same action
- Secrets: `ftp_uresname_real`, `ftp_password_real`
- `server-dir`: `/public_html/`

These deploys **sync git files via FTP**. They do not run `composer install`, Mix, or migrations. The remote must already have `vendor/`, valid `.env`, `storage/installed`, and PHP 8.1.

---

## Web root on the server

Remote `server-dir` values indicate the host uses a `public_html` layout. This project’s front controller is already `public_html/index.php`. If FTP uploads the **entire repo** into `public_html/`, the remote layout must match whatever the host expects (repo-with-nested-public_html vs contents-of-public_html). The workflow does not document that mapping beyond `server-dir`. Confirm on the server rather than assuming.

---

## Production PHP checklist (from source behavior)

1. `APP_ENV=production`, `APP_DEBUG=false`
2. Valid `APP_KEY`
3. `APP_URL` = public HTTPS URL; `APP_HTTPS=true` if terminating SSL
4. MySQL `DB_*`
5. `API_*` ETG credentials (B2B and B2C)
6. `CAR_API_*` if cars are live
7. PCB certs + `PCB_BANK_*`
8. Writable `storage/` and `bootstrap/cache/`
9. `storage/installed` present
10. Scheduler: cron `php artisan schedule:run` if vendor plans are used
11. Queue worker only if `QUEUE_CONNECTION` is not `sync`

Optional:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Gulp “builds” package

`gulpfile.js` copies the project to `../builds/booking-core` excluding `.env`, git, node_modules, logs. Use that only if you still maintain that zip workflow. It is not referenced by GitHub Actions.

---

## Laravel Sail / Docker

`laravel/sail` is in `require-dev`. **No** `docker-compose.yml` is in the repo. Sail is not a documented deploy path here.

---

## Updater

Admin updater talks to `http://check.bookingcore.co/updater.php` (`config/app.php`). License/update UI: `Modules\Core\Admin\UpdaterController`. This is Booking Core’s commercial updater, not GitHub Actions.

---

## SSL / certificates

PCB Bank requires client certificates (PEM) on the application server, default `storage/certs/`. These must be deployed **outside** git if they are secrets.
