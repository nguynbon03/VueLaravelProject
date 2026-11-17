# CleanCommerce Workspace Router (`AGENTS.md` / `CLAUDE.md`)

> **Hybrid Polyrepo-Ready + Docker `include:` Workspace:** `backend/` and `frontend/` are 100% self-contained (each owns its `.env.example`, `Dockerfile`, `docker-compose.yml`, and `docs/`), unified at workspace root via Docker Compose v2 `compose.yaml` (`include:`).

| Workspace | Stack & Architecture | Scoped Agent & Docs Hub |
| :--- | :--- | :--- |
| **`backend/`** | Pure Headless Laravel 13 API (`/api/v1/*`), FrankenPHP, 7 Hexagonal DDD Modules, Outbox, Idempotency | [backend/AGENTS.md](./backend/AGENTS.md) · [backend/docs/ARCHITECTURE.md](./backend/docs/ARCHITECTURE.md) · [backend/docs/LAW.md](./backend/docs/LAW.md) |
| **`frontend/`** | Enterprise Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS (`3` Layouts) | [frontend/AGENTS.md](./frontend/AGENTS.md) · [frontend/docs/DESIGN.md](./frontend/docs/DESIGN.md) · [frontend/docs/ARCHITECTURE.md](./frontend/docs/ARCHITECTURE.md) |
| **`compose.yaml`** | Zero-duplication Docker Compose v2 `include:` uniting `backend` + `frontend` on `cleancommerce-shared-net` | `docker compose up -d --build` |

## Commands
- **Full-Stack Docker (from root):** `docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`
- **Backend Only (`cd backend`):** `docker compose up -d --build` · `composer test` · `composer lint`
- **Frontend Only (`cd frontend`):** `docker compose up -d --build` · `npm run build`
