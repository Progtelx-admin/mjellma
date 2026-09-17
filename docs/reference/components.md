# Frontend Components Reference

Reusable UI building blocks. Grouped by area.

## Layout Components

| Name | Path | Purpose |
| --- | --- | --- |
| App layout | `modules/Layout/app.blade.php` | Public chrome |
| Admin layout | `modules/Layout/admin/app.blade.php` | Admin chrome + Vue mount |
| Header / Topbar | `themes/BC/Layout/parts/*` | BC branding/nav |
| Language switcher | `modules/Language/Views/frontend/switcher.blade.php` | Locale switch |

## Hotel HA Partials

| Name | Path | Purpose |
| --- | --- | --- |
| form-search-ha | `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php` | Hero search |
| results-ha | `…/results-ha.blade.php` | Results + map + sort |
| hotel-card-chunk | `…/partials/hotel-card-chunk.blade.php` | Result cards fragment |
| filter-search-context | `…/partials/filter-search-context.blade.php` | Filters |
| hotel-map-preview | `…/partials/hotel-map-preview.blade.php` | Map preview |
| info / prebook / payment blades | `…/*-ha.blade.php` | Booking funnel |

**Behavior:** results page uses Leaflet + `fetch` to load chunks; not the classic `filter.js` pipeline.

## Classic Search Building Blocks

Under `themes/BC/Hotel/Views/frontend/layouts/search/` — form-search, filter-search, list-item, loop variants. Driven by `filter.js` + `hotel.js`.

## Booking Checkout

| Piece | Path |
| --- | --- |
| checkout.blade.php | `themes/BC/Booking/Views/frontend/checkout.blade.php` |
| checkout-form / payment / deposit / customer-info | `…/booking/*` |
| checkout.js | `public_html/module/booking/js/checkout.js` |

Props/state are mostly DOM + Vue instance on `#bravo-checkout-page` inside checkout.js.

## Admin Vue Components

Under `public_html/themes/admin/`:

- `js/components/file-picker.vue`, `nested-draggable.vue`
- Template admin components (`row`, `column`, `regular`, form fields: select2, editor, upload, spacing, listItem, radio-images)
- Live editor layers

Entry: `public_html/themes/admin/js/app.js`.

## Car API Views

- `themes/BC/Car/Views/frontend/detail-api.blade.php`
- `layouts/search/api-car-item.blade.php`

## Offers

Admin section/card forms under `modules/Offers/Views`; public listing embeds on hotel search.

## Related Documentation

- [Frontend](../09-frontend.md)
- [UI & Design System](../15-ui-and-design-system.md)
