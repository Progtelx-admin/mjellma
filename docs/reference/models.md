# Models Reference

Important Eloquent models. Fields listed are indicative—see migrations for full schema.

## App\User

| Item | Detail |
| --- | --- |
| Table | `users` |
| Purpose | Authenticated users |
| Traits | HasRoles, HasApiTokens, TwoFactorAuthenticatable, SoftDeletes, wallet/member traits |
| Rules | Login requires `status == publish` (Fortify) |
| Relations | Roles, bookings, wishlist, tokens |

## Modules\Hotel\Models\HotelH

| Item | Detail |
| --- | --- |
| Table | `hotels` |
| Purpose | ETG static hotel catalog |
| Casts | `metapolicy_struct` array |
| Relations | `images()` → HotelImage |

## Modules\Hotel\Models\HotelImage

| Item | Detail |
| --- | --- |
| Table | `hotel_images` |
| Purpose | Hotel gallery images |
| FK | `hotel_id` → hotels |

## Modules\Hotel\Models\MjellmaBooking

| Item | Detail |
| --- | --- |
| Table | `mjellma_bookings` |
| Purpose | ETG hotel booking records |
| Fillable | order/partner ids, user contacts, payment fields, pcb/api status, pcb response |
| Casts | `pcb_bank_response` → array |
| Notes | Overrides `save()` to avoid missing `create_user` column issues |

## Modules\Hotel\Models\Hotel (legacy)

| Item | Detail |
| --- | --- |
| Table | `bravo_hotels` |
| Purpose | Classic Booking Core hotel inventory |
| Related | HotelRoom, dates, terms, translations |

## Modules\Booking\Models\Booking / Bookable

Core booking abstraction used by bookable services; `bravo_bookings` persistence and status helpers.

## Modules\Offers\Models

- `OfferSection` → `offer_sections`
- `OfferCard` → `offer_cards`

## Modules\User\Models\Role / RolePermission

Custom RBAC tables `core_roles`, `core_role_permissions`.

## Media / CMS / Location / Review

Standard Booking Core models under respective modules (`MediaFile`, `Page`, `News`, `Location`, `Review`, …).

## Related Documentation

- [Database Tables](./database-tables.md)
- [Business Logic](../12-business-logic.md)
