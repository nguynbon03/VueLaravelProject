# CleanCommerce — Laravel 13 Hexagonal Clean Architecture API + Enterprise Vue 3.5 SPA

Nền tảng E-Commerce kết hợp trọn vẹn **hai chuẩn mực thực chiến**:
1. **Chuẩn Polyrepo Độc Lập (Team / Tech Lead Standard):** `backend/` và `frontend/` tự chứa đầy đủ `.env.example`, `Dockerfile`, `docker-compose.yml` và `docs/` riêng biệt — sẵn sàng tách thành 2 Git repository độc lập bất kỳ lúc nào mà không vướng file rác; Backend thuần JSON API (`0` `vite.config.js`), Frontend phân tầng chuẩn Enterprise Vue 3 (`assets`, `components`, `composables`, `layouts`, `modules`, `router`, `services/axiosClient`, `stores`, `utils`).
2. **Chuẩn Big Tech PHP & Docker Compose v2 `include:` (Shopify / Stripe Craft):** Tại thư mục gốc, file `compose.yaml` (7 dòng chuẩn Docker Compose v2.20+) nối thẳng `backend/docker-compose.yml` và `frontend/docker-compose.yml` trên mạng cầu chung `cleancommerce-shared-net` — giúp chạy toàn bộ **9 containers chỉ bằng 1 câu lệnh duy nhất** mà không lặp lại một dòng cấu hình nào.

---

## 1. Sơ Đồ Tổng Thể Hạ Tầng Docker `include:` (`9 Self-Contained Containers`)

```mermaid
flowchart TB
    Root["compose.yaml (Root 1-Command Entry)\ninclude: backend/docker-compose.yml + frontend/docker-compose.yml"]

    subgraph FERepo["Repo Độc Lập: frontend/ (Vue 3.5 + TS + Axios + Pinia + SCSS)"]
        Web["web (:5173)\nDev: Vite HMR | Prod: Nginx SPA"]
    end

    subgraph BERepo["Repo Độc Lập: backend/ (Laravel 13 Pure Headless API)"]
        API["api: FrankenPHP 8.4 (:8080)\nREST API /api/v1/*"]
        Relay["outbox-relay\nphp artisan outbox:relay"]
        Queue["queue\nphp artisan queue:work redis"]
        PGB["pgbouncer (:6432)\nTransaction Pooler"]
        PG[("postgres:16-alpine\nACID + Outbox + Inbox")]
        Redis[("redis:7.4-alpine\nCache, Locks, Queue")]
        Meili[("meilisearch:v1.12\nFaceted Search")]
        Mail["mailpit (:8025)\nLocal SMTP UI"]
    end

    Root -.-> FERepo
    Root -.-> BERepo
    Web -->|"HTTP JSON + Bearer + Idempotency-Key"| API
    API --> PGB --> PG
    API --> Redis
    API --> Meili
    Relay --> PGB
    Relay --> Redis
    Queue --> PGB
    Queue --> Meili
    Queue --> Mail
```

---

## 2. Kiến Trúc Nội Tại Hai Miền (`Backend Hexagonal DDD` & `Frontend Enterprise Vue 3`)

### 2.1 Backend: 4 Vòng Đồng Tâm & 7 Bounded Contexts (`declare(strict_types=1)` + Pure Domain)

```mermaid
flowchart LR
    UI["UI Layer\nThin API Controllers"] --> App["Application Layer\nreadonly *Action + DTOs"]
    Infra["Infrastructure Layer\nEloquent, Sanctum, Outbox, HMAC"] --> App
    App --> Domain["Domain Layer (100% Pure PHP)\nEntities, Money VO, Events, *Port Interfaces"]
    Infra -.->|"Implements Port"| Domain
```

