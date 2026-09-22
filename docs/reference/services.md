# Services Reference

## App\Services\PcbBankService

| Item | Detail |
| --- | --- |
| Path | `app/Services/PcbBankService.php` |
| Purpose | PCB Bank order API over mTLS |
| Methods | `isConfigured`, `createOrder`, `getOrderDetails`, `refundOrder`, `reverseOrder` (as implemented) |
| Config | `config/pcb_bank.php` |
| Used by | HotelHController, CarController, PcbBankGateway, diagnostic routes/commands |
| Errors | Returns structured failure when certs missing or HTTP errors |

## App\Services\InvoiceService

| Item | Detail |
| --- | --- |
| Path | `app/Services/InvoiceService.php` |
| Purpose | Create/manage invoice records tied to bookings/payments |
| DB | `invoices` table |

## Booking Gateways (`modules/Booking/Gateways`)

| Class | Role |
| --- | --- |
| `BaseGateway` | Shared gateway behavior |
| `PcbBankGateway` | PCB HPP integration for classic checkout |
| `PaypalGateway` | PayPal |
| `StripeGateway` / `StripeCheckoutGateway` | Stripe |
| `PayrexxGateway` | Payrexx |
| `PaystackGateway` | Paystack |
| `OfflinePaymentGateway` | Offline/manual |

Registered in `config/payment.php`.

## Helpers (service-like)

| Helper | Path | Role |
| --- | --- | --- |
| AppHelper | `app/Helpers/AppHelper.php` | Settings, money, menus, media URLs, pages |
| ProHelper | `app/Helpers/ProHelper.php` | Pro feature helpers |
| PermissionHelper | `modules/User/Helpers/PermissionHelper.php` | Permission registration/checks |
| FileHelper | `modules/Media/Helpers/FileHelper.php` | Media paths |
| ReCaptchaEngine | `app/Helpers/ReCaptchaEngine.php` | Captcha |
| Assets | `app/Helpers/Assets.php` | Asset registry |

## Related Documentation

- [Integrations](../17-integrations.md)
- [Payments feature](../13-features.md)
- [Important Files](./important-files.md)
