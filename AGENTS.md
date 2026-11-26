# CleanCommerce Workspace Router (`AGENTS.md` / `CLAUDE.md`)  
*(Bộ Điều Hướng Không Gian Làm Việc CleanCommerce)*

- **Shared Root (`./`)**: `compose.yaml` (`include:`) + `docs/{LAW.md, ARCHITECTURE.md, API_CONTRACT.md, WBS.md}` — maps Frontend, Backend, and Infrastructure 1:1 with zero duplicated files.  
  *(**Phần Chung (`./`)**: `compose.yaml` (`include:`) + `docs/{LAW.md, ARCHITECTURE.md, API_CONTRACT.md, WBS.md}` — đối chiếu 1:1 cách Frontend, Backend và Hạ tầng khớp nối mà không lặp file.)*

- **Isolated Services (`backend/`, `frontend/`)**: Each service owns its `.env.example`, `Dockerfile`, `docker-compose.yml`, and internal docs, ready to split into two standalone repos at any time.  
  *(**Phần Riêng (`backend/`, `frontend/`)**: Mỗi dịch vụ tự sở hữu `.env.example`, `Dockerfile`, `docker-compose.yml` và tài liệu nội bộ, sẵn sàng tách thành 2 repo độc lập bất kỳ lúc nào.)*

| Architecture Zone | Role & Stack | Primary Entrypoints & Docs |
| :--- | :--- | :--- |
| **SHARED ROOT (`./`)** | Docker `include:` (`9` containers) + 1:1 FE<->BE Integration + `22` REST Endpoints | [compose.yaml](./compose.yaml) · [docs/LAW.md](./docs/LAW.md) · [docs/ARCHITECTURE.md](./docs/ARCHITECTURE.md) · [docs/API_CONTRACT.md](./docs/API_CONTRACT.md) · [docs/WBS.md](./docs/WBS.md) |
| **BACKEND (`backend/`)** | Laravel 13 Pure Headless API (`0` `vite.config.js`), FrankenPHP 8.4, 7 Hexagonal DDD Modules, Outbox/Inbox | [backend/AGENTS.md](./backend/AGENTS.md) · [backend/docs/LAW.md](./backend/docs/LAW.md) · [backend/docs/MODULE_MAP.md](./backend/docs/MODULE_MAP.md) |
| **FRONTEND (`frontend/`)** | Enterprise Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS (`3` Layouts, `14` Views) | [frontend/AGENTS.md](./frontend/AGENTS.md) · [frontend/docs/DESIGN.md](./frontend/docs/DESIGN.md) · [frontend/docs/WBS.md](./frontend/docs/WBS.md) |

---

## Quick Run & Verification Commands (`Must Exit 0`)  
*(Lệnh Chạy & Kiểm Chứng Nhanh (`Must Exit 0`))*

- **Full-stack from root**: `docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`  
  *(Chạy chung toàn hệ thống từ gốc: `docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`)*

- **Standalone Backend (`cd backend`)**: `docker compose up -d --build` · `composer lint && composer test`  
  *(Chạy/Test riêng Backend (`cd backend`): `docker compose up -d --build` · `composer lint && composer test`)*

- **Standalone Frontend (`cd frontend`)**: `docker compose up -d --build` · `npm run build`  
  *(Chạy/Build riêng Frontend (`cd frontend`): `docker compose up -d --build` · `npm run build`)*
