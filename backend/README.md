# CleanCommerce Backend API (Laravel 13 + FrankenPHP + Hexagonal Clean Architecture)

Thư mục **RIÊNG** của dịch vụ Backend — tự chứa đầy đủ `.env.example`, `Dockerfile`, `docker-compose.yml` (8 containers) và luật kiến trúc nội bộ, thuần 100% JSON REST API (`0` `vite.config.js`).

## 1. Khởi chạy độc lập bằng Docker (`8 Containers`)
```bash
cp -n .env.example .env
docker compose up -d --build
docker compose exec api php artisan migrate --seed --force
```
- **REST API:** `http://localhost:8080/api/v1/health` · **Mailpit:** `http://localhost:8025` · **Meilisearch:** `http://localhost:7700`

## 2. Khởi chạy nhanh Local (SQLite) & Kiểm thử
```bash
composer lint
composer test
php artisan migrate:fresh --seed && php artisan serve --port=8080
```

## 3. Liên kết Tài liệu
- **Nội bộ Backend (`Riêng`):** [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md)
- **Tích hợp Toàn hệ thống (`Chung`):** [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)
