# Background Jobs and Automation

## Overview

Default queue connection is **`sync`** (`.env.example`), so queued work runs inline in the HTTP/process that dispatches it unless you change the driver.

## Jobs

No domain `Job` classes were found under `app/` or `modules/`. A `jobs` database table migration may exist for Laravel queue infrastructure, but application logic primarily uses listeners and synchronous controller flows.

## Events & Listeners

Examples:

| Event area | Behavior |
| --- | --- |
| Mjellma booking created | Notification listener |
| Booking created/updated/paid | Emails; SMS module listeners |
| User registered / vendor approved | Notification emails |
| Enquiry send | Booking enquiry listener |
| Pusher notification events | Admin/private channels |

Registration: `app/Providers/EventServiceProvider` and module providers (Hotel may register Mjellma listener in addition to App provider).

## Scheduler

`app/Console/Kernel.php`:

- Daily: `ScanUserPlanExpiredCommand` (`user_plan:expired`) with `withoutOverlapping()`

System cron / Task Scheduler should call:

```powershell
php artisan schedule:run
```

## Artisan Commands

Notable commands under `app/Console/Commands`:

- Plan expiry scan
- PCB Bank test / curl / simple / status / enable helpers
- `scanLanguage`

## When Enabling Async Queues

1. Set `QUEUE_CONNECTION` to `database` or `redis`
2. Run migrations for jobs table if needed
3. Run `php artisan queue:work`
4. Confirm mail/SMS listeners still behave under workers

## Related Documentation

- [Notifications & Emails](./19-notifications-and-emails.md)
- [Deployment](./24-deployment.md)
