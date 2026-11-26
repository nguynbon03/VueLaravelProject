# Approval Brief: Big Tech Polyrepo, Docker Multi-Stage & Outbox Hardening  
*(Bản Tóm Tắt Phê Duyệt: Chuẩn Hóa Polyrepo, Docker Đa Tầng & Outbox Theo Chuẩn Big Tech)*

- **Status**: `approved` (Executed per user directive)  
  *(**Trạng thái**: `approved` — Đã thi công theo yêu cầu trực tiếp của người dùng)*

- **Mode**: `SOLO-INDIE` (Full enterprise safety floor enabled)  
  *(**Chế độ**: `SOLO-INDIE` — Bật đầy đủ sàn an toàn cấp doanh nghiệp)*

---

## 1. Outcome & Plain Why  
*(Kết Quả Đạt Được & Lý Do Thực Tế Dễ Hiểu)*

- 🇬🇧 Eliminate silent Outbox event loss, prevent Docker build bloat (`1-image-3-workers` + `.dockerignore` + 3-stage `backend/Dockerfile`), protect Redis from `maxclients` connection starvation (`--maxclients 10000` + `ulimits: 65535`), and synchronize Frontend `401` Pinia store resets.  
  🇻🇳 *(Chấm dứt lỗi rơi sự kiện ngầm ở bảng Outbox, chống phình dung lượng Docker (`1-image-3-workers` + `.dockerignore` + `backend/Dockerfile` 3 tầng), bảo vệ Redis khỏi sập kết nối giờ cao điểm (`--maxclients 10000` + `ulimits: 65535`), và đồng bộ xóa Pinia Store trên RAM khi gặp lỗi `401`.)*

---

## 2. Real Failure Scenarios Prevented  
*(Các Kịch Bản Sự Cố Thực Tế Đã Ngăn Chặn)*

- 🇬🇧 **Scenario A (`Outbox Silent Drop`)**: Previously, `EloquentOutboxAdapter::dispatchPending` set `dispatched_at = now()` without calling `Event::dispatch`, losing `OrderPlaced` / `OrderPaid` events.  
  🇻🇳 *(**Kịch bản A (`Rơi sự kiện Outbox ngầm`)**: Trước đây `EloquentOutboxAdapter::dispatchPending` chỉ đánh dấu `dispatched_at = now()` mà chưa gọi `Event::dispatch`, làm mất sự kiện đơn hàng.)*

- 🇬🇧 **Scenario B (`@anonystick 11 PM Redis maxclients Timeout`)**: Without explicit `--maxclients 10000` and `ulimits: nofile: 65535`, high-concurrency bursts can exhaust default file descriptors and trigger `RedisCommandTimeoutException`.  
  🇻🇳 *(**Kịch bản B (`Sự cố 11h đêm Redis maxclients từ bài học @anonystick`)**: Nếu không khóa rõ `--maxclients 10000` và `ulimits: nofile: 65535`, tải cao đột biến có thể làm cạn cổng kết nối và gây lỗi `RedisCommandTimeoutException`.)*

---

## 3. Blind-Spots & Trade-offs  
*(Điểm Mù & Sự Đánh Đổi)*

- 🇬🇧 Reusing `image: cleancommerce-backend:local` across `api`, `queue`, and `outbox-relay` requires `api` to build first (`depends_on: api: condition: service_started`).  
  🇻🇳 *(Việc tái sử dụng `image: cleancommerce-backend:local` cho cả `api`, `queue` và `outbox-relay` yêu cầu dịch vụ `api` phải được đúc trước (`depends_on: api`).)*

---

## 4. Micro-Lesson (`MentorLens`)  
*(Bài Học Nhỏ Đắt Giá (`MentorLens`))*

- 🇬🇧 Separate the heavy factory build stage (`dev`/`builder`) from the lean runtime delivery container (`prod` + non-root `appuser`), and never mark an outbox task `dispatched_at` until the downstream event bus confirms receipt.  
  🇻🇳 *(Luôn tách biệt công trường lắp ráp (`dev`/`builder`) khỏi thùng hàng vận hành thực tế (`prod` + `appuser` không dùng quyền root), và không bao giờ đánh dấu `dispatched_at` khi băng chuyền phía sau chưa thực sự nhận được gói tin.)*
