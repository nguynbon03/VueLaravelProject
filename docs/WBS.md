# Full-Stack Work Breakdown Structure & System Acceptance Gates (`docs/WBS.md`)  
*(Cấu Trúc Phân Rã Công Việc & Nghiệm Thu Toàn Hệ Thống (`docs/WBS.md`))*

- **System-Level Scope (`Shared`)**: Governs cross-domain integration (`Infra <-> Backend <-> Frontend`) and end-to-end verification gates; see [../backend/docs/WBS.md](../backend/docs/WBS.md) and [../frontend/docs/WBS.md](../frontend/docs/WBS.md) for service-internal gates.  
  *(**Phạm vi cấp hệ thống (`Phần CHUNG`)**: Quản lý phạm vi tích hợp liên miền (`Infra <-> Backend <-> Frontend`) và các cổng kiểm chứng đầu-cuối; chi tiết từng miền xem tại [../backend/docs/WBS.md](../backend/docs/WBS.md) và [../frontend/docs/WBS.md](../frontend/docs/WBS.md).)*

---

## 1. System Scope & Non-Goals  
*(Phạm Vi Hệ Thống (`System Scope & Non-Goals`))*

- **In-Scope**:  
  *(**Trong phạm vi (`In-Scope`)**:)*

  - **Polyrepo-Ready Isolation**: `backend/` (Laravel 13 Pure Headless API, `0` `vite.config.js`) and `frontend/` (Enterprise Vue 3.5 + TypeScript + Axios + Pinia + SCSS) each own their `.env.example`, `Dockerfile`, `docker-compose.yml`, and internal docs.  
    *(**Tách riêng đúng chỗ (`Polyrepo-Ready`)**: `backend/` (Laravel 13 Pure Headless API, `0` `vite.config.js`) và `frontend/` (Enterprise Vue 3.5 + TypeScript + Axios + Pinia + SCSS) tự sở hữu `.env.example`, `Dockerfile`, `docker-compose.yml` và tài liệu nội bộ riêng.)*

  - **Unified System Matching**: Orchestrates all 9 containers via root `compose.yaml` (`include:`), maps all 22 endpoints 1:1 in `docs/API_CONTRACT.md`, and documents cross-domain transactional flows in `docs/ARCHITECTURE.md` (`0%` file duplication).  
    *(**Gộp chung đúng chỗ (`Unified System Matching`)**: Điều phối 9 containers bằng `compose.yaml` (`include:`) ở gốc; đối chiếu 1:1 toàn bộ 22 endpoints tại `docs/API_CONTRACT.md` và các luồng giao dịch xuyên miền tại `docs/ARCHITECTURE.md` (`0%` copy lặp file).)*

  - **High-Concurrency Resilience**: Integer `Money` (`BIGINT`), `ksort` + `SELECT ... FOR UPDATE` row locks, `Idempotency-Key`, `Transactional Outbox`, `HMAC-SHA256 Inbox`, underpayment rejection, and expired-reservation late-payment protection (`refund_required`).  
    *(**Chống sập thực chiến tải cao**: Tiền số nguyên `Money` (`BIGINT`), khóa dòng `ksort` + `SELECT ... FOR UPDATE`, `Idempotency-Key`, `Transactional Outbox`, `HMAC-SHA256 Inbox`, chống chuyển thiếu tiền và xử lý chuyển khoản trễ quá hạn giữ chỗ (`refund_required`).)*

- **Out-of-Scope / Non-Goals**:  
  *(**Ngoài phạm vi giai đoạn hiện tại (`Out-of-Scope / Non-Goals`)**:)*

  - Multi-vendor marketplace split payouts and native iOS/Android mobile apps (which can later consume `docs/API_CONTRACT.md` directly).  
    *(Sàn đa nhà bán hàng chia doanh thu tự động (*Multi-vendor Split Payouts*) và ứng dụng di động gốc iOS/Android.)*

---

## 2. Master Full-Stack WBS  
*(Bảng Phân Rã Công Việc Liên Miền (`Master Full-Stack WBS`))*

