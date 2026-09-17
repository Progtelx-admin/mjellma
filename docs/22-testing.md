# Testing

## Overview

Automated tests use **PHPUnit 10** (`phpunit.xml`). Current coverage is minimal boilerplate.

## Layout

```text
tests/
├── CreatesApplication.php
├── TestCase.php
├── Feature/ExampleTest.php
└── Unit/ExampleTest.php
```

## Configuration

`phpunit.xml` defines Unit and Feature suites and bootstraps the Laravel app.

## Running Tests

From repository root:

```powershell
php artisan test
```

or:

```powershell
vendor\bin\phpunit
```

## Coverage Honesty

There are no substantial feature tests for hotel ETG flows, PCB payments, or car API integration in the repository. Treat manual QA and staging verification as required for those paths.

## Manual / Diagnostic Aids

- `GET /hotel/test-api-credentials` — env presence / user type mapping
- `GET /pcb-test` — PCB configuration presence (paths exist, not secret contents)
- Artisan PCB test commands

Do not expose these diagnostics on production without protection.

## Related Documentation

- [Troubleshooting](./26-troubleshooting.md)
- [Maintenance](./27-maintenance-and-extension-guide.md)