```mermaid
flowchart TD
    Ordering["Ordering\n(Đặt hàng, Trạng thái, Chống BOLA)"]
    Catalog["Catalog\n(Danh mục, SPU/SKU)"]
    Inventory["Inventory\n(Khóa FOR UPDATE & Giữ chỗ TTL)"]
    Promotion["Promotion\n(Báo giá giỏ & Voucher)"]
    Payment["Payment\n(VietQR, Stripe, HMAC Inbox)"]
    Identity["Identity\n(Sanctum Token & RBAC)"]
    Review["ReviewAnalytics\n(Verified Review & GMV)"]

    Ordering -->|"CatalogVariantReadPort"| Catalog
    Ordering -->|"InventoryReservationPort"| Inventory
    Ordering -->|"CouponEvaluatorPort"| Promotion
    Ordering -->|"PaymentInstructionPort"| Payment
    Payment -->|"OrderPaymentSettlementPort"| Ordering
    Identity -->|"OrderAnalyticsReadPort"| Ordering
    Review -->|"OrderAnalyticsReadPort"| Ordering
```

### 2.2 Frontend: Luồng Dữ Liệu Một Chiều Chuẩn Enterprise Vue 3.5

```mermaid
flowchart LR
    Layouts["layouts/\nCustomer · Admin · Auth"] --> Views["modules/{auth,customer,admin}/views/"]
    Views --> Composables["composables/\nuseAuth · useCart · useNotification"]
    Composables --> Stores["stores/\nauth.store · cart.store · ui.store"]
    Stores --> Services["services/\nauth · product · order · cart"]
    Services --> Axios["services/axiosClient.ts\nBearer + 401 Cleanup + Idempotency-Key"]
```

---

## 3. Phân Tích Đầy Đủ Các Luồng Giao Dịch & Tình Huống Biên (`Multi-Flow & Edge Cases`)

### 3.1 Luồng Đặt Hàng Idempotent, Chống Deadlock Đa SKU & Chống Bán Lố Flash Sale

```mermaid
sequenceDiagram
    autonumber
    actor Buyer as Khách hàng (Vue SPA)
    participant Idem as IdempotencyMiddleware
    participant Place as PlaceOrderAction
    participant DB as PostgreSQL (ACID)

    Buyer->>Idem: POST /api/v1/checkout (Idempotency-Key: UUID)
    Idem->>DB: Tra cứu idempotency_keys
    alt Cùng Key + Khác Payload (Sửa trộm giỏ hàng)
        DB-->>Buyer: 409 Conflict (Từ chối ngay)
    else Cùng Key + Đúng Payload (Mạng lag gửi lại)
        DB-->>Buyer: 200 OK + X-Idempotent-Replay: true (Trả lại nguyên đơn cũ, không trừ kho lần 2)
    else Key mới hoàn toàn
        Idem->>Place: Thực thi trong 1 khối DB::transaction()
        Note over Place,DB: ksort(variant_id) + ORDER BY variant_id chống Deadlock chéo
        Place->>DB: SELECT ... FROM inventory_stocks ORDER BY variant_id FOR UPDATE
        alt available_stock < số lượng đặt
            DB-->>Buyer: 422 OutOfStock (Rollback toàn bộ)
        else Đủ tồn kho
            Place->>DB: Trừ available_stock + INSERT inventory_reservations (TTL +30p)
            Place->>DB: INSERT ordering_orders + ordering_order_lines + outbox_messages (OrderPlaced)
            Place-->>Idem: Order + Mã QR VietQR
            Idem->>DB: Lưu snapshot JSON vào idempotency_keys
            Idem-->>Buyer: 201 Created
        end
    end
```

### 3.2 Luồng Webhook HMAC, Chống Trùng Inbox & Bẫy "Chuyển Khoản Trễ Sau Khi Hết Hạn Giữ Chỗ"

