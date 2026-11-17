# CleanCommerce Backend — Standalone Headless REST API Repository (`AGENTS.md` / `CLAUDE.md`)

> **Independent Backend Repo:** Pure Laravel 13 Headless REST API (`/api/v1/*`). Zero Vite, zero Blade UI, zero `package.json`.

## 1. Documentation Map (`./docs/`)
| Path | Role |
| :--- | :--- |
| [docs/LAW.md](./docs/LAW.md) | Hexagonal 4-ring laws, Integer Money, Sorted `lockForUpdate()`, Outbox & OWASP BOLA |
| [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) | 8-Container Docker topology, 7 Bounded Contexts & Multi-Flow Edge-Case diagrams |
| [docs/WBS.md](./docs/WBS.md) | Scope, Master WBS, Acceptance Criteria & Verification Gates |
| [docs/api/OPENAPI_V1.md](./docs/api/OPENAPI_V1.md) | Canonical 22-endpoint JSON REST contract consumed by the standalone Frontend repo |

## 2. Exact Commands
- **Docker Up:** `cp -n .env.example .env && docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`
- **Local Dev (`:8080`):** `php artisan migrate:fresh --seed && php artisan serve --port=8080`
- **Tests (`exit 0`):** `php artisan test`
- **Docker Config Check:** `docker compose config --quiet`

## 3. Hard Guardrails
- `app/Modules/*/Domain/**` must remain 100% pure PHP (zero `Illuminate\*` / Eloquent).
- Never re-introduce `vite.config.js`, `package.json`, or `resources/{js,css}` into this pure API repo (enforced by `ArchitectureBoundaryTest`).
