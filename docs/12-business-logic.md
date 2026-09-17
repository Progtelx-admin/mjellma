# Business Logic

## Overview

This section describes behavioral rules encoded in controllers, models, and gateways—especially the customized hotel and car flows.

## Hotel (ETG) Business Rules

### Discovery

1. Homepage lists/search uses local `hotels` rows for catalog data.
2. Live prices/availability come from ETG region/hotel SERP endpoints.
3. Suggestions combine local DB and ETG suggestion APIs.
4. Detail page (`/hotel/{id}`) merges DB hotel content with live rates (`book_hash` based).

### Credential selection

B2B vs B2C supplier accounts are chosen from the authenticated user’s type/role (see [Authentication](./10-authentication-and-authorization.md)). Wrong credentials cause empty rates or API errors—not Laravel 403s.

### Booking lifecycle

```text
Select rate (book_hash)
  → Prebook (ETG hold / price check)
  → Collect guest + payment choice
  → If PCB / pay-now card: create PCB order → HPP → return
  → Persist mjellma booking / invoice / payment meta
  → ETG hotel/order/booking/finish (+ status polling)
  → Confirmation / invoice views
```

### Constraints observed in code

- Hotel pay-now path emphasizes PCB Bank; unsupported non-PCB “now” paths are rejected where deposit finish is commented out.
- Partner order ids and `book_hash` correlate UI confirmation routes.
- Cancellation available for admin/partner order flows via cancel endpoints.

### Events

Creating an ETG booking dispatches `MjellmaBookingCreatedEvent` for notifications.

## Car Rental Business Rules

1. Search/filter metadata (locations, manufacturers, categories, transmissions) loaded from car API and cached ~1 hour.
2. Reservation detail fetched remotely.
3. Checkout session posts to partner `checkout` / `store-checkout`.
4. Payment: `pcb_bank` or `cash`.
5. On PCB success, booking completion + email side effects run in controller flow.

## Classic Booking Core Services

Tours, spaces, events, flights, boats, and legacy hotels follow Booking Core patterns:

- Vendor CRUD + availability calendars
- Cart → checkout → gateway confirm/cancel
- Coupons, deposits, enquiries, reviews
- Status transitions on `bravo_bookings` (draft/unpaid/processing/confirmed/cancelled/etc. as defined by Booking module)

## Payments

Gateway map (`config/payment.php`):

- offline, paypal, stripe, payrexx, paystack, pcb_bank

PCB success judged against configurable status lists (`fullypaid`, `paid`, …). Refund/reverse methods exist on `PcbBankService` for operational use.

## Users, Vendors, Wallets, Plans

- Vendors manage listings under `user/{service_prefix}` routes
- Wallet / member plan features exist via traits and Vendor/User modules
- Scheduler expires user plans daily (`ScanUserPlanExpiredCommand`)

## Offers

Promotional sections/cards with sort order and active flags; shown on hotel search UI and dedicated pages.

## Permissions

Ability checks via role permissions for admin menus, CRUD, logs, translation manager, etc. Always align new features with `PermissionHelper` registrations.

## Related Documentation

- [Features](./13-features.md)
- [Workflows](./14-workflows.md)
- [Integrations](./17-integrations.md)
