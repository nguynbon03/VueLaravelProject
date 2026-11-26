# Frontend Standalone Work Breakdown Structure & Acceptance Gates (`frontend/docs/WBS.md`)  
*(Cấu Trúc Công Việc & Cổng Nghiệm Thu Riêng Frontend (`frontend/docs/WBS.md`))*

## 1. Internal Scope (`Frontend Scope`)  
*(Phạm Vi Nội Bộ (`Frontend Scope`))*

- **In-Scope**: Standalone Single-Page Application built with Vue 3.5 + TypeScript + Pinia + Vue Router 4 + Axios + SCSS (`sass-embedded`), packaged via `frontend/Dockerfile` & `frontend/docker-compose.yml`.  
  *(**Trong phạm vi (`In-Scope`)**: Ứng dụng đơn trang độc lập Vue 3.5 + TypeScript + Pinia + Vue Router 4 + Axios + SCSS (`sass-embedded`) đóng gói qua `frontend/Dockerfile` & `frontend/docker-compose.yml`.)*

- **Out-of-Scope**: No Inertia/Blade coupling and no direct database access; all data calls go through `src/services/*.ts` matching [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md).  
  *(**Ngoài phạm vi (`Out-of-Scope`)**: Không phụ thuộc Inertia/Blade, không kết nối trực tiếp DB; mọi giao tiếp dữ liệu đi qua `src/services/*.ts` theo hợp đồng chung [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md).)*

---

## 2. Work Breakdown Items (`Frontend WBS`)  
*(Hạng Mục Thực Thi (`Frontend WBS`))*

| ID | Deliverable | Internal Path | Status |
| :--- | :--- | :--- | :---: |
| **FE-01** | SCSS design tokens (`variables.scss`, `main.scss`) & foundational components (`base/`, `common/`) | `./src/assets/`, `./src/components/` | `[x]` |
| **FE-02** | HTTP client `axiosClient.ts` + 4 domain services (`auth`, `product`, `cart`, `order`) | `./src/services/`, `./src/types/` | `[x]` |
| **FE-03** | Pinia state stores (`stores/`) + shared hooks (`composables/`) + helpers (`utils/`) | `./src/stores/`, `./src/composables/`, `./src/utils/` | `[x]` |
| **FE-04** | 3 application `layouts/` & role-guarded `router/` (`requiresAdmin` guard) | `./src/layouts/`, `./src/router/` | `[x]` |
| **FE-05** | 3 feature modules `modules/{auth,customer,admin}` (14 views & full VietQR / late-payment refund flows) | `./src/modules/` | `[x]` |

---

## 3. Acceptance Criteria (`Done Means`)  
*(Tiêu Chí Nghiệm Thu (`Acceptance Criteria — Done Means`))*

- [x] The `src/` directory tree strictly follows the Enterprise Vue 3 layered architecture standard.  
  *(Cây thư mục `src/` tuân thủ chính xác chuẩn phân tầng Enterprise Vue 3.)*

- [x] `docker compose config --quiet`, `npm run build` (`vue-tsc -b && vite build`), and `fe-gate.mjs src --app` all exit `0` (`0 FAIL, 0 REFLEX`).  
  *(`docker compose config --quiet`, `npm run build` (`vue-tsc -b && vite build`) và cổng `fe-gate.mjs src --app` đều đạt `exit 0` (`0 FAIL, 0 REFLEX`).)*

---

## 4. Standalone Verification Commands (`Must Exit 0` Inside `frontend/`)  
*(Lệnh Kiểm Chứng Độc Lập (`Must Exit 0` Khi Đứng Trong `frontend/`))*

```bash
docker compose config --quiet
npm run build
node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app
```
