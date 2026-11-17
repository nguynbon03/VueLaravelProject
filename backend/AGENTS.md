# Backend Agent Guide (`backend/AGENTS.md`)

> Scoped instructions for AI agents working inside `backend/` (Laravel 13 Headless Clean Architecture API).

## 1. Module Structure
- `app/Core/SharedKernel`: `Money` integer ValueObject, `DomainEvent`, `OutboxPort` -> `EloquentOutboxAdapter`, `IdempotencyMiddleware`, `OutboxRelayCommand`.
- `app/Modules/{Identity,Catalog,Inventory,Promotion,Ordering,Payment,ReviewAnalytics}`:
  - `Domain/`: Pure PHP 8.4+ (`readonly` classes, Value Objects, Port interfaces). **Zero `Illuminate\*` or Eloquent imports.**
  - `Application/`: Single-purpose `*Action.php` use cases & DTOs.
  - `Infrastructure/`: Eloquent Models & `*Adapter.php` bound in `app/Providers/ModulesServiceProvider.php`.
  - `UI/Http/Controllers/`: Thin JSON controllers matching `../docs/api/OPENAPI_V1.md`.

## 2. Scoped Verification Commands (Must Exit `0`)
- Run Architecture & E2E Feature suite: `php artisan test`
- Reset & seed local database: `php artisan migrate:fresh --seed`
- Process pending Outbox events & sweep expired stock holds: `php artisan outbox:relay --once`
