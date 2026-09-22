# Important Files

Curated project-owned files a new developer should know.

---

## File

```text
public_html/index.php
```

### Purpose

HTTP front controller / document root entry.

### Responsibilities

Bootstrap Laravel; optional `storage/bc.php` / `pro.php` includes; PHP version guard.

### Dependencies

Composer autoload, Laravel kernel.

### Used by

Web server.

---

## File

```text
config/app.php
```

### Purpose

Application name, env, providers, version `3.4.2`.

### Important symbols

Provider list including Theme, Modules, Plugins, Custom, Pro, Offers, Fortify.

### Related

`config/bc.php`, theme providers.

---

## File

```text
themes/Base/ThemeProvider.php
```

### Purpose

Registers all core module providers via `$modules`.

### Important behavior

Boot loads Base migrations and `RunUpdater` middleware.

---

## File

```text
themes/BC/ThemeProvider.php
```

### Purpose

Active product theme; version `3.6.0`; BC-specific providers.

### Used by

`Themes\ThemeServiceProvider` when `BC` is active.

---

## File

```text
modules/Hotel/Controllers/HotelHController.php
```

### Purpose

Primary hotel product: ETG + local catalog + PCB booking funnel.

### Important symbols

Credential helpers (`getUserType`, username/password selectors), search/book/payment methods.

### Dependencies

`HotelH`, `MjellmaBooking`, `PcbBankService`, ETG HTTP.

### Related files

Hotel routes, BC HA views, Python dump scripts.

---

## File

```text
modules/Car/Controllers/CarController.php
```

### Purpose

External car API proxy and checkout.

### Dependencies

`CAR_API_*`, cache, PCB service.

---

## File

```text
app/Services/PcbBankService.php
```

### Purpose

mTLS PCB order API client.

### Related

`config/pcb_bank.php`, `PcbBankGateway`, cert files under `storage/certs/`.

---

## File

```text
app/User.php
```

### Purpose

Central user model for web + API auth.

### Important behavior

Roles, Sanctum tokens, 2FA, soft deletes, publish status interactions.

---

## File

```text
app/Providers/FortifyServiceProvider.php
```

### Purpose

Wire Fortify views and authentication callback.

---

## File

```text
app/Http/Kernel.php
```

### Purpose

Global/group middleware and aliases (`dashboard`, `set_language_for_api`, …).

---

## File

```text
modules/Api/Routes/api.php
```

### Purpose

Mobile JSON API route map.

### Related

`modules/Api/Controllers/*`.

---

## File

```text
config/payment.php
```

### Purpose

Gateway class registry including `pcb_bank`.

---

## File

```text
hotels_data.py
meal_data.py
```

### Purpose

Offline ETL for hotel static data / meal types.

### Important behavior

May contain local credentials in working copies—**never commit or document real secrets**; configure privately.

---

## File

```text
app/Helpers/AppHelper.php
```

### Purpose

Global helpers for settings, money, menus, media, etc. (Composer autoloaded).

---

## File

```text
modules/Layout/app.blade.php
```

### Purpose

Public HTML shell and asset includes.

---

## File

```text
public_html/themes/admin/webpack.mix.js
```

### Purpose

Admin Vue/Sass build pipeline → `dist/admin`.

---

## File

```text
public_html/themes/bc/webpack.mix.js
```

### Purpose

Frontend Sass build → `dist/frontend`.

---

## Related Documentation

- [Project Structure](../04-project-structure.md)
- [Controllers](./controllers.md)
- [Services](./services.md)
