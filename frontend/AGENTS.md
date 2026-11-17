# CleanCommerce Frontend — Standalone Vue 3.5 Enterprise SPA (`AGENTS.md` / `CLAUDE.md`)

> **Independent Frontend Repo:** Standalone Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS repository with its own `.env`, `Dockerfile`, `docker-compose.yml`, and `docs/`.

## 1. Canonical `src/` Folder Standard
- `src/assets/{images/, styles/{variables.scss, main.scss}}` — Global SCSS tokens & reset
- `src/components/{base/, common/}` — Reusable UI primitives (`BaseButton`, `BaseInput`, `BaseModal`) & shared widgets (`Pagination`, `LoadingSpinner`, `ToastContainer`)
- `src/composables/{useAuth.ts, useCart.ts, useNotification.ts}` — Composition API hooks
- `src/layouts/{CustomerLayout.vue, AdminLayout.vue, AuthLayout.vue}` — Role-specific shell layouts
- `src/modules/{auth/, customer/, admin/}` — Domain modules (`components/` + `views/`)
- `src/router/{index.ts, customer.routes.ts, admin.routes.ts}` — Split route definitions & `requiresAdmin` guard
- `src/services/{axiosClient.ts, auth.service.ts, product.service.ts, order.service.ts, cart.service.ts}` — Axios HTTP layer with Bearer & error interceptors
- `src/stores/{auth.store.ts, cart.store.ts, ui.store.ts}` — Pinia state stores
- `src/utils/{formatCurrency.ts, formatDate.ts, validators.ts}` — Pure TypeScript helpers

## 2. Documentation Map (`./docs/`)
- [docs/DESIGN.md](./docs/DESIGN.md) — Calm Daylight 60-30-10 design tokens & Anti-Slop rules
- [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) — Frontend layered data flow (`View -> Composable/Store -> Service -> axiosClient`)
- [docs/WBS.md](./docs/WBS.md) — Scope, WBS & Verification Gates
- [docs/api/OPENAPI_V1.md](./docs/api/OPENAPI_V1.md) — REST API v1 contract

## 3. Exact Commands (`Must Exit 0`)
- **Docker Up:** `cp -n .env.example .env && docker compose up -d --build`
- **Local Dev (`:5173`):** `npm run dev`
- **Typecheck & Production Build:** `npm run build`
- **UI Anti-Slop Gate:** `node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`
