# Plan: Big Tech Polyrepo, Docker Compose, Outbox & Frontend Hardening  
*(Kế hoạch: Chuẩn Hóa Kiến Trúc Polyrepo, Docker Compose, Outbox & Frontend Theo Chuẩn Big Tech)*

- **Created**: 2026-11-26 13:55  
  *(**Ngày tạo**: 26/11/2026 13:55)*

- **Mode**: `SOLO-INDIE` (Full enterprise safety floor enabled)  
  *(**Chế độ**: `SOLO-INDIE` — giữ nguyên toàn bộ sàn an toàn cấp doanh nghiệp)*

- **Approval Brief**: [approval-brief.md](./approval-brief.md)  
  *(**Tóm tắt phê duyệt**: [approval-brief.md](./approval-brief.md))*

- **Purpose**: Resolve all findings from the MyKit + Orca Claude Opus 5.5 audit and apply Second Brain Mentor (`@anonystick`) production rules across workspace hygiene (`.gitignore`, `.dockerignore`), Docker multi-stage build (`base`/`dev`/`prod` non-root + `x-backend-env` anchor + `pgbouncer` healthcheck + Redis `maxclients=10000`), Backend Outbox event dispatching (`SKIP LOCKED` + `Event::dispatch`), and Frontend `0-WARN` UI + `axiosClient.ts` `401` Pinia store reset.  
  *(**Mục đích**: Xử lý triệt để toàn bộ phát hiện từ đợt kiểm toán MyKit + Orca Claude Opus 5.5 và áp dụng các bài học thực chiến của Mentor (`@anonystick`) trong Second Brain trên cả 4 tầng: vệ sinh kho mã nguồn (`.gitignore`, `.dockerignore`), Docker 3 tầng (`base`/`dev`/`prod` non-root + neo `x-backend-env` + `healthcheck` cho `pgbouncer` + Redis `maxclients=10000`), Backend Outbox (`SKIP LOCKED` + `Event::dispatch`), và Frontend `0-WARN` UI + `axiosClient.ts` xóa state `401`.)*

---

## Phases  
*(Các Giai Đoạn Thực Thi)*

- [x] [phase-01-docker-outbox-fe-hardening.md](./phase-01-docker-outbox-fe-hardening.md) — Add `.gitignore` & `.dockerignore`, harden `docker-compose.yml`, fix `EloquentOutboxAdapter::dispatchPending`, and sync `axiosClient.ts` `401` state reset.  
  *(Thêm `.gitignore` & `.dockerignore`, chuẩn hóa `docker-compose.yml`, sửa `EloquentOutboxAdapter::dispatchPending` và đồng bộ xóa state `401` tại `axiosClient.ts`.)*

- [x] [phase-02-mentor-docker-redis-and-zero-warn.md](./phase-02-mentor-docker-redis-and-zero-warn.md) — Upgrade `backend/Dockerfile` to 3-stage non-root build, add Redis `--maxclients 10000` + `ulimits: 65535`, eliminate all 3 Frontend `hallmark` warnings, and wipe old Docker containers/images.  
  *(Nâng cấp `backend/Dockerfile` lên 3 tầng non-root, thêm `--maxclients 10000` + `ulimits: 65535` cho Redis, xóa sạch 3 cảnh báo `hallmark` ở Frontend và dọn sạch container/image cũ trong Docker.)*

---

## Resume From Here  
*(Tiếp Tục Từ Đây)*

- Active phase: Completed (`phase-01` and `phase-02` verified exit `0`).  
  *(Giai đoạn hiện tại: Đã hoàn thành (`phase-01` và `phase-02` đều đã kiểm chứng đạt exit `0`).)*

---

## Execution Log  
*(Nhật Ký Thực Thi)*

- `2026-11-26 13:55`: Plan locked after live EN+VI+OSS research and Orca Claude Opus 5.5 (`ctx_d13ad61a5d92`) audit.  
  *(Đã khóa kế hoạch sau khi hoàn tất nghiên cứu EN+VI+OSS và nhận báo cáo kiểm toán từ Orca Claude Opus 5.5 (`ctx_d13ad61a5d92`).)*

- `2026-11-26 13:58`: Phase 01 completed and verified (`spec-gate.mjs`, `backend-gate.mjs`, `fe-gate.mjs`, `devops-gate.mjs`, `bilingual-md-audit.mjs --strict`, and `docker compose config --quiet` all exited `0`).  
  *(Đã hoàn thành và kiểm chứng Giai đoạn 01 (`spec-gate.mjs`, `backend-gate.mjs`, `fe-gate.mjs`, `devops-gate.mjs`, `bilingual-md-audit.mjs --strict` và `docker compose config --quiet` đều đạt `exit 0`).)*

- `2026-11-26 14:08`: Phase 02 completed and verified (`backend/Dockerfile` 3-stage non-root, Redis `maxclients=10000`, `fe-gate.mjs` `0 FAIL, 0 WARN`, and old `ecom_*` Docker containers/images wiped clean).  
  *(Đã hoàn thành và kiểm chứng Giai đoạn 02 (`backend/Dockerfile` 3 tầng non-root, Redis `maxclients=10000`, `fe-gate.mjs` đạt `0 FAIL, 0 WARN`, và đã dọn sạch các container/image `ecom_*` cũ trong Docker).)*

- `2026-11-26 14:32`: Added `pull_policy: missing` on pre-cached infrastructure images (`postgres`, `pgbouncer`, `redis`, `meilisearch`, `mailpit`) in `backend/docker-compose.yml` to bypass Docker Hub token handshake timeouts.  
  *(Đã thêm `pull_policy: missing` cho 5 image hạ tầng đã lưu sẵn trên máy (`postgres`, `pgbouncer`, `redis`, `meilisearch`, `mailpit`) trong `backend/docker-compose.yml` để bỏ qua bước gọi mạng lên Docker Hub gây timeout.)*
