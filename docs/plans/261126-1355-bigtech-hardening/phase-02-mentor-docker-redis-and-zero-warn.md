# Phase 02: Mentor Second-Brain Lessons (`Multi-Stage Dockerfile`, `Redis maxclients=10000`, `0-Warn UI`)  
*(Giai Đoạn 02: Áp Dụng Bài Học Mentor Từ Second Brain (`Multi-Stage Dockerfile`, `Redis maxclients=10000`, `0-Warn UI`))*

- **Done means**:  
  *(**Hoàn thành khi**:)*

  - [x] `backend/Dockerfile` implements a 3-stage build (`base` -> `dev` & lean non-root `prod` with `composer install --no-dev --classmap-authoritative`, `USER appuser`, and `HEALTHCHECK`), and `backend/docker-compose.yml` targets `dev`.  
        *(Tệp `backend/Dockerfile` chia 3 tầng (`base` -> `dev` & bản `prod` tối giản chạy `USER appuser` không root, `--no-dev --classmap-authoritative` và `HEALTHCHECK`), đồng thời `backend/docker-compose.yml` trỏ vào `target: dev`.)*

  - [x] `backend/docker-compose.yml` configures `ecom_redis` with `--maxclients 10000`, `--tcp-keepalive 60`, and `ulimits: nofile: 65535` (from `@anonystick` canon lesson).  
        *(Tệp `backend/docker-compose.yml` cấu hình `ecom_redis` với `--maxclients 10000`, `--tcp-keepalive 60` và `ulimits: nofile: 65535` theo đúng bài học thực chiến của `@anonystick`.)*

  - [x] `UserManageView.vue` and `DemoCredentialsCard.vue` use `nowrap` button labels so `fe-gate.mjs` passes with `0 FAIL, 0 WARN` across all 4 linters.  
        *(Hai tệp `UserManageView.vue` và `DemoCredentialsCard.vue` dùng nhãn nút gọn kèm `nowrap` để `fe-gate.mjs` đạt `0 FAIL, 0 WARN` trên cả 4 bộ Linter.)*

  - [x] All stale local Docker containers (`ecom_*`) and dangling images are cleaned so the user can build fresh from the project terminal.  
        *(Toàn bộ các container cũ (`ecom_*`) và image rác trong Docker được dọn sạch để người dùng tự chạy lệnh build mới tinh trong terminal dự án.)*

- **Verify**: `docker compose config --quiet && node "$HOME/.mykit/skills/fe-anti-slop/scripts/fe-gate.mjs" frontend/src --app && node "$HOME/.mykit/skills/devops-deploy/scripts/devops-gate.mjs" .`  
  *(**Lệnh kiểm chứng**: `docker compose config --quiet && node "$HOME/.mykit/skills/fe-anti-slop/scripts/fe-gate.mjs" frontend/src --app && node "$HOME/.mykit/skills/devops-deploy/scripts/devops-gate.mjs" .`)*

- **Evaluator**: MyKit deterministic gates (`0 FAIL, 0 WARN`).  
  *(**Người đánh giá**: Bộ cổng kiểm chứng tất định MyKit (`0 FAIL, 0 WARN`).)*

## Resume From Here  
*(Tiếp Tục Từ Đây)*

- Phase 02 completed and verified (`exit 0`).  
  *(Giai đoạn 02 đã hoàn thành và kiểm chứng đạt `exit 0`.)*