```mermaid
sequenceDiagram
    autonumber
    participant Bank as SePay VietQR / Stripe
    participant Ctrl as PaymentWebhookController
    participant Act as ProcessPaymentWebhookAction
    participant DB as PostgreSQL (Inbox + Stock Lock)

    Bank->>Ctrl: POST /api/v1/webhooks/payment/{provider} (X-Webhook-Signature)
    Ctrl->>Ctrl: Kiểm tra HMAC-SHA256 (hash_equals)
    alt Sai chữ ký HMAC
        Ctrl-->>Bank: 401 Unauthorized
    else Chữ ký hợp lệ
        Ctrl->>Act: PaymentWebhookCommand
        Act->>DB: INSERT inbox_messages (UNIQUE provider, external_event_id)
        alt Ngân hàng bắn trùng event_id lần 2
            DB-->>Bank: 200 OK { duplicate: true } (Bỏ qua an toàn)
        else Sự kiện mới -> Khóa đơn FOR UPDATE
            alt paid_amount_minor < grand_total_minor (Gian lận sửa số tiền chuyển QR)
                Act-->>Bank: 422 Underpayment Rejected (Rollback, giữ unpaid)
            else Đủ tiền -> Gọi confirmForOrder()
                alt Phiếu giữ còn hiệu lực HOẶC kho hiện tại vẫn đủ hàng cấp lại
                    Act->>DB: Chốt sold_stock, Order = paid, Ghi Outbox (OrderPaid)
                    DB-->>Bank: 200 OK { order_status: "paid", payment_status: "paid" }
                else Phiếu đã hết hạn 30p VÀ khách khác đã mua hết sạch kho
                    Act->>DB: Kho giữ nguyên = 0 (Không âm kho!), Order = refund_required, Outbox (OrderRefundRequired)
                    DB-->>Bank: 200 OK { order_status: "refund_required", payment_status: "paid_pending_refund" }
                end
            end
        end
    end
```

---

## 4. Sổ Tay 6 Sự Cố Thực Chiến Cloud & Giải Pháp Kiến Trúc Đã Tích Hợp

| # | Tình huống thực tế gặp phải trên Production | Nguyên nhân gốc (`Root Cause`) | Giải pháp đã tích hợp & kiểm thử tự động |
| :-: | :--- | :--- | :--- |
| **1** | Lỗi `prepared statement "pdo_stmt_..." already exists` khi chạy qua PgBouncer | PgBouncer ở `POOL_MODE=transaction` tráo kết nối vật lý giữa các câu lệnh làm đụng độ PDO prepared statements | Bật `PDO::ATTR_EMULATE_PREPARES => true` cho `pgsql` tại [backend/config/database.php](./backend/config/database.php) |
| **2** | Âm tồn kho (*Phantom Orders*) khi khách quét QR chuyển tiền trễ sau 35 phút | Hết hạn giữ chỗ 30p kho nhả ra cho Khách B mua đứt, nhưng Webhook Khách A đến trễ vẫn đổi thành `paid` | `confirmForOrder()` khóa dòng thử cấp lại; nếu kho = 0 sẽ chuyển sang `refund_required` + phát `OrderRefundRequired` |
| **3** | Gian lận sửa số tiền khi chuyển khoản VietQR | Khách sửa tay số tiền từ `890.000₫` xuống `10.000₫` nhưng giữ nguyên mã `ORD-...` | `settlePaidOrder()` chặn `422` nếu `paidAmountMinor < grand_total_minor` |
| **4** | Treo DB (`Deadlock`) khi 2 giỏ hàng khóa cặp SKU ngược chiều nhau | Request 1 khóa `[SKU_1, SKU_2]`, Request 2 khóa `[SKU_2, SKU_1]` cùng mili-giây | Luôn `ksort` + `ORDER BY variant_id` trước `SELECT ... FOR UPDATE` |
| **5** | Đặt trùng 2 đơn khi mạng 4G lag khách bấm liên tiếp | Client retry `POST /api/v1/checkout` khi chưa nhận response | Header `Idempotency-Key` (UUIDv4) + bảng `idempotency_keys` trả snapshot cũ |
| **6** | Rò rỉ phiên đăng nhập giữa 2 request trên FrankenPHP / Octane | Tiến trình PHP giữ bộ nhớ qua nhiều request | Dùng `final readonly` Actions không biến `static` + `FreshBearerTokenMiddleware` + PHP `Money` số nguyên (`int`, không dùng `float`) |

