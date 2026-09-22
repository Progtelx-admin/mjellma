# Troubleshooting

## Overview

Common failure modes for this repository’s actual setup.

## Application Does Not Start

| Symptom | Likely cause | What to check |
| --- | --- | --- |
| PHP version error | PHP ≤ 8.0.2 | Upgrade to 8.1+ |
| Redirect loop to `/install` | Missing `storage/installed` | Complete installer or create flag after valid setup |
| 500 on boot | Missing `APP_KEY` / bad `.env` | `php artisan key:generate`, fix env |
| Blank page | Debug off + exception | `storage/logs`, temporarily inspect with safe local debug |

## Document Root Issues

| Symptom | Cause | Fix |
| --- | --- | --- |
| Assets 404 / wrong app | Docroot not `public_html` | Point vhost to `public_html` |
| `server.php` broken | Expects `public/index.php` | Use Apache/Laragon or `artisan serve` |

## Database

| Symptom | Cause | Fix |
| --- | --- | --- |
| Connection refused | Wrong `DB_*` | Match MySQL; installer `POST /install/check-db` |
| Migration errors | Partial schema | Backup; migrate carefully; check module/theme migrations loaded |

## Frontend Cannot Reach Backend / Search Empty

| Symptom | Cause | Fix |
| --- | --- | --- |
| Empty hotel results | Empty `hotels` table | Run `hotels_data.py` with local config |
| API errors on rates | Missing `API_*` env | Set credentials; use diagnostics endpoint carefully |
| `not_allowed_host` from ETG | Egress IP not allowlisted | Allowlist server IPv4; code already forces IPv4 |
| Car search fails | Missing `CAR_API_*` | Set base URL/token/referer |

## Asset Build Failures

| Symptom | Cause | Fix |
| --- | --- | --- |
| Root `npm run admin-watch` fails | No root `webpack.mix.js` | Build inside `public_html/themes/admin` or `bc` |
| Missing `node_modules` | Deps not installed | `npm install` in theme folder |

## Authentication Issues

| Symptom | Cause | Fix |
| --- | --- | --- |
| Cannot login | User not `publish` | Check user status |
| API 401 | Missing/invalid Sanctum token | Re-login; send Bearer token |
| Admin 403 | Missing dashboard permission | Assign role permissions |

## PCB Payments

| Symptom | Cause | Fix |
| --- | --- | --- |
| `Certificates not configured` | Missing PEMs / paths | Place certs; set `PCB_BANK_*_PATH` |
| Return URL fails | Wrong `APP_URL` / routes | Align public URL with PCB return |
| Order not FullyPaid | Pending/declined status | Inspect PCB status safely; check logs without dumping secrets |

## Queue / Mail

| Symptom | Cause | Fix |
| --- | --- | --- |
| Mail “not sending” with database queue | No worker | Run `queue:work` or use `sync` |
| Scheduler idle | No cron | Add `schedule:run` every minute |

## Stale Config

```powershell
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

## Related Documentation

- [Installation](./05-installation-and-setup.md)
- [Integrations](./17-integrations.md)
- [Error Handling](./20-error-handling-and-logging.md)
