# Cấu Trúc Công Việc & Cổng Nghiệm Thu Riêng Backend (`backend/docs/WBS.md`)

## 1. Phạm Vi Nội Bộ (`Backend Scope`)
- **Trong phạm vi (`In-Scope`):** Laravel 13 Pure Headless REST API (`/api/v1/*`), 8 dịch vụ trong `backend/docker-compose.yml` (`api`, `queue`, `outbox-relay`, `pgbouncer`, `postgres`, `redis`, `meilisearch`, `mailpit`), 7 Hexagonal Bounded Contexts + `SharedKernel`.
- **Ngoài phạm vi (`Out-of-Scope`):** Mọi giao diện HTML/CSS/JS, Vite, Blade UI hoặc thư viện Node.js.

## 2. Hạng Mục Thực Thi (`Backend WBS`)
| Mã | Hạng mục | Đường dẫn nội bộ | Trạng thái |
| :--- | :--- | :--- | :---: |
| **BE-01** | `Dockerfile` FrankenPHP 8.4 & `docker-compose.yml` 8 containers độc lập | `./Dockerfile`, `./docker-compose.yml` | `[x]` |
| **BE-02** | `SharedKernel` (`Money` VO, `Outbox`, `IdempotencyMiddleware`, `AdminRoleMiddleware`, `FreshBearerTokenMiddleware`) | `./app/Core/SharedKernel/` | `[x]` |
| **BE-03** | 7 Modules Hexagonal (`Identity`, `Catalog`, `Inventory`, `Promotion`, `Ordering`, `Payment`, `ReviewAnalytics`) | `./app/Modules/` | `[x]` |
| **BE-04** | Kiểm thử kiến trúc AST `ArchitectureBoundaryTest` + Luồng giao dịch, BOLA & Late-Webhook | `./tests/{Unit,Feature}/` | `[x]` |

## 3. Tiêu Chí Nghiệm Thu (`Acceptance Criteria — Done Means`)
- [x] `Domain/` hoàn toàn thuần PHP (`0` `Illuminate\*`), không gọi chéo `Infrastructure` giữa các Module, và không tồn tại `vite.config.js` / `package.json`.
- [x] Vượt qua 100% kiểm tra định dạng chuẩn Laravel Pint (`composer lint`) và toàn bộ `19/19` bài test (`238` assertions).

## 4. Lệnh Kiểm Chứng Độc Lập (`Must Exit 0` Khi Đứng Trong `backend/`)
```bash
docker compose config --quiet
composer lint
composer test
```
