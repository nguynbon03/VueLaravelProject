# Work Breakdown Structure, Scope & Verification Gates (`docs/WBS.md`)

> Tài liệu hợp nhất **Phạm vi sản phẩm (`Spec Scope`)**, **Cấu trúc phân rã công việc (`WBS`)** và **Tiêu chí nghiệm thu tự động (`Acceptance & Verification Gates`)** — triệt tiêu hoàn toàn việc lặp lại giữa `SPEC.md` và `plan.md`.

---

## 1. Phạm Vi Hệ Thống (`Scope & Non-Goals`)

- **Trong phạm vi (`In-Scope`):**
  - Tách biệt 100% `backend/` (Laravel 13 Headless REST API `/api/v1`) và `frontend/` (Vue 3.5 + TypeScript + Pinia + Vue Router SPA — không dùng Inertia).
  - Đầy đủ 7 miền nghiệp vụ E-Commerce: Tài khoản RBAC, Danh mục SPU/SKU, Kho vận khóa dòng `FOR UPDATE` + giữ chỗ 15 phút, Khuyến mãi & Báo giá giỏ hàng, Đặt hàng `Idempotency-Key` + `Transactional Outbox`, Thanh toán VietQR/Stripe/COD + `HMAC Inbox`, Đánh giá mua thật & Admin Backoffice.
  - Đóng gói Docker Compose 9 dịch vụ tự thân (`backend`, `frontend`, `queue`, `outbox-relay`, `postgres`, `pgbouncer`, `redis`, `meilisearch`, `mailpit`).
- **Ngoài phạm vi giai đoạn 1 (`Out-of-Scope / Non-Goals`):**
  - Sàn đa nhà bán hàng chia doanh thu tự động (*Multi-vendor Split Payouts*).
  - Ứng dụng di động gốc Swift/Kotlin (đã sẵn sàng dùng chung `/api/v1/*`).
  - Kết nối máy quét mã vạch phần cứng kho ERP ngoại vi.

---

## 2. Bảng Phân Rã Công Việc Theo Chức Năng (`Master WBS`)

| Mã WBS | Hạng mục chức năng | Thư mục thực thi | Chuyên gia phụ trách | Trạng thái |
| :--- | :--- | :--- | :--- | :---: |
| **WBS-01** | Hạ tầng Docker 9 containers tự thân & Hợp đồng `OPENAPI_V1.md` | `docker-compose.yml`, `docs/api/` | `devops-automator` + `planner` | `[x]` |
| **WBS-02** | `SharedKernel`: `Money` VO, `Outbox`, `IdempotencyMiddleware`, `outbox:relay` | `backend/app/Core/SharedKernel/` | `software-architect` | `[x]` |
| **WBS-03** | Miền `Identity` (Sanctum Token, RBAC) & `Catalog` (Danh mục, SPU/SKU, Bộ lọc) | `backend/app/Modules/{Identity,Catalog}/` | `backend-architect` | `[x]` |
| **WBS-04** | Miền `Inventory` (Khóa `FOR UPDATE`, Giữ chỗ TTL 15p, Sổ cái nhập/xuất) | `backend/app/Modules/Inventory/` | `database-optimizer` | `[x]` |
| **WBS-05** | Miền `Promotion`, `Ordering` & `Payment` (VietQR/Stripe HMAC + `inbox_messages`) | `backend/app/Modules/{Promotion,Ordering,Payment}/` | `backend-architect` + `security-auditor` | `[x]` |
| **WBS-06** | Miền `ReviewAnalytics` (Verified Review, Thống kê GMV, Cảnh báo kho thấp) | `backend/app/Modules/ReviewAnalytics/` | `backend-architect` | `[x]` |
| **WBS-07** | Giao diện Storefront Vue 3.5 (Khám phá, Biến thể SKU, Giỏ hàng, VietQR Checkout) | `frontend/src/views/`, `frontend/src/modules/` | `frontend-developer` | `[x]` |
| **WBS-08** | Giao diện Admin Backoffice (`/admin`: KPI, Duyệt đơn, Tạo SKU, Chỉnh tồn kho) | `frontend/src/modules/admin/` | `frontend-developer` | `[x]` |
| **WBS-09** | Bộ kiểm thử kiến trúc `ArchitectureBoundaryTest` & Luồng E2E `15` kịch bản | `backend/tests/{Unit,Feature}/` | `security-auditor` + `code-reviewer` | `[x]` |

---

## 3. Tiêu Chí Nghiệm Thu (`Acceptance Criteria — Done Means`)

- [x] **Tách biệt tuyệt đối FE / BE:** `backend/composer.json` không chứa Inertia/Vue; `frontend/` biên dịch độc lập thành gói tĩnh qua `vue-tsc -b && vite build`.
- [x] **Ranh giới Clean Architecture bất khả xâm phạm:** `ArchitectureBoundaryTest` quét toàn bộ AST/import xác nhận `Domain/` thuần PHP 100% và không có module nào gọi chéo `Infrastructure` của module khác.
- [x] **An toàn tài chính & Tồn kho:** Gửi 2 request đặt hàng cùng `Idempotency-Key` chỉ tạo đúng 1 đơn và trừ kho 1 lần; đặt vượt tồn kho bị chặn sạch; gửi trùng Webhook cùng `event_id` bị chặn tại `inbox_messages`.
- [x] **Cấu trúc tài liệu sạch sau `git clone`:** Thư mục gốc chỉ giữ `README.md`, `AGENTS.md`, `CLAUDE.md`; toàn bộ đặc tả nằm đúng ngăn trong `docs/` và từng thư mục dịch vụ.

---

## 4. Lệnh Kiểm Chứng Tự Động (`Verification Commands — Must Exit 0`)

```bash
# 1. Kiểm chứng cấu hình Docker Compose (0 lỗi cú pháp / volume)
docker compose config --quiet

# 2. Kiểm chứng toàn bộ Architecture Test & Feature Flow Backend
cd backend && php artisan test

# 3. Kiểm chứng Strict TypeScript & Production Bundle Frontend
cd frontend && npm run build

# 4. Kiểm chứng các cổng chất lượng MyKit (Spec, Clean Arch, UI Anti-Slop, Security, DB)
node ~/.gemini/config/skills/spec-discovery/scripts/spec-gate.mjs .
node ~/.gemini/config/skills/backend-clean-arch/scripts/backend-gate.mjs .
node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs frontend/src --app
```
