# Controllers Reference

## HotelHController

| Item | Detail |
| --- | --- |
| Path | `modules/Hotel/Controllers/HotelHController.php` |
| Responsibility | ETG hotel search, detail, prebook/book, PCB payment bridge, finish/cancel, history/invoice |
| Key actions | `showHotels`, `searchHotels`, `mapHotels`, `getHotelSuggestions`, `hotelInfo`, `prebookRoom`, `bookRoom`, `handleBookingSubmission`, `handlePcbReturn`, `confirmAfterPcb`, `finishBooking`, `completeBooking`, `cancelBooking`, `testApiCredentials` |
| Integrations | ETG Basic Auth, PcbBankService, HotelH/MjellmaBooking models |
| Auth | Mostly public; admin/history/cancel as routed |

## CarController

| Item | Detail |
| --- | --- |
| Path | `modules/Car/Controllers/CarController.php` |
| Responsibility | External car API search/checkout and PCB/cash completion |
| Dependencies | `CAR_API_*` env, HTTP client, cache, PcbBankService |

## Booking Controllers

Under `modules/Booking/Controllers` — cart, checkout, gateway returns (`NormalCheckoutController`, etc.). Drive classic `bravo_bookings` flows.

## Api\AuthController

| Item | Detail |
| --- | --- |
| Path | `modules/Api/Controllers/AuthController.php` |
| Actions | login, register, logout, refresh, me, changePassword |
| Auth | Sanctum except login/register |
| Validation | email/password/device_name on login |

## Api Search / Booking Controllers

`SearchController`, `BookingController`, `UserController`, `LocationController`, `NewsController`, `MediaController`, `ReviewController` — mobile API surface.

## Admin Controllers

Each module’s `Admin\*` controllers provide CRUD for that domain (hotels legacy, tours, pages, users, reports, …). Guarded by admin middleware/permissions.

## App Controllers

- `HomeController`, `InstallerController`, `LandingpageController`
- `Auth\LoginController` social OAuth
- Fortify views wired via service provider (not classic Auth controllers for all flows)

## Related Documentation

- [Routes](./routes.md)
- [Backend](../08-backend.md)
- [Services](./services.md)
