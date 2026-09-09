# Authentication and Authorization

Related: [API](./10-API.md) · [Environment Variables](./06-ENVIRONMENT-VARIABLES.md)

---

## Stack

| Piece | Role |
|---|---|
| Laravel Fortify | Login, password reset, email verify, 2FA views |
| `App\Models\User` extends `App\User` | Eloquent user (`config/auth.php`) |
| Sanctum | API personal access tokens |
| Socialite | `social-login/{provider}` Facebook/Google/Twitter |
| `HasRoles` | Permissions for admin/vendor |
| Chatify | Messenger (`/chatify`) for authenticated users |

Guards: only `web` (session) is defined in `config/auth.php`. API uses Sanctum, not a separate `api` guard in that file.

---

## Web login

- Views registered in `App\Providers\FortifyServiceProvider` (`auth.login`, passwords, 2FA, verify).
- `Modules\User\CustomFortifyAuthenticationProvider` binds Fortify `LoginRequest` to `Modules\User\Fortify\LoginRequest` (reCAPTCHA-capable login validation).
- `Fortify::authenticateUsing`: user must exist, password hash match, **`status == "publish"`**.
- Login throttle: 5 per minute per email+IP.
- After login, `LoginController@redirectTo`: users with `dashboard_access` go to `/admin`, others `/user/profile`.
- Fortify `home` is `/` (`RouteServiceProvider::HOME`).

Registration: `GET/POST /register` → `Modules\User\Controllers\Auth\RegisterController` (also Fortify create-user action `App\Actions\Fortify\CreateNewUser`).

Email verification: `MustVerifyEmail` on `App\User`. Many user routes use middleware `verified`.

Force password change: `RequireChangePassword` middleware unless `DISABLE_REQUIRE_CHANGE_PW`.

---

## Social login

`routes/web.php`:

- `GET social-login/{provider}`
- `GET social-callback/{provider}`

`LoginController@initConfigs` sets Socialite `client_id` / `client_secret` from **settings** (`facebook_client_id`, etc.), not from `config/services.php` Google/Facebook keys (those service keys were not found in `config/services.php` beyond mail/stripe/recaptcha).

---

## API authentication

`POST /api/auth/login` (`Modules\Api\Controllers\AuthController`):

- Validates `email`, `password`, `device_name`
- Returns Sanctum `plainTextToken` as `access_token`
- Constructor: `auth:sanctum` except `login` and `register`

`GET /api/user` (root api file) also uses `auth:sanctum`.

JWT: `config/jwt.php` and `JWT_SECRET` exist, but this login path uses **Sanctum**, not tymon/jwt-auth in `composer.json`. Treat JWT config as leftover unless another package consumes it.

---

## Roles, B2B / B2C, admin

`HotelHController::getUserType()`:

1. Guest → `b2c`
2. User meta `user_type` if `b2b` or `b2c`
3. Role `code` containing `b2b`, `administrator`, `vendor` → `b2b`; `b2c` → `b2c`
4. Role `name` containing `b2b`, `business`, `administrator`, `vendor` → `b2b`; `b2c` or `customer` → `b2c`
5. Else `b2c`

Administrator and Vendor roles are treated as B2B. Full env setup and `/hotel/test-api-credentials`: [B2B / B2C credentials](./28-B2B-B2C-CREDENTIALS.md).

Admin UI: middleware `dashboard` (`App\Http\Middleware\Dashboard`). Permission checks via `PermissionHelper` (e.g. `hotel_view`, `carrent_view`, `offers_view`).

Logs: extra `system_log_view`.

Pro features: `pro_plan` middleware + `PRO_ENABLE`.

---

## User model capabilities (`App\User`)

Traits: `HasRoles`, `TwoFactorAuthenticatable`, `HasApiTokens`, `HasMembers`, `HasWallet`, `SoftDeletes`, `Notifiable`.

Meta: `getMeta` / `addMeta` on `user_meta`.

---

## 2FA

Fortify feature `two-factor-authentication` is removed at runtime if setting `user_enable_2fa` is empty (`AppServiceProvider`). User UI: `/user/2fa` (`TwoFactorController`).

---

## CSRF and CORS

Web POST routes require CSRF. API CORS: `config/cors.php` allows `*` on `api/*`.
