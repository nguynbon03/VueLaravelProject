# System Architecture, Multi-Flow Sequences & Cloud Production Scars (`docs/ARCHITECTURE.md`)

> Tài liệu mô tả toàn cảnh kiến trúc hạ tầng, bản đồ 7 Bounded Contexts, **đầy đủ 4 luồng nghiệp vụ trọng yếu kèm tình huống lỗi biên (Edge Cases)** và **Sổ tay bài học thực chiến từ sự cố Cloud/Cộng đồng 2025–2026**.
> *Quy chuẩn sơ đồ:* Tối ưu tương phản cho cả **Dark Mode** và **Light Mode** trong Markdown Preview (không dùng khối nền trắng đặc gây lóa chữ).

---

## 1. Sơ Đồ Hạ Tầng Docker Compose (`9 Self-Contained Containers`)

```mermaid
flowchart TB
    subgraph Client["Tầng Giao Diện Độc Lập (frontend/)"]
        SPA["Vue 3.5 + TypeScript SPA (:5173)\nDev: Vite HMR | Prod: Nginx Alpine"]
    end

    subgraph BackendCluster["Tầng Ứng Dụng & Xử Lý Nền (backend/)"]
        API["FrankenPHP 8.4 (:8080)\nHeadless REST API /api/v1"]
        Relay["outbox-relay\nphp artisan outbox:relay"]
        Queue["queue\nphp artisan queue:work redis"]
    end

    subgraph DataLayer["Tầng Dữ Liệu & Tìm Kiếm"]
        PGB["pgbouncer (:6432)\nTransaction Pooler"]
        PG[("postgres:16-alpine\nACID + Outbox + Inbox")]
        Redis[("redis:7.4-alpine\nCache, Locks, Queue")]
        Meili[("meilisearch:v1.12\nFaceted Search")]
        Mail["mailpit (:8025)\nLocal SMTP UI"]
    end

    SPA -->|"JSON + Bearer + Idempotency-Key"| API
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

## 2. Bản Đồ 7 Bounded Contexts & Giao Tiếp Qua `Ports`

```mermaid
flowchart LR
    Ordering["Ordering\n(Đặt hàng & Trạng thái)"]
    Catalog["Catalog\n(Danh mục, SPU/SKU)"]
    Inventory["Inventory\n(Khóa kho & Giữ chỗ)"]
    Promotion["Promotion\n(Báo giá & Voucher)"]
    Payment["Payment\n(VietQR, Stripe, HMAC)"]
    Identity["Identity\n(Sanctum & RBAC)"]
    Review["ReviewAnalytics\n(Đánh giá & GMV)"]

    Ordering -->|"CatalogVariantReadPort"| Catalog
    Ordering -->|"InventoryReservationPort"| Inventory
    Ordering -->|"CouponEvaluatorPort"| Promotion
    Ordering -->|"PaymentInstructionPort"| Payment
    Payment -->|"OrderPaymentSettlementPort"| Ordering
    Review -->|"OrderAnalyticsReadPort"| Ordering
