# CleanCommerce Frontend — Standalone Enterprise Vue 3.5 SPA (`frontend/AGENTS.md`)  
*(Dịch Vụ Frontend Độc Lập — Enterprise Vue 3.5 SPA (`frontend/AGENTS.md`))*

- **Standalone Frontend Scope**: Self-contained Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS application with its own `.env.example`, `Dockerfile`, and `docker-compose.yml`.  
  *(**Phần RIÊNG của Frontend**: Ứng dụng độc lập Vue 3.5 + TypeScript + Axios + Pinia + Vue Router 4 + SCSS với `.env.example`, `Dockerfile` và `docker-compose.yml` riêng.)*

---

## 1. Documentation Map (`Private` vs `Shared`)  
*(Bản Đồ Tài Liệu (`Riêng` vs `Chung`))*

- **Frontend Design & Internal Structure (`Private`)**: [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md)  
  *(**Thiết kế & Cấu trúc nội bộ Frontend (`Riêng`)**: [docs/DESIGN.md](./docs/DESIGN.md) · [docs/WBS.md](./docs/WBS.md))*

- **22-Endpoint API Contract & Integration Flow (`Shared`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md)  
  *(**Hợp đồng API 22 Endpoints & Luồng tích hợp (`Chung`)**: [../docs/API_CONTRACT.md](../docs/API_CONTRACT.md) · [../docs/ARCHITECTURE.md](../docs/ARCHITECTURE.md))*

---

## 2. Unidirectional Data Flow Inside `src/`  
*(Quy Luật Luồng Dữ Liệu Một Chiều Trong `src/`)*

- Flow order: `layouts/` -> `modules/{auth,customer,admin}/views/` -> `composables/` -> `stores/` -> `services/*.service.ts` -> `services/axiosClient.ts`  
  *(Thứ tự luồng: `layouts/` -> `modules/{auth,customer,admin}/views/` -> `composables/` -> `stores/` -> `services/*.service.ts` -> `services/axiosClient.ts`)*

- Never call raw `axios` or `fetch` directly inside `.vue` components.  
  *(Tuyệt đối không viết lệnh `axios` hay `fetch` trực tiếp bên trong `.vue` components.)*

- Format all monetary amounts consistently via `src/utils/formatCurrency.ts` paired with `tabular-nums`.  
  *(Định dạng tiền tệ thống nhất qua `src/utils/formatCurrency.ts` kèm `tabular-nums`.)*

---

## 3. Standalone Commands (`cd frontend`)  
*(Lệnh Thực Thi Độc Lập (`cd frontend`))*

- **Start standalone Frontend container (`:5173`)**: `cp -n .env.example .env && docker compose up -d --build`  
  *(**Bật Container riêng Frontend (`:5173`)**: `cp -n .env.example .env && docker compose up -d --build`)*

- **Run local dev server**: `npm run dev`  
  *(**Chạy Local Dev**: `npm run dev`)*

- **Verify TypeScript, Build & Anti-Slop (`exit 0`)**: `docker compose config --quiet && npm run build && node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`  
  *(**Kiểm tra TypeScript, Build & Anti-Slop (`exit 0`)**: `docker compose config --quiet && npm run build && node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app`)*
