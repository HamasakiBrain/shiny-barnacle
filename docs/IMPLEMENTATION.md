# Implementation record

Full source is provided as ordinary project files (no omitted method bodies).
The staged checks below are updated as implementation proceeds.

## 1. Architecture and schema

Created `docs/ARCHITECTURE.md`: domain scope, boundaries, concurrency rules,
state transitions, schema and Mermaid ER diagram. Checked relationships and
unique constraints against checkout, cancellation, reviews and favorite flows.
No executable application existed at this stage; runtime tests are not applicable.

## 2. Persistence
Created the store migration, eight domain models, eight factories, enums and a
repeatable development seeder. User models expose only relationships/casts.
SchemaTest passed (5 assertions); foreign keys and factory relationships resolve.
The schema matches stage 1. Demo passwords come from environment configuration.

## 3. Authorization foundation
Added Sanctum and its token migration, 24-hour token expiry, OrderPolicy,
ReviewPolicy, the admin Gate and separate API/auth rate limiters.
Authorization tests verify ownership, role protection and payment ownership.
HTTP authentication is wired through services in stages 4-6.

## 4. Repositories
Created five repository interfaces and Eloquent implementations, registered with
the container. Repository tests passed (7 assertions), including eager loading
and hidden-product filtering. Queries remain outside controllers and services.

## 5. Services and DTOs
Added AuthService, CatalogService, CartService, CheckoutData, OrderWorkflow and
BusinessException. Unit tests cover allowed/forbidden transitions and stable
request fingerprints. Service dependencies resolve through stage 4 bindings.

## 6. HTTP API
Created FormRequests, explicit JSON resources, controllers, versioned routes and
JSON exception handling. Auth tests passed (14 assertions): registration, login,
revocation, weak passwords, mass-assignment defense and admin access controls.
The HTTP layer delegates business decisions to services.

## 7. Cart, orders and payments
Added CheckoutService, OrderService and PaymentService. Five checkout tests passed
(41 assertions): server pricing, retry keys, rollback, stock restoration, IDOR,
mock failure/retry, COD settlement and state transitions. No external payments.

## 8. Reviews and favorites
Added EngagementService/Controller and routes. Two tests passed (16 assertions).
Reviews require delivered purchases; upserts and favorites are idempotent.
Fixed PUT review status to consistently return 200 for create/update.

## 9. Events and queues
OrderPlaced implements ShouldDispatchAfterCommit. The discovered listener queues
SendOrderNotification; NotificationService uses Laravel Notifications and a
deterministic UUID under a user lock to avoid duplicate delivery on retry.
Redis backs cache, rate limits and queues in Docker. Tests cover rollback,
commit, repeated job execution and notification privacy.

## 10. Unit and feature tests

Added catalog administration/filtering, tenant isolation, token expiration/revocation,
rate-limiting and OpenAPI coverage checks. Removed placeholder example tests.
The default configuration uses SQLite in memory; phpunit.mysql.xml uses the
dedicated market_testing database. A TestCase guard refuses application databases.
Docker inherits environment values through multiple PHP environment adapters;
both env and server settings are forced in test XML to keep isolation reliable.

## 11. Docker and Swagger

Created Dockerfile, compose.yaml, docker/entrypoint.sh, docker/nginx.conf,
docker/php.ini and docker/mysql-init.sh. Startup installs locked dependencies,
migrates and seeds local data. The queue runs in a separate service using the PHP
image. Initial Windows CRLF shell-script failure was fixed; a fresh MySQL-volume
startup then completed successfully.

Created public/openapi.json, scripts/build-openapi.py, the Swagger Blade view,
local Swagger assets, npm lock file and OpenApiTest. All 31 API operations are
documented and matched against real routes. Browser verification rendered all 31
operations, the authorization dialog control and no console errors.

## 12. README and final verification

README.md contains installation, Docker/local commands, architecture, ER diagram,
source layout, account configuration, API examples and testing instructions.
scripts/setup-env.php generates secrets only in ignored .env.
CodeGraph was initialized with user approval after source creation.

Validation performed on 2026-11-10:
- PHP 8.3 Docker execution and PHP 8.5 host compatibility.
- SQLite and MySQL unit/feature suites each passed: 31 tests, 186 assertions.
- Two-process MySQL probe passed: final-unit contention and same-key checkout.
- HTTP smoke passed through Nginx, including COD delivery, mock failure/retry,
  reviews, favorites, stock accounting, token revocation and real Redis notifications.
- Swagger rendered 31 operations without browser errors.
- PSR-12 check passed; Composer metadata validated.
- Composer audit and npm audit found no known advisories at verification time.
- No failed Laravel queue jobs.
- No CI/CD, GitHub Actions or deployment pipelines created.

Scope limitations remain those documented in ARCHITECTURE.md: no real payment or
mail/SMS integration, no production deployment and no transactional outbox.
See SOURCE_INDEX.md for direct links to the complete source files.


Final source review also added an HTTP test for payment ownership and cancellation
of a paid mock order (refund state, restored stock, payment blocked after cancellation).
The environment generator was checked with CRLF input and repeated execution:
credentials are generated correctly and an existing .env remains byte-identical.
An exact-value scan found no generated secrets in 161 Git-visible source files.
Both dependency audits and the 115-file PSR-12 check completed successfully.

### Files by implementation stage

| Stage | Created or updated files |
| --- | --- |
| 1 | docs/ARCHITECTURE.md, docs/IMPLEMENTATION.md |
| 2 | database/migrations/, database/factories/, database/seeders/, app/Models/, app/Enums/, config/store.php |
| 3 | app/Policies/, config/sanctum.php, app/Providers/AppServiceProvider.php, Sanctum migration |
| 4 | app/Repositories/Contracts/, app/Repositories/Eloquent/ |
| 5 | app/DTOs/CheckoutData.php, app/Exceptions/BusinessException.php, AuthService, CatalogService, CartService, OrderWorkflow |
| 6 | app/Http/Controllers/, app/Http/Requests/, app/Http/Resources/, routes/api.php, bootstrap/app.php |
| 7 | CheckoutService, OrderService, PaymentService, CheckoutTest |
| 8 | EngagementService, EngagementController, EngagementTest |
| 9 | app/Events/, app/Listeners/, app/Jobs/, app/Notifications/, NotificationService, config/queue.php |
| 10 | tests/Unit/, tests/Feature/, tests/TestCase.php, phpunit.xml, phpunit.mysql.xml, pint.json |
| 11 | Dockerfile, compose.yaml, docker/, public/openapi.json, resources/views/docs.blade.php, scripts/build-openapi.py, scripts/copy-swagger.mjs |
| 12 | README.md, docs/SOURCE_INDEX.md, scripts/setup-env.php, scripts/check-concurrency.php, scripts/smoke.py |

All stages use the same model relationships, repository contracts, resource shapes
and enum states. Final tests run across the complete assembled application.
