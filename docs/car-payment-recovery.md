# Car payment rollout and recovery

Deploy the migration, model, command, configuration, controllers and admin view together.
Keep the existing APP_KEY unchanged: pending booking data and bank passwords are encrypted with it.

Before deploying, set the full dashboard Authorization header value in the server environment:

```dotenv
CAR_RENT_DASHBOARD_API_BASE=https://dev.rentacar-orange.com/api/agency
CAR_RENT_DASHBOARD_API_TOKEN="Bearer <provider-token>"
```

The old dashboard address is retained only as a default. It currently returns 404.
Obtain the current dashboard base URL and token from the provider. Do not substitute the
car availability endpoint: it returns cars, not customer reservations.
The previously hardcoded dashboard token must be supplied in the environment at deployment.

Run on the deployment host, before accepting new payments:

```sh
php artisan migrate --path=database/migrations/2026_10_19_000001_create_car_payment_orders_table.php --force
php artisan config:cache
```

New attempts are persisted in `car_payment_orders` before contacting the bank.
The browser token is stored as a hash. Customer data, provider responses and bank passwords
are encrypted. The callback can recover data without the original browser session.
The dashboard lists local payment orders separately from external reservations.

`paid` describes bank verification, not reservation confirmation. `creating` is an atomic
claim before the external POST. Only one callback can claim a pending order. Failed,
ambiguous or interrupted submissions require review; they are never automatically retried.
An HTTP success must also contain `success: true` or a reservation identifier (`id`,
`reservation_id`, `reservation.id`, `data.id`, or `data.reservation_id`), without an explicit
failure or errors. Other responses require review. Obtain its exact success/ID contract before rollout
and validate it in a provider-approved test environment.

To query an existing persisted payment (does not charge or submit a reservation):

```sh
php artisan car:verify-payment BANK_ORDER_ID
```

If the customer never returns from the bank, use this command to verify the recorded order.
There is no automatic webhook or scheduled reconciliation in this change.

For `review_required`, or a `creating` order left after an interrupted process:

1. Verify the bank order, amount and currency.
2. Ask the car provider whether a reservation exists for that bank order and rental details.
3. If it exists, reconcile the local status with its confirmed reservation ID.
4. If it does not exist, arrange manual completion or refund through the provider/bank.
   Do not repeat the POST until absence is confirmed, and do not ask the customer to pay again.

No automatic resubmission or refund is implemented because the provider's idempotency and
lookup contract are not documented. Pre-deployment payments are not imported: their bank
credentials/customer data may exist only in old sessions. Reconcile them manually.

The bank service still uses its existing TLS configuration; this change does not supply missing
certificates. Use bank-issued test credentials/certificates for testing. Do not make live payments
to validate deployment. Preserve backups and restrict access to the database and application logs.
