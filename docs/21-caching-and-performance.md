# Caching and Performance

## Overview

Caching is present but modest. Do not assume Redis unless configured.

## Backend Cache

| Item | Detail |
| --- | --- |
| Default driver | `CACHE_DRIVER=file` in `.env.example` |
| Optional | Redis/Memcached via env |
| Car metadata | `CarController` caches locations/manufacturers/categories/transmissions ~1 hour |
| Settings | Helpers may cache setting reads (AppHelper patterns) |

## HTTP / API Cache

No first-class CDN configuration ships in-repo. `cache.headers` middleware alias exists for opt-in route usage.

## Frontend

- Compiled assets in `public_html/dist` reduce runtime Sass cost
- Classic search uses AJAX fragment replacement (less full-page reload)
- HA results use chunked `fetch` for cards
- Lazyload hooks referenced from search JS

## Database

- Pagination on list endpoints/admin indexes
- Indexes defined in migrations—review when adding filters on large `hotels` tables
- ETG static catalog can be large; keep queries selective

## Async Processing

Default queue `sync`. Moving heavy mail/notifications to a real queue can reduce request latency.

## Image Handling

Intervention Image + `APP_RESIZE_SIMPLE` for media derivatives.

## Related Documentation

- [Configuration](./06-configuration.md)
- [Deployment](./24-deployment.md)
