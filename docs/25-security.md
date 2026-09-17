# Security

## Overview

Security controls verified in the current codebase. This is not a penetration-test report.

## Authentication & Sessions

- Fortify web auth with publish-status gate
- Password hashing via Laravel hasher
- Optional 2FA
- Sanctum tokens for API (store tokens securely on clients)
- Session driver configurable; use secure cookies on HTTPS

## Authorization

- Role/permission checks and `dashboard` middleware
- Policies for bookable resources
- Signed URLs used on some destructive vendor routes (`middleware(['signed'])`)

## CSRF

`VerifyCsrfToken` in web middleware group. API token auth does not rely on CSRF the same way—protect tokens.

## Validation & Sanitization

- Request validation in controllers
- `mews/purifier` available for HTML cleaning
- File uploads go through Media module validation patterns

## Payment Security

- PCB uses mTLS client certificates—protect PEM files (permissions, outside public web root)
- Never log full card data; PCB HPP keeps card entry on bank side
- Gateway secrets via env/settings only

## Rate Limiting

- API middleware group uses `ThrottleRequests:api`
- Login route uses `throttle:login` on API auth

## Headers / HTTPS / CORS

- `TrustProxies`, `HandleCors`, `APP_HTTPS` related behavior
- Configure CORS intentionally for API clients (`config/cors.php`)

## Installer & Diagnostics

- `RedirectToInstaller` until installed
- Diagnostic routes (`/pcb-test`, `/hotel/test-api-credentials`) can leak configuration metadata—restrict in production

## Secrets Handling

- Keep `.env` out of VCS
- Rotate any secret that was ever committed historically
- Python dump scripts must not ship live passwords

## Related Documentation

- [Authentication](./10-authentication-and-authorization.md)
- [Environment Variables](./reference/environment-variables.md)
- [Deployment](./24-deployment.md)
