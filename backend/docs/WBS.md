# Backend Standalone Work Breakdown Structure & Acceptance Gates (`backend/docs/WBS.md`)  
*(Cấu Trúc Công Việc & Cổng Nghiệm Thu Riêng Backend (`backend/docs/WBS.md`))*

## 1. Internal Scope (`Backend Scope`)  
*(Phạm Vi Nội Bộ (`Backend Scope`))*

- **In-Scope**: Laravel 13 Pure Headless REST API (`/api/v1/*`), 8 services in `backend/docker-compose.yml` (`api`, `queue`, `outbox-relay`, `pgbouncer`, `postgres`, `redis`, `meilisearch`, `mailpit`), 7 Hexagonal Bounded Contexts + `SharedKernel`.  
  *(**Trong phạm vi (`In-Scope`)**: Laravel 13 Pure Headless REST API (`/api/v1/*`), 8 dịch vụ trong `backend/docker-compose.yml` (`api`, `queue`, `outbox-relay`, `pgbouncer`, `postgres`, `redis`, `meilisearch`, `mailpit`), 7 Hexagonal Bounded Contexts + `SharedKernel`.)*

- **Out-of-Scope**: Any HTML/CSS/JS views, Vite, Blade UI, or Node.js packages.  
  *(**Ngoài phạm vi (`Out-of-Scope`)**: Mọi giao diện HTML/CSS/JS, Vite, Blade UI hoặc thư viện Node.js.)*

---

## 2. Work Breakdown Items (`Backend WBS`)  
*(Hạng Mục Thực Thi (`Backend WBS`))*

| ID | Deliverable | Internal Path | Status |
| :--- | :--- | :--- | :---: |
| **BE-01** | FrankenPHP 8.4 `Dockerfile` & standalone 8-container `docker-compose.yml` | `./Dockerfile`, `./docker-compose.yml` | `[x]` |
| **BE-02** | `SharedKernel` (`Money` VO, `Outbox`, `IdempotencyMiddleware`, `AdminRoleMiddleware`, `FreshBearerTokenMiddleware`) | `./app/Core/SharedKernel/` | `[x]` |
| **BE-03** | 7 Hexagonal Modules (`Identity`, `Catalog`, `Inventory`, `Promotion`, `Ordering`, `Payment`, `ReviewAnalytics`) | `./app/Modules/` | `[x]` |
| **BE-04** | AST `ArchitectureBoundaryTest` + Transaction, BOLA & Late-Webhook test suites | `./tests/{Unit,Feature}/` | `[x]` |

---

## 3. Acceptance Criteria (`Done Means`)  
*(Tiêu Chí Nghiệm Thu (`Acceptance Criteria — Done Means`))*

- [x] `Domain/` is 100% Pure PHP (`0` `Illuminate\*`), zero cross-module `Infrastructure` imports, and zero `vite.config.js` / `package.json` files.  
  *(`Domain/` hoàn toàn thuần PHP (`0` `Illuminate\*`), không gọi chéo `Infrastructure` giữa các Module, và không tồn tại `vite.config.js` / `package.json`.)*

- [x] Passes 100% of Laravel Pint formatting checks (`composer lint`) and all `19/19` tests (`238` assertions).  
  *(Vượt qua 100% kiểm tra định dạng chuẩn Laravel Pint (`composer lint`) và toàn bộ `19/19` bài test (`238` assertions).)*

---

## 4. Standalone Verification Commands (`Must Exit 0` Inside `backend/`)  
*(Lệnh Kiểm Chứng Độc Lập (`Must Exit 0` Khi Đứng Trong `backend/`))*

```bash
docker compose config --quiet
composer lint
composer test
```
