# Testing

Related: [Scripts](./15-SCRIPTS-AND-COMMANDS.md)

---

## Framework

**PHPUnit 10** (`composer.json` `require-dev`: `phpunit/phpunit: ^10.1`). Config: `phpunit.xml`.

Also present: Mockery, Collision, Laravel Pint, Debugbar, Ignition. **No Pest** despite `allow-plugins.pestphp/pest-plugin`.

---

## Test directories

| Suite | Path | `phpunit.xml` |
|---|---|---|
| Unit | `tests/Unit` | suffix `Test.php` |
| Feature | `tests/Feature` | suffix `Test.php` |

Bootstrap: `vendor/autoload.php`. Coverage include: `app/` only.

---

## Tests that exist

### `tests/Unit/ExampleTest.php`

`test_that_true_is_true` — `assertTrue(true)`. Extends `PHPUnit\Framework\TestCase` (not Laravel TestCase).

### `tests/Feature/ExampleTest.php`

`test_the_application_returns_a_successful_response` — `$this->get('/')` asserts **200**.

That request hits `HotelHController@showHotels`. It will **fail** if:

- `storage/installed` is missing (redirect to `/install`, not 200)
- DB/settings errors occur during boot
- An exception is thrown in the hotel form view

`RefreshDatabase` is commented out.

### Support files

- `tests/TestCase.php`
- `tests/CreatesApplication.php`

No other `*Test.php` files were found.

There are **no** dedicated ETG, PCB, or car integration tests in `tests/`.

---

## How to run all tests

From repository root, with `vendor/` installed:

```powershell
php artisan test
```

or

```powershell
vendor\bin\phpunit
```

---

## How to run one test

```powershell
vendor\bin\phpunit --filter test_that_true_is_true
```

```powershell
php artisan test --filter=ExampleTest
```

---

## Environment

`phpunit.xml` `<php>` env:

- `APP_ENV=testing`
- `BCRYPT_ROUNDS=4`
- `CACHE_DRIVER=array`
- `MAIL_MAILER=array`
- `QUEUE_CONNECTION=sync`
- `SESSION_DRIVER=array`
- `TELESCOPE_ENABLED=false`

SQLite in-memory is **commented out**. Feature tests use whatever `.env` / phpunit does not override for `DB_*` — typically your MySQL `.env`. That is easy to point at production by mistake; use a dedicated test database.

---

## Frontend / E2E

No Cypress, Playwright, or Dusk configuration was found.

---

## Static analysis / lint

Pint is a Composer dev dependency (`laravel/pint`). No npm test script. No project-specific Pint command is defined in `composer.json` `scripts` beyond Laravel defaults.
