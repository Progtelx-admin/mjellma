# B2B / B2C ETG credentials

Hotel live rates use **two WorldOTA Basic Auth accounts**. Which pair is used depends on the **logged-in user**, not on a toggle in the search form (except a manual `?type=` override on some requests).

**Do not put real keys in git or in this file.** Use placeholders only.

Related: [Environment Variables](./06-ENVIRONMENT-VARIABLES.md) · [Hotel Search](./21-HOTEL-SEARCH.md) · [Authentication](./12-AUTHENTICATION.md)

---

## What to put in `.env`

These keys are **not** in `.env.example`. Add them yourself:

```env
API_URL=https://api.worldota.net/api/b2b/v3/

# Fallback if B2B/B2C keys are empty
API_USERNAME=YOUR_ETG_KEY_ID
API_PASSWORD=YOUR_ETG_API_KEY

# Used when getUserType() === b2b
API_USERNAME_B2B=YOUR_ETG_B2B_KEY_ID
API_PASSWORD_B2B=YOUR_ETG_B2B_API_KEY

# Used for guests and B2C users
API_USERNAME_B2C=YOUR_ETG_B2C_KEY_ID
API_PASSWORD_B2C=YOUR_ETG_B2C_API_KEY
```

Read in `modules/Hotel/Controllers/HotelHController.php`:

- `getApiUrl()` → `API_URL` with default `https://api.worldota.net/api/b2b/v3/`
- `getApiUsername()` / `getApiPassword()` → B2B pair or B2C pair, each falling back to `API_USERNAME` / `API_PASSWORD`

Same URL for both types. Different usernames typically return **different prices** (`display_final_price` on prebook/book).

---

## How the app chooses B2B vs B2C

Private method `getUserType()`:

1. **No user** → `b2c` (public search uses B2C keys).
2. User meta `user_type` (`user_meta` table, `User::getMeta('user_type')`) if value is `b2b` or `b2c` (case-insensitive).
3. Else role **code** (`$user->role->code`):
   - contains `b2b`, `administrator`, or `vendor` → `b2b`
   - contains `b2c` → `b2c`
4. Else role **name**:
   - contains `b2b`, `business`, `administrator`, or `vendor` → `b2b`
   - contains `b2c` or `customer` → `b2c`
5. Else → `b2c`

Administrator and Vendor are treated as **B2B**, so an admin browsing `/` sees B2B ETG prices if `API_USERNAME_B2B` is set.

---

## Manual override

`getManualApiCredentials()`:

- Request query `type=b2b` → B2B env pair
- `type=b2c` → B2C env pair
- otherwise automatic `getUserType()`

Used where that helper is called (credential test / some requests). Do not rely on it for the main search form unless the request includes `type`.

---

## How to set a user’s type

| Mechanism | Where |
|---|---|
| Meta `user_type` | `user_meta.name = user_type`, `val` = `b2b` or `b2c`. Set via user admin/profile meta if your UI exposes it. |
| Role code/name | Admin **Roles** (`/admin/module/user/role`). Codes containing `vendor` / `administrator` imply B2B. |
| Guest | Always B2C keys |

There is no dedicated `.env` switch “force all traffic B2C”.

---

## Verify without printing secrets

```
GET /hotel/test-api-credentials
```

`HotelHController@testApiCredentials` returns JSON: user id/email, `user_type`, role name/code, meta, **masked** username (`xxxx****`), password **length** only, and whether `API_USERNAME_B2B` / `_B2C` / `API_URL` are **set** or **not_set**.

Comment on the route: remove in production.

Logs: `Log::info('API Credentials Test', ...)` also masks username.

---

## Effect on payment

PCB `createOrder` amount prefers `display_final_price` from the session/request (the price the guest saw with the current credential pair). ETG finish still uses ETG `payment_types` amounts in `bookingData`. After PCB, finish is sent as ETG type **`deposit`** so RateHawk is not given the card (`handlePcbReturn`).

If B2B and B2C keys are swapped or B2B is missing, admins and guests can see **the same or wrong** prices. Check `/hotel/test-api-credentials` while logged in as each role.

---

## Region sort

`getHotelIdsForRegion` POSTs ETG `search/serp/region/` with occupancy/dates and caches IDs 15 minutes (`etg_region_search_*`, includes `user_type` in the cache key so B2B/B2C results stay separate). `search/hotelsort/` is not used on this branch.
