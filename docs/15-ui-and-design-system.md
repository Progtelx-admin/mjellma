# UI and Design System

## Overview

UI consistency comes from shared Blade layouts, Bootstrap-based admin/frontend themes, Sass partials, and reusable Booking Core CSS patterns—not a separate design-token package.

## Layout Structure

| Region | Implementation |
| --- | --- |
| Public shell | `Layout::app` → `.bravo_wrap`, topbar, header, content, footer |
| User dashboard shell | `Layout::user` |
| Empty/minimal | `Layout::empty` |
| Auth | `modules/Layout/auth/*` |
| Admin | `Layout::admin.app` with sidebar + header |

BC theme customizes public header/topbar.

## Navigation

- Public header/menu from menu settings + theme parts
- Admin sidebar built from module `ModuleProvider` admin menu hooks
- Language switcher: Language module frontend view

## Reusable Patterns

| Pattern | Where |
| --- | --- |
| Search forms | Theme `layouts/search` + HA hero form |
| Result cards | Classic loops; HA `hotel-card-chunk` partial |
| Filters | Sidebar filters, ionRangeSlider |
| Maps | BravoMapEngine; HA Leaflet |
| Checkout panels | Booking checkout partials |
| Modals / confirms | bootbox (admin npm), Bootstrap modals |
| Tables | Admin index blades |
| Alerts | Session flash + Bootstrap alerts |
| Pagination | Laravel paginator in list views |

## Typography & Spacing

- Public layout references Poppins and Bootstrap spacing utilities
- Admin uses theme SCSS under `public_html/themes/admin` and `public_html/themes/admin/scss` patterns
- Frontend Sass entry points compile through `public_html/themes/bc/webpack.mix.js` from `public_html/sass` and `public_html/module/*/scss`

## Responsive Behavior

Bootstrap grid drives breakpoints. HA hotel pages include mobile-oriented hero tabs and map/list toggles in Blade.

## Styling Locations

| Kind | Path |
| --- | --- |
| Compiled CSS | `public_html/dist/frontend`, `public_html/dist/admin` |
| Source Sass | `public_html/sass`, `public_html/module/*/scss`, admin theme SCSS |
| Module CSS (legacy) | `public_html/module/*/css` |
| Libs | `public_html/libs` |

## Admin Vue Components

Template builder and media widgets live under `public_html/themes/admin` (file-picker, nested-draggable, form-generator fields, template row/column components).

## Related Documentation

- [Frontend](./09-frontend.md)
- [Components](./reference/components.md)
