# CleanCommerce Backend API (Laravel 13 + FrankenPHP + Hexagonal Clean Architecture)  
*(Dịch Vụ Backend CleanCommerce — Laravel 13 + FrankenPHP + Kiến Trúc Lục Giác)*

Standalone Backend service directory — self-contained with `.env.example`, `Dockerfile`, `docker-compose.yml` (8 containers), and internal architectural laws, serving a 100% pure JSON REST API (`0` `vite.config.js`).  
*(Thư mục **RIÊNG** của dịch vụ Backend — tự chứa đầy đủ `.env.example`, `Dockerfile`, `docker-compose.yml` (8 containers) và luật kiến trúc nội bộ, thuần 100% JSON REST API (`0` `vite.config.js`).)*

---

## 1. Run Standalone via Docker (`8 Containers`)  
*(Khởi Chạy Độc Lập Bằng Docker (`8 Containers`))*

```bash
cp -n .env.example .env
docker compose up -d --build
docker compose exec api php artisan migrate --seed --force
```

- **REST API**: `http://localhost:8080/api/v1/health` · **Mailpit**: `http://localhost:8025` · **Meilisearch**: `http://localhost:7700`  
  *(**REST API**: `http://localhost:8080/api/v1/health` · **Mailpit**: `http://localhost:8025` · **Meilisearch**: `http://localhost:7700`)*

---

## 2. Fast Local Execution (SQLite) & Testing  
*(Khởi Chạy Nhanh Local (SQLite) & Kiểm Thử)*

```bash
composer lint
composer test
php artisan migrate:fresh --seed && php artisan serve --port=8080
```

---

## 3. Documentation Links  
*(Liên Kết Tài Liệu)*

- **Backend Internal (`Private`)**: [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md)  
  *(**Nội bộ Backend (`Riêng`)**: [docs/LAW.md](./docs/LAW.md) · [docs/MODULE_MAP.md](./docs/MODULE_MAP.md) · [docs/WBS.md](./docs/WBS.md))*

- **Full-Stack Integration (`Shared`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)  
  *(**Tích hợp Toàn hệ thống (`Chung`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md))*
