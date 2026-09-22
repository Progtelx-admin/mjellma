# Features

## Overview

Significant product features and where they live in the codebase.

---

## 1. ETG Hotel Search & Booking

| Aspect | Detail |
| --- | --- |
| Purpose | Primary public hotel product |
| Access | Public; B2B/B2C affects supplier credentials |
| Frontend | `themes/BC/Hotel/Views/frontend/*-ha.blade.php` |
| Backend | `HotelHController` |
| Database | `hotels`, `hotel_images`, `mjellma_bookings`, invoices |
| API | ETG B2B + web routes |
| Validation | Request validation in controller actions |
| AuthZ | Public browse; admin cancel/history protected |
| Key files | `modules/Hotel/Controllers/HotelHController.php`, `Models/HotelH.php`, `MjellmaBooking.php` |

Edge cases: empty catalog if Python dump not run; ETG IP allowlisting; missing env credentials.

---

## 2. Car Rental (External API)

| Aspect | Detail |
| --- | --- |
| Purpose | Search/book cars via partner API |
| Access | Public search; checkout as implemented |
| Frontend | BC Car API views |
| Backend | `CarController` |
| Database | May still use Bravo car tables for legacy/vendor; live search is API |
| Env | `CAR_API_BASE`, `CAR_API_TOKEN`, `CAR_API_REFERER` |
| Payments | PCB or cash |

---

## 3. Classic Bookable Services

Tour, Space, Event, Flight, Boat (+ legacy Hotel vendor tools): availability, vendor manage, cart checkout via Booking module.

---

## 4. Payments & Invoices

- Gateways in `config/payment.php`
- PCB Bank primary for HA hotel pay-now and car card flow
- `InvoiceService` + `invoices` table
- Plugin: TwoCheckout under `plugins/PaymentTwoCheckout`

---

## 5. User Accounts, Vendors, Wallets

Registration (when enabled), profile, wishlist, booking history, vendor dashboards, wallet/plans.

---

## 6. Admin CMS

Pages, news, templates (Vue builder), popups, media library, menus, languages/translations, reports, settings, offers sections/cards.

---

## 7. Mobile / JSON API

Sanctum-authenticated service search, booking, news, media upload. See [API](./11-api.md).

---

## 8. Localization

English and Albanian language packs; Language module switcher; admin translation tools.

---

## 9. Offers

CMS-driven promotional cards embedded on hotel search and `/offers` routes (`modules/Offers`, registered in `config/app.php`).

---

## 10. Pro Add-on

`pro/` namespace, `PRO_ENABLE`, `pro_plan` middleware — optional commercial features.

---

## 11. Communications

Booking/user emails, SMS listeners, Chatify package, Pusher notification events.

---

## Related Documentation

- [Workflows](./14-workflows.md)
- [Business Logic](./12-business-logic.md)
- [Modules structure](./04-project-structure.md)
