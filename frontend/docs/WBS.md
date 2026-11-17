# Frontend Work Breakdown Structure & Verification Gates (`frontend/docs/WBS.md`)

## 1. Scope Boundary
- **In-Scope:** Standalone Vue 3.5 + TypeScript SPA structured by `assets/`, `components/{base,common}`, `composables/`, `layouts/`, `modules/{auth,customer,admin}`, `router/`, `services/axiosClient`, `stores/`, and `utils/`.
- **Out-of-Scope (Non-Goals):** Server-side Inertia/Blade coupling or direct database connections.

## 2. Master WBS
| ID | Deliverable | Target Path | Status |
| :--- | :--- | :--- | :---: |
| **FE-01** | SCSS tokens (`variables.scss`, `main.scss`) & Base/Common UI components | `src/assets/`, `src/components/` | `[x]` |
| **FE-02** | Axios client with interceptors + domain services (`auth`, `product`, `order`, `cart`) | `src/services/` | `[x]` |
| **FE-03** | Pinia stores (`auth.store`, `cart.store`, `ui.store`) & Composables | `src/stores/`, `src/composables/` | `[x]` |
| **FE-04** | Layouts (`Customer`, `Admin`, `Auth`) & split Router (`customer.routes`, `admin.routes`) | `src/layouts/`, `src/router/` | `[x]` |
| **FE-05** | Auth, Customer Storefront (6 views) & Admin Dashboard (6 views incl. Products/Orders/Users) | `src/modules/{auth,customer,admin}/` | `[x]` |

## 3. Acceptance Criteria (`Done Means`)
- [x] Directory tree matches the team's Enterprise Vue 3 architecture 1:1.
- [x] `docker compose config --quiet`, `npm run build` (`vue-tsc -b && vite build`), and `fe-gate.mjs src --app` all exit `0`.

## 4. Verification Commands (`Must Exit 0`)
- `docker compose config --quiet`
- `npm run build`
- `node ~/.gemini/config/skills/spec-discovery/scripts/spec-gate.mjs .`
- `node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`
