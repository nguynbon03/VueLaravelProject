# CleanCommerce Frontend SPA (Enterprise Vue 3.5 + TypeScript + Pinia + Axios + SCSS)

Thư mục **RIÊNG** của dịch vụ Frontend — tự chứa đầy đủ `.env.example`, `Dockerfile`, `docker-compose.yml` và chuẩn phân tầng `src/` (`assets`, `components`, `composables`, `layouts`, `modules`, `router`, `services`, `stores`, `utils`).

## 1. Khởi chạy độc lập bằng Docker
```bash
cp -n .env.example .env
docker compose up -d --build
```
- **Giao diện Storefront & Admin:** `http://localhost:5173`

## 2. Khởi chạy Local & Kiểm tra Build
```bash
cp -n .env.example .env
npm install
npm run dev
npm run build
```

## 3. Liên kết Tài liệu
- **Nội bộ Frontend (`Riêng`):** [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md)
- **Tích hợp Toàn hệ thống (`Chung`):** [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)
