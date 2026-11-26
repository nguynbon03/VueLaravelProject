# Phase 01: Workspace Hygiene, Docker Compose, Transactional Outbox & Frontend Hardening  
*(Giai Đoạn 01: Chuẩn Hóa Vệ Sinh Kho Mã Nguồn, Docker Compose, Transactional Outbox & Frontend)*

- **Done means**:  
  *(**Hoàn thành khi**:)*

  - [x] Root `.gitignore`, `backend/.dockerignore`, and `frontend/.dockerignore` exist and exclude `.serena/`, `graphify-out/`, `.git`, `.env*`, `vendor/`, and `node_modules/`.  
        *(Các tệp `.gitignore` ở gốc, `backend/.dockerignore` và `frontend/.dockerignore` tồn tại và loại trừ `.serena/`, `graphify-out/`, `.git`, `.env*`, `vendor/` cùng `node_modules/`.)*

  - [x] `backend/docker-compose.yml` shares one `image: cleancommerce-backend:local` + `x-backend-env` anchor across `api`, `queue`, and `outbox-relay`, pins `pgbouncer` with a TCP `healthcheck`, and includes `pgbouncer` in `depends_on:`.  
        *(Tệp `backend/docker-compose.yml` dùng chung 1 `image: cleancommerce-backend:local` và biến neo `x-backend-env` cho `api`, `queue`, `outbox-relay`, khóa phiên bản `pgbouncer` kèm `healthcheck` và đưa `pgbouncer` vào `depends_on:`.)*

  - [x] `EloquentOutboxAdapter::dispatchPending` uses `FOR UPDATE SKIP LOCKED` on PostgreSQL/MySQL and dispatches each event via Laravel's `Event::dispatch` before marking `dispatched_at`.  
        *(Hàm `EloquentOutboxAdapter::dispatchPending` sử dụng `FOR UPDATE SKIP LOCKED` trên PostgreSQL/MySQL và bắn sự kiện qua `Event::dispatch` trước khi đánh dấu `dispatched_at`.)*

  - [x] `frontend/src/services/axiosClient.ts` uses `API_BASE_URL` in network-error messages and emits `cleancommerce:unauthorized` on `401` so `auth.store.ts` clears in-memory Pinia session state immediately.  
        *(Tệp `frontend/src/services/axiosClient.ts` dùng `API_BASE_URL` trong thông báo lỗi mạng và phát sự kiện `cleancommerce:unauthorized` khi gặp `401` để `auth.store.ts` xóa ngay trạng thái Pinia trên RAM.)*

- **Verify**: `docker compose config --quiet && node "$HOME/.mykit/skills/spec-discovery/scripts/spec-gate.mjs" . && node "$HOME/.gemini/config/plugins/MyKit/scripts/bilingual-md-audit.mjs" . --strict`  
  *(**Lệnh kiểm chứng**: `docker compose config --quiet && node "$HOME/.mykit/skills/spec-discovery/scripts/spec-gate.mjs" . && node "$HOME/.gemini/config/plugins/MyKit/scripts/bilingual-md-audit.mjs" . --strict`)*

- **Evaluator**: MyKit deterministic gates + Orca Claude Opus 5.5 (`ctx_d13ad61a5d92`) verification.  
  *(**Người đánh giá**: Bộ cổng tất định MyKit + kiểm chứng độc lập từ Orca Claude Opus 5.5 (`ctx_d13ad61a5d92`).)*

## Resume From Here  
*(Tiếp Tục Từ Đây)*

- Phase 01 completed and verified (`exit 0`).  
  *(Giai đoạn 01 đã hoàn thành và kiểm chứng đạt `exit 0`.)*