| WBS ID | Integration Deliverable | Scope (`Shared` / `Private`) | Target Path | Status |
| :--- | :--- | :---: | :--- | :---: |
| **SYS-01** | Docker Compose v2 `include:` orchestration (`9` containers on shared bridge network) | **SHARED** | `compose.yaml` | `[x]` |
| **SYS-02** | End-to-end architecture & `API_CONTRACT.md` (`22` endpoints mapped 1:1) | **SHARED** | `docs/{LAW,ARCHITECTURE,API_CONTRACT,WBS}.md` | `[x]` |
| **BE-01** | `SharedKernel` (`Money` VO, `Outbox`, `IdempotencyMiddleware`, `outbox:relay`) | **PRIVATE (`BE`)** | `backend/app/Core/SharedKernel/` | `[x]` |
| **BE-02** | 7 Hexagonal Bounded Contexts (`Identity`, `Catalog`, `Inventory`, `Promotion`, `Ordering`, `Payment`, `ReviewAnalytics`) | **PRIVATE (`BE`)** | `backend/app/Modules/*` | `[x]` |
| **BE-03** | `ArchitectureBoundaryTest` & `19` transaction/security tests | **PRIVATE (`BE`)** | `backend/tests/{Unit,Feature}/` | `[x]` |
| **FE-01** | Enterprise Vue 3 scaffold (`assets/styles`, `components/{base,common}`, `composables`, `layouts`, `router`, `services`, `stores`, `utils`) | **PRIVATE (`FE`)** | `frontend/src/*` | `[x]` |
| **FE-02** | 3 UI feature modules (`modules/auth`, `modules/customer`, `modules/admin`) mapped 1:1 to 22 endpoints | **PRIVATE (`FE`)** | `frontend/src/modules/*` | `[x]` |

---

## 3. System Acceptance Criteria (`Done Means`)  
*(Tiêu Chí Nghiệm Thu Toàn Hệ Thống (`Acceptance Criteria — Done Means`))*

- [x] **Zero Duplication**: Only one canonical `docs/API_CONTRACT.md` exists at root, and `compose.yaml` uses `include:` instead of duplicating `backend/` and `frontend/` service definitions.  
  *(**Không lặp cấu hình & không lặp tài liệu (`Zero Duplication`)**: Chỉ có đúng 1 bản hợp đồng `docs/API_CONTRACT.md` ở gốc; `compose.yaml` dùng `include:` thay vì chép lại dịch vụ của `backend/` và `frontend/`.)*

- [x] **Standalone Ready**: Running `docker compose config --quiet` at root, inside `backend/`, and inside `frontend/` all exit `0`.  
  *(**Tự chủ hoàn toàn khi tách lẻ (`Standalone Ready`)**: Chạy `docker compose config --quiet` tại gốc, tại `backend/` và tại `frontend/` đều trả về `exit 0`.)*

- [x] **1:1 UI-to-DB Alignment**: Every method in `frontend/src/services/*.ts` matches the exact route, HTTP verb, and payload schema of `backend/routes/api.php`, including `refund_required` on late bank transfers.  
  *(**Khớp nối 1:1 từ UI tới DB**: Mọi hàm trong `frontend/src/services/*.ts` gọi đúng đường dẫn, method và kiểu dữ liệu của `backend/routes/api.php`, xử lý đầy đủ cả trạng thái `refund_required` khi chuyển khoản trễ.)*

- [x] **100% Green Automated Quality Gates**: Laravel Pint (`passed`), PHPUnit (`19/19 passed, 238 assertions`), `vue-tsc -b && vite build` (`exit 0`), `spec-gate.mjs`, `backend-gate.mjs`, and `fe-gate.mjs` (`0 FAIL`).  
  *(**Xanh 100% các cổng chất lượng tự động**: Laravel Pint (`passed`), PHPUnit (`19/19 passed, 238 assertions`), `vue-tsc -b && vite build` (`exit 0`), `spec-gate.mjs`, `backend-gate.mjs`, và `fe-gate.mjs` (`0 FAIL`).)*

---

## 4. End-to-End Verification Commands (`Must Exit 0`)  
*(Lệnh Kiểm Chứng Đầu-Cuối (`Verification Commands — Must Exit 0`))*

```bash
# 1. Verify Docker Compose config at SHARED root and PRIVATE services (BE, FE)
docker compose config --quiet
(cd backend && docker compose config --quiet)
(cd frontend && docker compose config --quiet)

# 2. Verify Backend code style (Pint) + Architecture & Feature Tests
(cd backend && composer lint && composer test)

# 3. Verify Frontend strict TypeScript + Production Bundle
(cd frontend && npm run build)

# 4. Verify MyKit gates (Spec, Clean Arch, UI Anti-Slop, Bilingual Audit)
node ~/.mykit/skills/spec-discovery/scripts/spec-gate.mjs .
node ~/.mykit/skills/backend-clean-arch/scripts/backend-gate.mjs .
node ~/.mykit/skills/fe-anti-slop/scripts/fe-gate.mjs frontend/src --app
node ~/.gemini/config/plugins/MyKit/scripts/bilingual-md-audit.mjs . --strict
```
