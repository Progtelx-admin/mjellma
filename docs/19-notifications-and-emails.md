# Notifications and Emails

## Overview

Communications use Laravel mail, notification classes, SMS module listeners, and optional Chatify/Pusher. Do not document real recipient addresses or SMTP passwords.

## Email

| Area | Implementation |
| --- | --- |
| Mail config | `config/mail.php`, `MAIL_*` env |
| Booking emails | `modules/Booking/Emails/*` (e.g. new booking) |
| User emails | verify, forgot password, registration mails under User module |
| Vendor emails | payout / approval style mails |
| Module | `modules/Email` settings class for admin email settings |

Triggers commonly ride on model events / listeners (`BookingCreated`, user registered, vendor approved, Mjellma booking created).

## SMS

`modules/Sms` listens to booking created/updated events when SMS is configured.

## In-app / Realtime

- Chatify package (`config/chatify.php`)
- `PusherNotificationAdminEvent` / `PusherNotificationPrivateEvent` in `app/Events`

## Queue Behavior

With `QUEUE_CONNECTION=sync`, mails send during the request. Switching to async queues delays sending until a worker processes jobs (if notifications implement `ShouldQueue`).

## Templates

Blade email views live under module `Views/emails` trees (e.g. User forgot/verify templates).

## Failure Handling

Failed SMTP appears in Laravel logs (`storage/logs`). Configure `LOG_CHANNEL` (default daily). No custom retry policy beyond Laravel/queue defaults was identified as a first-class hotel-specific layer.

## Related Documentation

- [Background Jobs](./18-background-jobs-and-automation.md)
- [Configuration](./06-configuration.md)
- [Error Handling](./20-error-handling-and-logging.md)
