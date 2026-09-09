# Python

Python is **optional**. The Laravel site does not invoke these files. They import ETG static data into MySQL.

There is **no** `requirements.txt`, `pyproject.toml`, or documented Python version. Imports imply CPython 3 with:

- `requests`
- `zstandard` (`hotels_data.py` only)
- `mysql.connector` (PyPI package `mysql-connector-python`)

A `.venv` directory exists in the repo; creation method is not in source.

**Security:** both scripts currently embed API and database passwords in plaintext. This documentation uses placeholders. Rotate any credentials that were committed and load them from environment variables locally.

Related: [Database](./11-DATABASE.md) · [Hotel Search](./21-HOTEL-SEARCH.md) · [ETG B2B API](./29-ETG-B2B-API.md)

---

## Virtualenv (suggested; not defined by the project)

```powershell
python -m venv .venv
.\.venv\Scripts\activate
pip install requests zstandard mysql-connector-python
```

Working directory: repository root.

---

## How Python fits in

```
ETG dump API → hotels_data.py → MySQL hotels + hotel_images
ETG static API → meal_data.py → MySQL meal_types
Laravel HotelHController reads hotels / hotel_images at search time
```

Without `hotels` rows, region/hotel search has nothing to join against ETG live rates.

---

## Script: `hotels_data.py`

**Purpose:** Request a compressed hotel-info dump from WorldOTA, stream-decompress zstd, upsert hotels and insert images in batches of 100.

**Runnable:** yes (`if __name__ == "__main__"`).

**Command:**

```powershell
python hotels_data.py
```

**Working directory:** repository root (or any cwd; the script uses absolute API/DB constants, not relative files).

**Arguments:** none.

**Environment variables:** none. The script uses in-file constants:

- `API_URL` = `https://api.worldota.net/api/b2b/v3/hotel/info/dump/`
- `USERNAME` / `PASSWORD` — use `YOUR_ETG_KEY_ID` / `YOUR_ETG_API_KEY`
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` — use your MySQL placeholders

**Expected output:** prints dump URL, batch insert messages, or traceback.

**Services that must be running:** MySQL reachable at the configured host; outbound HTTPS to `api.worldota.net` and the dump URL returned in JSON `data.url`.

**Flow:**

1. `fetch_dump_url()` — `POST` dump endpoint with Basic Auth, JSON `{"language": "en"}`.
2. `decompress_and_store_data(dump_url)` — `GET` dump, zstd stream, NDJSON lines.
3. Creates `hotels` and `hotel_images` if missing.
4. Image URLs: `{size}` replaced with `1080x1920`.
5. `INSERT ... ON DUPLICATE KEY UPDATE` for hotels; inserts for images (no image dedup).

**Error behavior:** non-200 raises; per-line JSON errors are printed and skipped; top-level `traceback.print_exc()`.

---

## Script: `meal_data.py`

**Purpose:** Fetch meal type catalog from ETG static endpoint and upsert `meal_types`.

**Runnable:** yes (`if __name__ == "__main__"`).

**Command:**

```powershell
python meal_data.py
```

**Working directory:** repository root.

**Arguments:** none.

**Environment variables:** none. In-file `API_URL` = `https://api.worldota.net/api/b2b/v3/hotel/static/` (GET, Basic Auth). Separate DB constants from `hotels_data.py` (this file uses different default DB name/user in source — point both at the same Laravel database locally).

**Expected output:** “Fetching meal types…”, sample meal JSON, “Meal types stored successfully.” or traceback.

**Table:** `meal_types (name UNIQUE, locale JSON)`.

**Laravel usage of `meal_types`:** not traced as a first-class model in this documentation pass. The table is created by this script for data that may be used in views/filters; confirm with a code search if you rely on it.

---

## Other Python

No other `.py` files were found at the repository root or in application packages. GeoLite and CKEditor trees contain no project Python.

---

## Not executable as modules

There is no `python -m package` layout. Do not run these as Laravel Artisan commands.