```

| Bounded Context | Bảng Sở Hữu Riêng | Port Cung Cấp Cho Miền Khác |
| :--- | :--- | :--- |
| **`Identity`** | `users`, `personal_access_tokens` | `IdentityAuthPort` |
| **`Catalog`** | `catalog_categories`, `catalog_products`, `catalog_variants` | `CatalogRepositoryPort`, `CatalogVariantReadPort` |
| **`Inventory`** | `inventory_stocks`, `inventory_reservations`, `inventory_movements` | `InventoryReservationPort`, `InventoryStockReadPort` |
| **`Promotion`** | `promotion_coupons` | `CouponEvaluatorPort` |
| **`Ordering`** | `ordering_orders`, `ordering_order_lines` | `OrderingRepositoryPort`, `OrderPaymentSettlementPort`, `OrderAnalyticsReadPort` |
| **`Payment`** | `payment_transactions`, `inbox_messages` | `PaymentInstructionPort`, `PaymentInboxPort` |
| **`ReviewAnalytics`** | `product_reviews` | `ProductReviewReadPort`, `ReviewRepositoryPort` |
| **`SharedKernel`** | `outbox_messages`, `idempotency_keys` | `OutboxPort`, `IdempotencyMiddleware` |

---

## 3. Phân Tích Toàn Diện Các Luồng Nghiệp Vụ & Tình Huống Biên (`Multi-Flow & Edge-Case Sequences`)

### 3.1 Luồng Đặt Hàng Idempotent, Chống Deadlock & Chống Bán Lố Flash Sale

Xử lý đồng thời 4 tình huống: **(1) Bấm đúp / Mạng lag gửi lại đơn**, **(2) Sửa body trên cùng `Idempotency-Key`**, **(3) Tranh chấp dòng tồn kho cuối cùng**, và **(4) Đặt nhiều SKU ngược thứ tự gây Deadlock**.

```mermaid
sequenceDiagram
    autonumber
    actor Buyer as Khách hàng (Vue)
    participant Idem as IdempotencyMiddleware
    participant Place as PlaceOrderAction
    participant DB as PostgreSQL (ACID)

    Buyer->>Idem: POST /api/v1/checkout (Idempotency-Key)
    Idem->>DB: Tra cứu idempotency_keys
    alt Cùng Key + Khác Payload (Nghi vấn sửa đơn)
        DB-->>Buyer: 409 Conflict (Từ chối ngay)
    else Cùng Key + Đúng Payload (Mạng lag gửi lại)
        DB-->>Buyer: 200 OK + X-Idempotent-Replay: true (Trả lại đúng đơn cũ)
    else Key mới hoàn toàn
        Idem->>Place: Thực thi trong 1 khối DB::transaction()
        Note over Place,DB: Sắp xếp variant_id tăng dần trước khi khóa để chống Deadlock
        Place->>DB: SELECT ... FROM inventory_stocks ORDER BY variant_id FOR UPDATE
        alt Tồn kho khả dụng < Số lượng đặt
            DB-->>Buyer: 422 OutOfStock (Rollback toàn bộ, kho giữ nguyên)
        else Đủ tồn kho
            Place->>DB: Trừ available_stock + Ghi inventory_reservations (TTL +30p)
            Place->>DB: Lưu ordering_orders + ordering_order_lines + outbox_messages (OrderPlaced)
            Place-->>Idem: Order + Mã QR VietQR
            Idem->>DB: Lưu snapshot JSON vào idempotency_keys
            Idem-->>Buyer: 201 Created
        end
    end
```

### 3.2 Luồng Webhook HMAC, Chống Trùng Inbox & Bẫy "Chuyển Khoản Trễ Sau Khi Hết Giữ Chỗ"

Trong thực tế vận hành VietQR / Stripe ngày Flash Sale, có **4 kịch bản hiểm hóc** phải chặn sạch:
1. **Giả mạo chữ ký Webhook** -> Chặn `401 Unauthorized`.
2. **Sửa số tiền chuyển khoản thấp hơn giá trị đơn (`Underpayment Tampering`)** -> Chặn `422 Unprocessable`, giữ nguyên `unpaid`.
3. **Ngân hàng bắn trùng gói tin (`At-least-once retry`)** -> Bảng `inbox_messages` chặn trùng, trả `200 duplicate: true`.
4. **Khách chuyển khoản trễ sau khi hết hạn giữ chỗ 30 phút (`Black Friday Phantom Order Trap`):** Khách A giữ món hàng cuối nhưng 35 phút sau mới chuyển tiền; lúc đó `outbox:relay` đã thu hồi kho và Khách B đã mua mất. Hệ thống **tuyệt đối không để âm kho**, tự động chuyển sang `status = refund_required` (`payment_status = paid_pending_refund`) và phát sự kiện `OrderRefundRequired` để hoàn tiền minh bạch.

```mermaid
sequenceDiagram
    autonumber
    participant Bank as Cổng SePay / Stripe
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
        alt Đã xử lý event_id này trước đó
            DB-->>Bank: 200 OK { duplicate: true }
        else Sự kiện mới -> Khóa đơn FOR UPDATE
            alt Số tiền chuyển < grand_total_minor (Chuyển thiếu tiền)
                Act-->>Bank: 422 Underpayment Rejected (Rollback)
            else Đủ số tiền -> Kiểm tra & xác nhận giữ chỗ kho
                Act->>DB: confirmForOrder() (Khóa inventory_reservations + inventory_stocks)
                alt Phiếu giữ còn hiệu lực HOẶC kho hiện tại vẫn còn hàng để cấp lại
                    Act->>DB: Chốt sold_stock, Order = paid, Ghi Outbox (OrderPaid)
                    DB-->>Bank: 200 OK { order_status: "paid", payment_status: "paid" }
                else Phiếu đã hết hạn VÀ khách khác đã mua hết sạch kho
                    Act->>DB: Kho giữ nguyên = 0, Order = refund_required, Ghi Outbox (OrderRefundRequired)
                    DB-->>Bank: 200 OK { order_status: "refund_required", payment_status: "paid_pending_refund" }
                end
            end
        end
    end
