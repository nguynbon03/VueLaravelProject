# CleanCommerce Frontend — Standalone Enterprise Vue 3.5 SPA (`frontend/AGENTS.md`)

> **Phần RIÊNG của Frontend:** Ứng dụng độc lập Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS với `.env.example`, `Dockerfile` và `docker-compose.yml` riêng.

## 1. Bản Đồ Tài Liệu (`Riêng` vs `Chung`)
- **Thiết kế & Cấu trúc nội bộ Frontend (`Riêng`):** [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md)
- **Hợp đồng API 22 Endpoints & Luồng tích hợp (`Chung`):** [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)

## 2. Quy Luật Luồng Dữ Liệu Một Chiều Trong `src/`
`layouts/` -> `modules/{auth,customer,admin}/views/` -> `composables/` -> `stores/` -> `services/*.service.ts` -> `services/axiosClient.ts`
- Tuyệt đối không viết lệnh `axios` hay `fetch` trực tiếp bên trong `.vue` components.
- Định dạng tiền tệ thống nhất qua `src/utils/formatCurrency.ts` kèm `tabular-nums`.

## 3. Lệnh Thực Thi Độc Lập (`cd frontend`)
- **Bật Container riêng Frontend (`:5173`):** `cp -n .env.example .env && docker compose up -d --build`
- **Chạy Local Dev:** `npm run dev`
- **Kiểm tra TypeScript, Build & Anti-Slop (`exit 0`):** `docker compose config --quiet && npm run build && node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`
