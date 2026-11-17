# CleanCommerce Backend API (Laravel 13 + FrankenPHP + Hexagonal Clean Architecture)

Repository Backend độc lập 100% (thuần JSON REST API `/api/v1/*`, không chứa `vite.config.js` hay bất kỳ công cụ build Frontend nào).

## 1. Khởi chạy bằng Docker
```bash
cp -n .env.example .env
docker compose up -d --build
docker compose exec api php artisan migrate --seed --force
```
- API Endpoint: `http://localhost:8080/api/v1/health`
- Mailpit UI: `http://localhost:8025` · Meilisearch: `http://localhost:7700`

## 2. Khởi chạy Local nhanh (SQLite)
```bash
php artisan migrate:fresh --seed
php artisan serve --port=8080
```

## 3. Kiểm thử tự động
```bash
php artisan test
```
- Tài liệu chi tiết nằm tại `./docs/{LAW.md, ARCHITECTURE.md, WBS.md, api/OPENAPI_V1.md}`.