```

### 3.3 Luồng Outbox Relay, Quét Giữ Chỗ Quá Hạn & Hủy Đơn Hoàn Kho

```mermaid
flowchart LR
    Tick["Tiến trình nền\noutbox:relay (500ms)"] --> Sweep["1. Quét inventory_reservations\nstatus='reserved' & expires_at < now()"]
    Sweep -->|"Cộng trả available_stock\nGhi sổ cái reservation_expired"| Stock[("inventory_stocks")]
    Tick --> Poll["2. Đọc outbox_messages\ndispatched_at IS NULL"]
    Poll -->|"Phát sự kiện sang Queue\nĐánh dấu dispatched_at"| Queue["Redis Queue\n(Meilisearch / Mailpit)"]
```

### 3.4 Luồng Xác Thực Bearer Token, Phân Quyền RBAC & Chống Xem Trộm Đơn (`Anti-BOLA`)

- **Tách phiên sạch cho Octane/FrankenPHP:** `FreshBearerTokenMiddleware` làm sạch bộ nhớ đệm Guard giữa các request để tránh rò rỉ tài khoản khi chạy Worker Mode dài hạn.
- **Chống BOLA/IDOR ở `GET /api/v1/orders/{orderNumber}`:** Khách hàng `user_id = 2` cố tình đoán mã đơn của `user_id = 5` bị chặn `403 Forbidden` ngay tại tầng Application.
- **Khóa chặt `/api/v1/admin/*`:** `AdminRoleMiddleware` trả `401` JSON chuẩn khi thiếu token (không redirect về trang HTML `/login`) và `403` nếu `role !== 'admin'`.

---

## 4. Sổ Tay Bài Học Thực Chiến Cloud & Cộng Đồng (`Anti-Out-of-Meta Ledger 2025–2026`)

Tổng hợp từ các báo cáo sự cố thực tế (Post-mortems Black Friday, GitHub Issues Laravel Octane/FrankenPHP, PgBouncer & VietQR/Stripe):

| # | Sự cố thực tế người dùng hay gặp trên Cloud | Nguyên nhân gốc (`Root Cause`) | Giải pháp đã tích hợp sẵn trong dự án |
| :-: | :--- | :--- | :--- |
| **1** | Lỗi `prepared statement "pdo_stmt_..." already exists` khi bật PgBouncer tải cao | PgBouncer ở chế độ `POOL_MODE=transaction` luân chuyển kết nối vật lý giữa các câu lệnh, làm xung đột Prepared Statement gốc của PDO | Đã cấu hình `PDO::ATTR_EMULATE_PREPARES => true` cho `pgsql` tại `backend/config/database.php` |
| **2** | Âm tồn kho ("Phantom Orders") khi ngân hàng báo chuyển khoản chậm sau 30 phút | Hết TTL giữ chỗ, kho nhả ra cho người khác mua, nhưng Webhook đến trễ vẫn đổi đơn cũ thành `paid` mà không kiểm tra lại kho | `EloquentInventoryAdapter::confirmForOrder()` tự thử cấp phát lại có khóa dòng; nếu hết sạch hàng sẽ chuyển sang `refund_required` + phát `OrderRefundRequired` |
| **3** | Gian lận sửa số tiền khi quét mã VietQR động | Khách sửa tay số tiền chuyển từ `890.000₫` xuống `10.000₫` nhưng giữ nguyên nội dung chuyển khoản `ORD-...` | `EloquentOrderingAdapter::settlePaidOrder()` đối chiếu cứng `paidAmountMinor >= grand_total_minor` trước khi gạch nợ |
| **4** | Treo DB (`Deadlock`) khi 2 khách đặt giỏ hàng có cùng cặp SKU ngược thứ tự | Giao dịch 1 khóa `[SKU_1, SKU_2]`, giao dịch 2 khóa `[SKU_2, SKU_1]` cùng mili-giây | `ksort($variantQuantities)` + `ORDER BY variant_id` trước mọi lệnh `lockForUpdate()` |
| **5** | Trừ kho 2 lần khi mạng 4G chập chờn khách bấm thanh toán liên tục | Frontend gửi lặp `POST /checkout` khi chưa nhận được phản hồi | Cấp `crypto.randomUUID()` làm `Idempotency-Key` ở `CheckoutView.vue` + lưu snapshot tại `idempotency_keys` |
| **6** | Rò rỉ thông tin đăng nhập giữa 2 khách hàng trên FrankenPHP / Octane | Worker giữ nguyên bộ nhớ tiến trình PHP qua nhiều request liên tiếp | Dùng `readonly` Action/Adapter không lưu biến tĩnh (`static`) + `FreshBearerTokenMiddleware` |
