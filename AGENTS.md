# CleanCommerce Workspace Router (`AGENTS.md` / `CLAUDE.md`)

> **Nguyên tắc `Chung vs Riêng` (Zero Duplication):**
> - **PHẦN CHUNG (Root):** `compose.yaml` (`include:`) + `docs/{ARCHITECTURE.md, API_CONTRACT.md, WBS.md}` — nơi đối chiếu 1:1 cách Frontend, Backend và Hạ tầng khớp nối với nhau mà không copy lặp file sang hai nơi.
> - **PHẦN RIÊNG (`backend/`, `frontend/`):** Mỗi dịch vụ tự sở hữu `.env.example`, `Dockerfile`, `docker-compose.yml` và tài liệu nội bộ chuyên biệt, sẵn sàng tách thành 2 repo độc lập bất kỳ lúc nào.

| Vùng Kiến Trúc | Vai trò & Công nghệ | Điểm điều hướng & Tài liệu chính |
| :--- | :--- | :--- |
| **PHẦN CHUNG (`./`)** | Điều phối Docker `include:` (`9` containers) + Sơ đồ khớp nối FE<->BE + Hợp đồng `22` REST Endpoints | [compose.yaml](./compose.yaml) · [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) · [docs/API_CONTRACT.md](./docs/API_CONTRACT.md) · [docs/WBS.md](./docs/WBS.md) |
| **PHẦN RIÊNG (`backend/`)** | Laravel 13 Pure Headless API (`0` `vite.config.js`), FrankenPHP 8.4, 7 Modules Hexagonal DDD, Outbox/Inbox | [backend/AGENTS.md](./backend/AGENTS.md) · [backend/docs/LAW.md](./backend/docs/LAW.md) · [backend/docs/MODULE_MAP.md](./backend/docs/MODULE_MAP.md) |
| **PHẦN RIÊNG (`frontend/`)** | Enterprise Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS (`3` Layouts, `14` Views) | [frontend/AGENTS.md](./frontend/AGENTS.md) · [frontend/docs/DESIGN.md](./frontend/docs/DESIGN.md) · [frontend/docs/WBS.md](./frontend/docs/WBS.md) |

## Lệnh Chạy & Kiểm Chứng Nhanh (`Must Exit 0`)
- **Chạy chung toàn hệ thống từ gốc:** `docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`
- **Chạy/Test riêng Backend (`cd backend`):** `docker compose up -d --build` · `composer lint && composer test`
- **Chạy/Build riêng Frontend (`cd frontend`):** `docker compose up -d --build` · `npm run build`
