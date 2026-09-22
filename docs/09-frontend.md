# Frontend

## Overview

The public site and most admin pages are **Blade** templates. Admin interactive builders use **Vue 2** compiled with Laravel Mix. Classic service search uses **jQuery**; the ETG hotel (HA) experience uses Blade plus `fetch` and Leaflet.

## Document Root & Entry

- Web root: `public_html/`
- Front controller: `public_html/index.php`
- Layout alias: `resources/views/layouts/app.blade.php` → `Layout::app`
- Admin layout alias: `resources/views/admin/layouts/app.blade.php` → `Layout::admin.app`

## Theme Resolution

`Themes\ThemeServiceProvider`:

1. Prepends active theme view paths
2. Loads namespaced views from `themes/{Theme}/{Module}/Views` as `{Module}`
3. BC overrides Base and module defaults

Active theme: **BC** (`config/bc.php`).

## Application Shells

### Public

`modules/Layout/app.blade.php`:

- Loads Bootstrap/FA/ionicons, `dist/frontend/css/app.css`, shared scripts
- Structure: topbar → header → `@yield('content')` → footer
- BC overrides header/topbar under `themes/BC/Layout/parts/`

### Admin

`modules/Layout/admin/app.blade.php`:

- `#app` root for Vue
- Sidebar/header
- Mix bundles from `public_html/dist/admin`

## Pages & Feature UIs

| Area | Primary views |
| --- | --- |
| Hotel homepage / search | `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php`, `results-ha.blade.php` |
| Hotel detail / book | `info-ha`, `prebook-result-ha`, `booking-confirmation-ha`, `payment-ha`, success/failed |
| Classic hotel (legacy) | `search.blade.php`, `detail.blade.php` still present |
| Car API UI | BC Car views (`detail-api`, API list items) |
| Booking checkout | `themes/BC/Booking/Views/frontend/checkout.blade.php` + partials |
| Offers | Offered on hotel search UI; public `/offers` routes |

## JavaScript Behavior

| Concern | Location |
| --- | --- |
| Classic AJAX search | `public_html/js/filter.js` (`doSearch`) |
| Hotel filters / range | `public_html/module/hotel/js/hotel.js` |
| Map search | `public_html/module/hotel/js/hotel-map.js` |
| Home autocomplete | `public_html/js/home.js` |
| Checkout | `public_html/module/booking/js/checkout.js` |
| HA results | Inline scripts in `results-ha.blade.php` (Leaflet, chunked `fetch`) |

## State Management

No Redux/Vuex store for the public site. State lives in:

- Server session (auth, locale, currency, booking tokens)
- DOM / jQuery
- Vue local component state in admin

## API Communication From Frontend

- Form posts and AJAX to Laravel named routes
- HA hotel search calls `hotel.search` / map endpoints
- API clients use `/api` + Sanctum Bearer token (separate from Blade session)

## Assets

| Build | Source | Output |
| --- | --- | --- |
| Admin Mix | `public_html/themes/admin` | `public_html/dist/admin` |
| Frontend Mix | `public_html/themes/bc` | `public_html/dist/frontend` |
| Static JS/CSS | `public_html/js`, `public_html/module/*/js` | served directly |

Root `package.json` scripts `admin-watch` / `admin-prod` call `mix` at repo root, but **no root `webpack.mix.js`** exists—run Mix from theme directories.

## Forms & Validation

- Blade forms with CSRF tokens
- Server-side validation errors via session shared errors
- Client-side checks in checkout/HA scripts

## Loading / Error UX

- AJAX search replaces result containers
- PCB return uses processing/spinner style pages
- Booking failed view: `Hotel::frontend.booking-failed`

## Related Documentation

- [UI & Design System](./15-ui-and-design-system.md)
- [Components](./reference/components.md)
- [Workflows](./14-workflows.md)
