# Cấu Trúc Phân Rã Công Việc & Nghiệm Thu Toàn Hệ Thống (`docs/WBS.md`)

> Tài liệu cấp hệ thống (**Phần CHUNG**) quản lý phạm vi tích hợp, phân rã hạng mục liên miền (`Infra <-> Backend <-> Frontend`) và các cổng kiểm chứng đầu-cuối.
> Chi tiết kiểm chứng nội bộ từng miền xem tại [backend/docs/WBS.md](../backend/docs/WBS.md) và [frontend/docs/WBS.md](../frontend/docs/WBS.md).

---

## 1. Phạm Vi Hệ Thống (`System Scope & Non-Goals`)

- **Trong phạm vi (`In-Scope`):**
  - **Tách riêng đúng chỗ (`Polyrepo-Ready`):** `backend/` (Laravel 13 Pure Headless API, `0` `vite.config.js`) và `frontend/` (Enterprise Vue 3.5 + TypeScript + Axios + Pinia + SCSS) tự sở hữu `.env.example`, `Dockerfile`, `docker-compose.yml` và tài liệu nội bộ riêng.
  - **Gộp chung đúng chỗ (`Unified System Matching`):** Điều phối 9 containers bằng `compose.yaml` (`include:`) ở gốc; đối chiếu 1:1 toàn bộ 22 endpoints tại `docs/API_CONTRACT.md` và các luồng giao dịch xuyên miền tại `docs/ARCHITECTURE.md` (`0%` copy lặp file giữa hai thư mục).
  - **Chống sập thực chiến tải cao:** Tiền số nguyên `Money` (`BIGINT`), khóa dòng `ksort` + `SELECT ... FOR UPDATE`, `Idempotency-Key`, `Transactional Outbox`, `HMAC-SHA256 Inbox`, chống chuyển thiếu tiền và xử lý chuyển khoản trễ quá hạn giữ chỗ (`refund_required`).
- **Ngoài phạm vi giai đoạn hiện tại (`Out-of-Scope / Non-Goals`):**
  - Sàn đa nhà bán hàng chia doanh thu tự động (*Multi-vendor Split Payouts*).
  - Ứng dụng di động gốc iOS/Android (đã sẵn sàng dùng chung hợp đồng `docs/API_CONTRACT.md`).

---

## 2. Bảng Phân Rã Công Việc Liên Miền (`Master Full-Stack WBS`)

| Mã WBS | Hạng mục tích hợp | Phạm vi (`Chung` / `Riêng`) | Đường dẫn thực thi | Trạng thái |
| :--- | :--- | :---: | :--- | :---: |
| **SYS-01** | Điều phối Docker Compose v2 `include:` (`9` containers trên mạng cầu chung) | **CHUNG** | `compose.yaml` | `[x]` |
| **SYS-02** | Kiến trúc khớp nối đầu-cuối & Hợp đồng `API_CONTRACT.md` (`22` endpoints 1:1) | **CHUNG** | `docs/{ARCHITECTURE,API_CONTRACT,WBS}.md` | `[x]` |
| **BE-01** | `SharedKernel` (`Money` VO, `Outbox`, `IdempotencyMiddleware`, `outbox:relay`) | **RIÊNG (`BE`)** | `backend/app/Core/SharedKernel/` | `[x]` |
| **BE-02** | 7 Bounded Contexts Hexagonal (`Identity`, `Catalog`, `Inventory`, `Promotion`, `Ordering`, `Payment`, `ReviewAnalytics`) | **RIÊNG (`BE`)** | `backend/app/Modules/*` | `[x]` |
| **BE-03** | Kiểm thử biên kiến trúc `ArchitectureBoundaryTest` & `19` bài test giao dịch/bảo mật | **RIÊNG (`BE`)** | `backend/tests/{Unit,Feature}/` | `[x]` |
| **FE-01** | Khung chuẩn Enterprise Vue 3 (`assets/styles`, `components/{base,common}`, `composables`, `layouts`, `router`, `services`, `stores`, `utils`) | **RIÊNG (`FE`)** | `frontend/src/*` | `[x]` |
| **FE-02** | 3 Phân hệ giao diện (`modules/auth`, `modules/customer`, `modules/admin`) khớp 1:1 với 22 endpoints | **RIÊNG (`FE`)** | `frontend/src/modules/*` | `[x]` |

---

## 3. Tiêu Chí Nghiệm Thu Toàn Hệ Thống (`Acceptance Criteria — Done Means`)

- [x] **Không lặp cấu hình & không lặp tài liệu (`Zero Duplication`):** Chỉ có đúng 1 bản hợp đồng `docs/API_CONTRACT.md` ở gốc; `compose.yaml` dùng `include:` thay vì chép lại dịch vụ của `backend/` và `frontend/`.
- [x] **Tự chủ hoàn toàn khi tách lẻ (`Standalone Ready`):** Chạy `docker compose config --quiet` tại gốc, tại `backend/` và tại `frontend/` đều trả về `exit 0`.
- [x] **Khớp nối 1:1 từ UI tới DB:** Mọi hàm trong `frontend/src/services/*.ts` gọi đúng đường dẫn, method và kiểu dữ liệu của `backend/routes/api.php`, xử lý đầy đủ cả trạng thái `refund_required` khi chuyển khoản trễ.
- [x] **Xanh 100% các cổng chất lượng tự động:** Laravel Pint (`passed`), PHPUnit (`19/19 passed, 238 assertions`), `vue-tsc -b && vite build` (`exit 0`), `spec-gate.mjs`, `backend-gate.mjs`, và `fe-gate.mjs` (`0 FAIL`).

---

## 4. Lệnh Kiểm Chứng Đầu-Cuối (`Verification Commands — Must Exit 0`)

```bash
# 1. Kiểm chứng cấu hình Docker ở cả cấp CHUNG (Root) và 2 cấp RIÊNG (BE, FE)
docker compose config --quiet
(cd backend && docker compose config --quiet)
(cd frontend && docker compose config --quiet)

# 2. Kiểm chứng Code Style (Pint) + Architecture & Feature Tests của Backend
(cd backend && composer lint && composer test)

# 3. Kiểm chứng Strict TypeScript + Production Bundle của Frontend
(cd frontend && npm run build)

# 4. Kiểm chứng các cổng chất lượng MyKit (Spec, Clean Arch, UI Anti-Slop)
node ~/.gemini/config/skills/spec-discovery/scripts/spec-gate.mjs . --strict
node ~/.gemini/config/skills/backend-clean-arch/scripts/backend-gate.mjs .
node ~/.gemini/config/skills/fe-anti-slop/scripts/fe-gate.mjs frontend/src --app
```
