# Documentation Index

All files in `docs/project-documentation/` generated from **source and configuration**, not from existing Markdown.

## Start and setup

- [Start Here](./00-START-HERE.md) — Required software, first-time env, database, how to verify the site is up.
- [Project Overview](./01-PROJECT-OVERVIEW.md) — What Mjellma/Booking Core is and which products it sells.
- [Installation](./04-INSTALLATION.md) — Composer, document root `public_html`, installer, migrate/seed.
- [Configuration](./05-CONFIGURATION.md) — `config/` files and settings overlay from the database.
- [Environment Variables](./06-ENVIRONMENT-VARIABLES.md) — Complete `env()` / `.env.example` reference with placeholders.
- [Running the Project](./07-RUNNING-THE-PROJECT.md) — Artisan, Mix, queue, scheduler, Python, gulp.

## Architecture and code layout

- [Project Structure](./02-PROJECT-STRUCTURE.md) — Important directories and how they connect.
- [Architecture](./03-ARCHITECTURE.md) — Layers, request flow, Mermaid hotel diagram.
- [Frontend](./08-FRONTEND.md) — Blade, Mix, Vue admin, HA views, assets.
- [Backend](./09-BACKEND.md) — Laravel lifecycle, controllers, middleware, services.
- [Modules and Important Files](./24-MODULES.md) — File-level notes for major modules.
- [Development Guide](./19-DEVELOPMENT-GUIDE.md) — Where to add routes, models, env vars, tests.

## Data, APIs, auth

- [API](./10-API.md) — Web hotel/car/booking routes and `/api` JSON API.
- [Database](./11-DATABASE.md) — MySQL, migrations, `bravo_hotels` vs `hotels`, seeders.
- [Authentication](./12-AUTHENTICATION.md) — Fortify, Sanctum, roles, B2B/B2C ETG keys.
- [External Integrations](./13-EXTERNAL-INTEGRATIONS.md) — ETG, car API, PCB, PayPal, Stripe, mail, SMS.
- [Python](./14-PYTHON.md) — `hotels_data.py` and `meal_data.py` run instructions.

## Features

- [Features](./20-FEATURES.md) — Hotel, cars, tours/spaces/events/flights/boats, CMS, offers, admin.
- [Hotel Search](./21-HOTEL-SEARCH.md) — End-to-end ETG search, IDs, chunks, prebook, book.
- [ETG B2B API](./29-ETG-B2B-API.md) — Official [docs.emergingtravel.com B2B API](https://docs.emergingtravel.com/docs/b2b-api/) mapped to this repo (used vs unused).
- [Car Rental](./22-CAR-RENTAL.md) — External car API flow and admin CarRent.
- [Payments](./23-PAYMENTS.md) — PCB Bank, other gateways, invoices.
- [Admin routes](./25-ADMIN-ROUTES.md) — Every admin prefix and route file (`dashboard_access`).
- [Route list](./26-ROUTE-LIST.md) — How to run `route:list` and a source-based dump of public/hotel/car/booking routes.
- [PCB payment flow](./27-PCB-PAYMENT-FLOW.md) — Hotel PCB + ETG finish, step by step (no HPP screenshots in repo).
- [B2B / B2C credentials](./28-B2B-B2C-CREDENTIALS.md) — How ETG `.env` keys are chosen per user.

## Operations

- [Scripts and Commands](./15-SCRIPTS-AND-COMMANDS.md) — Composer, npm, Artisan, gulp, Python.
- [Testing](./16-TESTING.md) — PHPUnit example tests only.
- [Deployment](./17-DEPLOYMENT.md) — GitHub FTP workflows and production checklist.
- [Troubleshooting](./18-TROUBLESHOOTING.md) — Installer, ETG, Mix, PCB, Python, CORS.

---

## Suggested reading order for a new developer

1. Start Here  
2. Project Overview  
3. Installation  
4. Architecture  
5. Hotel Search  
6. ETG B2B API  
7. PCB payment flow  
8. B2B / B2C credentials  
9. Environment Variables  
10. Development Guide  

