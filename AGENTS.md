# CleanCommerce — Universal Agent Entrypoint (`AGENTS.md` / `CLAUDE.md`)

> **Cross-Agent Compatible:** Read automatically by Claude Code (`CLAUDE.md`), OpenAI Codex, Antigravity, Cursor, Windsurf, and OpenCode after `git clone`. Keep under 80 lines; deep specs live in `docs/` and workspace folders.

## 1. Repository Topology (Strict FE / BE Separation — Zero Inertia)

| Path | Responsibility | Scoped Manual to Read First |
| :--- | :--- | :--- |
| `backend/` | Laravel 13 Headless REST API (`/api/v1/*`), FrankenPHP, 7 Hexagonal DDD Modules | [backend/AGENTS.md](./backend/AGENTS.md) |
| `frontend/` | Standalone Vue 3.5 + TypeScript + Pinia + Vue Router SPA (`:5173`) | [frontend/AGENTS.md](./frontend/AGENTS.md) & [frontend/DESIGN.md](./frontend/DESIGN.md) |
| `docs/LAW.md` | Non-negotiable Clean Architecture, Integer Money, Stock Lock & OWASP rules | [docs/LAW.md](./docs/LAW.md) |
| `docs/ARCHITECTURE.md` | 9-Container Docker topology, Bounded Context map & Outbox sequence diagrams | [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) |
| `docs/WBS.md` | Scope boundaries, Work Breakdown Structure, Acceptance Criteria & Quality Gates | [docs/WBS.md](./docs/WBS.md) |
| `docs/api/OPENAPI_V1.md` | Canonical JSON REST contract shared between `frontend/` and `backend/` | [docs/api/OPENAPI_V1.md](./docs/api/OPENAPI_V1.md) |

## 2. Exact Commands (Run Scoped to Your Change — Never Guess)
- **Validate Docker Topology:** `docker compose config --quiet`
- **Boot Full Docker Stack:** `cp -n .env.example .env && docker compose up -d --build && docker compose exec backend php artisan migrate --seed --force`
- **Backend Tests (Arch + E2E):** `cd backend && php artisan test`
- **Backend Local Dev (`:8080`):** `cd backend && php artisan migrate --seed && php artisan serve --port=8080`
- **Frontend Typecheck & Build:** `cd frontend && npm run build`
- **Frontend Local Dev (`:5173`):** `cd frontend && npm run dev -- --port=5173`

## 3. Top 5 Non-Negotiable Guardrails (Full Details in `docs/LAW.md`)
1. **Zero FE/BE Coupling:** Never add Inertia/Livewire/Blade UI to `backend/`. All FE<->BE changes must update `docs/api/OPENAPI_V1.md` first.
2. **Pure Domain Layer:** `backend/app/Modules/*/Domain/**` must remain 100% pure PHP (zero `Illuminate\*` / Eloquent imports — enforced by `ArchitectureBoundaryTest`).
3. **Port-Only Cross-Module Calls:** Module `A` never imports `Modules\B\Infrastructure\*`; bind contracts in `ModulesServiceProvider` and emit side effects via `outbox_messages`.
4. **Integer Money & Row Locks:** Currency uses integer minor units (`Money` VO, never `float`); stock deductions use `DB::transaction()` + `lockForUpdate()`.
5. **Clean Root Discipline:** Never drop ad-hoc `.md`, `.sql`, or `docker/` folders at repository root; co-locate Docker configs in each service's `Dockerfile` and docs in `docs/`.