---

## 5. Cấu Trúc Kho Chứa & Liên Kết Tài Liệu Chi Tiết

```text
VueLaravelProject/
├── compose.yaml                 # Docker Compose v2 `include:` (gộp chạy cả BE + FE bằng 1 lệnh)
├── AGENTS.md / CLAUDE.md        # Điều hướng nhanh cho AI Agents (Claude Code, Codex, Antigravity, Cursor)
├── backend/                     # Repo Độc Lập 1: Laravel 13 Pure Headless API (0 vite.config.js)
│   ├── .env.example & Dockerfile & docker-compose.yml
│   ├── docs/{LAW.md, ARCHITECTURE.md, WBS.md, api/OPENAPI_V1.md}
│   └── app/{Core/SharedKernel, Modules/7-Bounded-Contexts}
└── frontend/                    # Repo Độc Lập 2: Enterprise Vue 3.5 + TS + Axios + Pinia + SCSS
    ├── .env.example & Dockerfile & docker-compose.yml
    ├── docs/{DESIGN.md, ARCHITECTURE.md, WBS.md, api/OPENAPI_V1.md}
    └── src/{assets, components, composables, layouts, modules, router, services, stores, utils}
```

- **Tài liệu chuyên sâu Backend:** [LAW.md](./backend/docs/LAW.md) · [ARCHITECTURE.md](./backend/docs/ARCHITECTURE.md) · [WBS.md](./backend/docs/WBS.md) · [OPENAPI_V1.md](./backend/docs/api/OPENAPI_V1.md)
- **Tài liệu chuyên sâu Frontend:** [DESIGN.md](./frontend/docs/DESIGN.md) · [ARCHITECTURE.md](./frontend/docs/ARCHITECTURE.md) · [WBS.md](./frontend/docs/WBS.md)

---

## 6. Ba Cách Khởi Chạy Linh Hoạt

### Cách 1 — Chạy Full-Stack 9 Containers Từ Thư Mục Gốc (1 Lệnh Duy Nhất Qua `compose.yaml`)
```bash
docker compose up -d --build
docker compose exec api php artisan migrate --seed --force
```
- **Storefront & Admin SPA:** http://localhost:5173
- **Headless REST API:** http://localhost:8080/api/v1/health · **Mailpit:** http://localhost:8025 · **Meilisearch:** http://localhost:7700

### Cách 2 — Chạy Tách Rời Từng Repo Độc Lập (Khi Chia Team BE / FE Riêng)
```bash
# Chỉ bật cụm Backend API + DB + Redis + Queue + Outbox:
cd backend && cp -n .env.example .env && docker compose up -d --build

# Chỉ bật cụm Frontend SPA:
cd frontend && cp -n .env.example .env && docker compose up -d --build
```

### Cách 3 — Chạy Trực Tiếp Trên Máy Local (Không Cần Bật Docker)
```bash
# Terminal 1 (Backend API + SQLite):
cd backend && composer test && php artisan migrate:fresh --seed && php artisan serve --port=8080

# Terminal 2 (Frontend SPA):
cd frontend && npm run dev
```

### Tài Khoản & Voucher Seed Sẵn
- **Admin (`/admin`):** `admin@cleancommerce.vn` / `Admin@123456`
- **Khách hàng:** `khachhang@cleancommerce.vn` / `Customer@123456` *(có nút điền nhanh 1 chạm tại `/auth/login`)*
- **Mã giảm giá:** `WELCOME10` · `VIP200K` · `FREESHIP`
