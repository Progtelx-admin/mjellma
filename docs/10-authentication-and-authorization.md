# Authentication and Authorization

## Overview

Web authentication uses **Laravel Fortify** with the `web` session guard. The JSON API uses **Laravel Sanctum** personal access tokens. Authorization uses a **custom role/permission** system (not Spatie).

Never log or document real passwords, hashes, or tokens.

## Registration

- Fortify registration feature is **commented out** in Fortify config; registration is handled through custom User module / API flows gated by `is_enable_registration()`.
- API: `POST /api/auth/register` in `modules/Api/Controllers/AuthController.php`.

## Login (Web)

- Fortify login view `auth.login`
- Custom credential check: user must have `status == "publish"` and valid password (`FortifyServiceProvider`)
- Session cookie auth thereafter

## Login (API)

```text
POST /api/auth/login
{ email, password, device_name }
→ { access_token, user, status }
```

Token created via `$user->createToken($device_name)->plainTextToken`. Subsequent routes use `Authorization: Bearer <token>` with `auth:sanctum`.

Comments in AuthController still mention “JWT”; implementation is Sanctum. `config/jwt.php` / `JWT_SECRET` are legacy and not the active API mechanism.

## Logout / Password / Verification / 2FA

Fortify enables:

- Password reset
- Email verification (`MustVerifyEmail` on `App\User`)
- Two-factor authentication (with confirm password)

Vendor user routes often use `auth` + `verified` middleware.

## Social Login

`routes/web.php`:

- `GET social-login/{provider}`
- `GET social-callback/{provider}`

Implemented via Socialite in Auth login controller.

## User Model

`app/User.php` (auth provider may resolve `App\Models\User` alias):

- Traits: `HasRoles`, `HasApiTokens`, `TwoFactorAuthenticatable`, soft deletes, wallet/member traits
- Status-driven publish gate for login

## Roles & Permissions

| Piece | Location |
| --- | --- |
| Trait | `modules/User/Traits/HasRoles.php` |
| Models | `Modules\User\Models\Role`, `RolePermission` |
| Tables | `core_roles`, `core_role_permissions` |
| Helper | `Modules\User\Helpers\PermissionHelper` |
| Config | `config/permissions.php` |
| Policies | `modules/User/Policies/*` |

Admin UI requires `dashboard` middleware (`App\Http\Middleware\Dashboard`) checking dashboard access permission/gate.

## B2B vs B2C (ETG credentials)

`HotelHController` classifies the current user for supplier credentials:

- Guests → B2C
- User meta `user_type` or role name/code hints (`b2b`, `administrator`, `vendor`, `business` → B2B; `b2c`/`customer` → B2C)
- Selects `API_USERNAME_B2B` / `API_PASSWORD_B2B` or `*_B2C` with fallback to `API_USERNAME` / `API_PASSWORD`

This is supplier credential selection, not a separate Laravel guard.

## Route Protection Patterns

| Area | Typical middleware |
| --- | --- |
| Admin | `auth`, `dashboard` |
| Vendor manage | `auth`, `verified` |
| API auth routes | `auth:sanctum` (except login/register) |
| Logs | `auth`, `dashboard`, `system_log_view` |
| Pro features | `pro_plan` |

## Password Handling (conceptual)

Passwords are hashed with Laravel’s hasher (bcrypt rounds from `config/hashing.php`). Never store plaintext passwords. Require-change-password middleware can force updates when enabled.

## Related Documentation

- [API](./11-api.md)
- [Security](./25-security.md)
- [Business Logic](./12-business-logic.md)
