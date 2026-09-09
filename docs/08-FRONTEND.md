# Frontend

There is no React/SPA app. The public site is **Laravel Blade** with jQuery and inline scripts. The admin UI uses **Vue 2** compiled by Laravel Mix.

Related: [Architecture](./03-ARCHITECTURE.md) · [Hotel Search](./21-HOTEL-SEARCH.md) · [Car Rental](./22-CAR-RENTAL.md)

---

## Framework and stack

| Piece | Detail |
|---|---|
| Templates | Blade (`modules/*/Views`, overlay `themes/BC`, `themes/Base`) |
| CSS | Bootstrap 4 (`package.json`), Sass in `public_html/sass` and `public_html/module/*/scss` |
| JS (public) | jQuery, libs under `public_html/libs/` |
| JS (admin) | Vue 2.7, vue-form-generator, vuedraggable, he-tree-vue, TinyMCE Vue |
| Bundler | Laravel Mix 6 (`public_html/themes/admin/webpack.mix.js`, `public_html/themes/bc/webpack.mix.js`) |
| Vite | `vite.config.js` exists; `resources/js/app.js` / `resources/css/app.css` were not found |

---

## Application “entry” (pages)

HTTP entry: `public_html/index.php`.

Homepage: `GET /` → `HotelHController@showHotels` → view `Hotel::frontend.form-search-ha` → `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php`.

`routes/web.php` has `HomeController@index` on `/home` only; `/` is not the CMS homepage.

Layouts: `modules/Layout/` (`admin/app.blade.php`, frontend parts). Theme locations are prepended so BC views win.

---

## Routing (user-facing)

Laravel named routes, not a client-side router.

Important public routes (hotel prefix defaults):

| Route name | Method / path | View / behavior |
|---|---|---|
| `hotel.show` | `GET /` | Search form |
| `hotel.search` | `GET /hotels/search` | Results + AJAX chunks |
| `hotel.suggestions` | `GET /hotel-suggestions` | JSON hotels/regions |
| `hotel.info` | `GET /hotel/{id}` | Hotel + rates |
| `hotel.prebook` | `POST /hotel/prebook` | ETG prebook |
| `hotel.prebook.result` | `GET /hotel/prebook/result` | Prebook page |
| `hotel.book` | `POST /hotel/book` | Start booking + PCB |
| `car.search` | `GET /car` | Car search |
| `booking.checkout` | `GET /booking/{code}/checkout` | Legacy checkout |
| `auth.register` | `GET/POST register` | Registration |
| Fortify login | `/login` | `auth.login` |

Admin: `/admin` and `/admin/module/{module}/...`.

When multi-language URL prefixes are enabled (`is_enable_language_route()`), `modules/Hotel/Routes/language.php` includes the same `web.php` under `/{locale}/`.

---

## Important pages (hotel HA)

| File | Role |
|---|---|
| `themes/BC/Hotel/Views/frontend/form-search-ha.blade.php` | Homepage search; ETG autocomplete |
| `themes/BC/Hotel/Views/frontend/results-ha.blade.php` | Results list/map; hidden `etg_hotel_id` |
| `themes/BC/Hotel/Views/frontend/info-ha.blade.php` | Hotel detail / rooms |
| `themes/BC/Hotel/Views/frontend/prebook-result-ha.blade.php` | Prebook confirmation |
| `themes/BC/Hotel/Views/frontend/booking-confirmation-ha.blade.php` | After booking |
| `themes/BC/Hotel/Views/frontend/payment-ha.blade.php` | Payment UI |
| `themes/BC/Hotel/Views/frontend/payment-success.blade.php` | Success |
| `themes/BC/Hotel/Views/frontend/booking-failed.blade.php` | Failure |
| `themes/BC/Hotel/Views/frontend/booking-pending.blade.php` | Pending |

Legacy Booking Core hotel search/detail views still exist (`search.blade.php`, `detail.blade.php`) but public `HotelController@index` routes are commented out.

Car: `themes/BC/Car/Views/frontend/` including `detail-api.blade.php`, `layouts/search/api-car-item.blade.php`.

---

## Important components

- Template builder blocks: `modules/Hotel/Blocks/FormSearchHotel.php`, `ListHotel.php` (page builder, not the HA homepage).
- Offers Blade components namespace `offers` (`Modules\Offers\ModuleProvider`).
- Admin Vue root compiled from `public_html/themes/admin/js/app.js`.

---

## State management

No Vuex/Redux. State is:

- Laravel session (`prebookData`, search dates, display price)
- Cache keys `search_params_{hash}`, `etg_region_hotel_ids_{id}_{b2b|b2c}`
- Query string (`checkin`, `checkout`, `etg_hotel_id`, `region_id`, `hid`, …)
- Auth session / Sanctum token for API

---

## Forms

- Hotel search form in `form-search-ha.blade.php` (GET `/hotels/search`)
- Autocomplete: `fetch` to `route('hotel.suggestions')` with `query` and `type`
- CSRF: web middleware `VerifyCsrfToken` on POST routes
- Auth forms: Fortify + `modules/User/Routes/web.php` register

---

## API communication (browser)

- Hotel suggestions: `GET /hotel-suggestions?query=&type=`
- Hotel chunks: AJAX `GET /hotels/search` with `chunk`, `_token`/ajax headers as implemented in the results view script
- Car: page loads plus `/car/api/*` routes on `CarController`
- Optional JSON API `/api/*` for mobile (not used by HA Blade as the primary hotel client)

---

## Styling and assets

- Mix frontend Sass: `public_html/sass/app.scss`, module scss → `public_html/dist/frontend`
- Mix admin: `public_html/themes/admin/scss/app.scss` → `public_html/dist/admin`
- Vendor libs: `public_html/libs/` (bootstrap, leaflet, datepicker, …)
- Uploads: `public_html/uploads/`
- Vue debug vs min: Blade picks `vue.js` vs `vue.min.js` from `APP_DEBUG`

---

## Frontend environment variables

Blade uses `env('CAR_IMAGE_BASE_URL')`, `env('CAR_API_BASE')` in car views. Pusher Mix/Vite keys exist in `.env.example` but the Vite app files are missing.

---

## Development server vs production build

| Command | Directory | Output |
|---|---|---|
| `npm run watch` | `public_html/themes/admin` | `public_html/dist/admin` |
| `npm run prod` | same | minified admin |
| Mix in `public_html/themes/bc` | frontend css | `public_html/dist/frontend` |

The PHP app does not need Mix running to serve already-built files.

---

## User flows

Documented separately:

- [Hotel Search](./21-HOTEL-SEARCH.md)
- [Car Rental](./22-CAR-RENTAL.md)
- [Authentication](./12-AUTHENTICATION.md)
- [Features](./20-FEATURES.md) (tours, spaces, CMS, offers)
