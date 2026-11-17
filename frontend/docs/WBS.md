# Cấu Trúc Công Việc & Cổng Nghiệm Thu Riêng Frontend (`frontend/docs/WBS.md`)

## 1. Phạm Vi Nội Bộ (`Frontend Scope`)
- **Trong phạm vi (`In-Scope`):** Ứng dụng đơn trang độc lập Vue 3.5 + TypeScript + Pinia + Vue Router 4 + Axios + SCSS (`sass-embedded`) đóng gói qua `frontend/Dockerfile` & `frontend/docker-compose.yml`.
- **Ngoài phạm vi (`Out-of-Scope`):** Không phụ thuộc Inertia/Blade, không kết nối trực tiếp DB; mọi giao tiếp dữ liệu đi qua `src/services/*.ts` theo hợp đồng chung [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md).

## 2. Hạng Mục Thực Thi (`Frontend WBS`)
| Mã | Hạng mục | Đường dẫn nội bộ | Trạng thái |
| :--- | :--- | :--- | :---: |
| **FE-01** | Hệ biến SCSS (`variables.scss`, `main.scss`) & bộ Component nền (`base/`, `common/`) | `./src/assets/`, `./src/components/` | `[x]` |
| **FE-02** | Tầng giao tiếp `axiosClient.ts` + 4 dịch vụ miền (`auth`, `product`, `cart`, `order`) | `./src/services/`, `./src/types/` | `[x]` |
| **FE-03** | Quản lý trạng thái Pinia (`stores/`) + Hooks dùng chung (`composables/`) + Helpers (`utils/`) | `./src/stores/`, `./src/composables/`, `./src/utils/` | `[x]` |
| **FE-04** | 3 Khung `layouts/` & bộ định tuyến phân quyền `router/` (`requiresAdmin` guard) | `./src/layouts/`, `./src/router/` | `[x]` |
| **FE-05** | 3 Phân hệ `modules/{auth,customer,admin}` (14 màn hình & đầy đủ luồng VietQR / Hoàn tiền trễ) | `./src/modules/` | `[x]` |

## 3. Tiêu Chí Nghiệm Thu (`Acceptance Criteria — Done Means`)
- [x] Cây thư mục `src/` tuân thủ chính xác chuẩn phân tầng Enterprise Vue 3.
- [x] `docker compose config --quiet`, `npm run build` (`vue-tsc -b && vite build`) và cổng `fe-gate.mjs src --app` đều đạt `exit 0` (`0 FAIL, 0 REFLEX`).

## 4. Lệnh Kiểm Chứng Độc Lập (`Must Exit 0` Khi Đứng Trong `frontend/`)
```bash
docker compose config --quiet
npm run build
node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs src --app
```
