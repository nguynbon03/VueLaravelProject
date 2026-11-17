# CleanCommerce Backend — Standalone Headless REST API (`backend/AGENTS.md`)

> **Phần RIÊNG của Backend:** Laravel 13 Pure Headless API (`/api/v1/*`). Không chứa `vite.config.js`, `package.json` hay tài liệu giao diện thừa.

## 1. Bản Đồ Tài Liệu (`Riêng` vs `Chung`)
- **Luật nội bộ Backend (`Riêng`):** [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md)
- **Hợp đồng API & Sơ đồ khớp nối FE<->BE (`Chung`):** [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)

## 2. Lệnh Thực Thi Độc Lập (`cd backend`)
- **Bật cụm 8 Containers riêng Backend:** `cp -n .env.example .env && docker compose up -d --build && docker compose exec api php artisan migrate --seed --force`
- **Chạy nhanh Local (`:8080`):** `php artisan migrate:fresh --seed && php artisan serve --port=8080`
- **Kiểm tra chuẩn Code & Test (`exit 0`):** `docker compose config --quiet && composer lint && composer test`

## 3. Chốt Chặn Bất Biến
- `app/Modules/*/Domain/**` giữ nguyên 100% Pure PHP (không import `Illuminate\*` / Eloquent).
- Tuyệt đối không thêm `vite.config.js`, `package.json`, hoặc gọi chéo `Infrastructure` giữa các Module (`ArchitectureBoundaryTest` sẽ chặn ngay).
