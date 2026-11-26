# CleanCommerce Frontend SPA (Enterprise Vue 3.5 + TypeScript + Pinia + Axios + SCSS)  
*(Ứng Dụng Frontend CleanCommerce — Enterprise Vue 3.5 + TypeScript + Pinia + Axios + SCSS)*

Standalone Frontend service directory — self-contained with `.env.example`, `Dockerfile`, `docker-compose.yml`, and the layered `src/` tree (`assets`, `components`, `composables`, `layouts`, `modules`, `router`, `services`, `stores`, `types`, `utils`).  
*(Thư mục **RIÊNG** của dịch vụ Frontend — tự chứa đầy đủ `.env.example`, `Dockerfile`, `docker-compose.yml` và chuẩn phân tầng `src/` (`assets`, `components`, `composables`, `layouts`, `modules`, `router`, `services`, `stores`, `types`, `utils`).)*

---

## 1. Run Standalone via Docker  
*(Khởi Chạy Độc Lập Bằng Docker)*

```bash
cp -n .env.example .env
docker compose up -d --build
```

- **Storefront & Admin UI**: `http://localhost:5173`  
  *(**Giao diện Storefront & Admin**: `http://localhost:5173`)*

---

## 2. Run Locally & Verify Production Build  
*(Khởi Chạy Local & Kiểm Tra Build)*

```bash
cp -n .env.example .env
npm install
npm run dev
npm run build
```

---

## 3. Documentation Links  
*(Liên Kết Tài Liệu)*

- **Frontend Internal (`Private`)**: [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md)  
  *(**Nội bộ Frontend (`Riêng`)**: [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md))*

- **Full-Stack Integration (`Shared`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)  
  *(**Tích hợp Toàn hệ thống (`Chung`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md))*
