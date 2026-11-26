# CleanCommerce Backend — Standalone Headless REST API (`backend/AGENTS.md`)  
*(Dịch Vụ Backend Độc Lập — Headless REST API (`backend/AGENTS.md`))*

- **Standalone Backend Scope**: Laravel 13 Pure Headless API (`/api/v1/*`) with zero `vite.config.js`, `package.json`, or frontend assets.  
  *(**Phần RIÊNG của Backend**: Laravel 13 Pure Headless API (`/api/v1/*`), không chứa `vite.config.js`, `package.json` hay tài liệu giao diện thừa.)*

---

## 1. Documentation Map (`Private` vs `Shared`)  
*(Bản Đồ Tài Liệu (`Riêng` vs `Chung`))*

- **Backend Internal Laws (`Private`)**: [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md)  
  *(**Luật nội bộ Backend (`Riêng`)**: [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md))*

- **Shared API Contract & Integration Map (`Shared`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)  
  *(**Hợp đồng API & Sơ đồ khớp nối FE<->BE (`Chung`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md))*

---

## 2. Standalone Commands (`cd backend`)  
*(Lệnh Thực Thi Độc Lập (`cd backend`))*

- **Start standalone 8-container Backend stack**: `cp -n .env.example .env && docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`  
  *(**Bật cụm 8 Containers riêng Backend**: `cp -n .env.example .env && docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`)*

- **Fast local SQLite dev server (`:8080`)**: `php artisan migrate:fresh --seed && php artisan serve --port=8080`  
  *(**Chạy nhanh Local (`:8080`)**: `php artisan migrate:fresh --seed && php artisan serve --port=8080`)*

- **Verify code style & test suite (`exit 0`)**: `docker compose config --quiet && composer lint && composer test`  
  *(**Kiểm tra chuẩn Code & Test (`exit 0`)**: `docker compose config --quiet && composer lint && composer test`)*

---

## 3. Immutable Guardrails  
*(Chốt Chặn Bất Biến)*

- Keep `app/Modules/*/Domain/**` 100% Pure PHP (never import `Illuminate\*` or Eloquent).  
  *(Giữ `app/Modules/*/Domain/**` nguyên vẹn 100% Pure PHP, không import `Illuminate\*` hay Eloquent.)*

- Never add `vite.config.js`, `package.json`, or cross-module `Infrastructure` imports (`ArchitectureBoundaryTest` fails immediately).  
  *(Tuyệt đối không thêm `vite.config.js`, `package.json`, hoặc gọi chéo `Infrastructure` giữa các Module (`ArchitectureBoundaryTest` sẽ chặn ngay).)*
