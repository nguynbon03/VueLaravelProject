# Bản Đồ Nội Bộ 7 Bounded Contexts & Cơ Sở Dữ Liệu (`backend/docs/MODULE_MAP.md`)

> **Phạm vi:** Tài liệu **RIÊNG** của `backend/` — mô tả cấu trúc kết nối nội bộ giữa 7 Bounded Contexts, `SharedKernel`, bảng PostgreSQL sở hữu riêng và tiến trình nền `outbox:relay`.
> Để xem sơ đồ khớp nối xuyên sang Frontend Vue 3, mở [../../docs/ARCHITECTURE.md](../../docs/ARCHITECTURE.md).

---

## 1. Sơ Đồ Giao Tiếp Nội Bộ Qua `Domain Ports` (`app/Providers/ModulesServiceProvider.php`)

Các Module tuyệt đối không gọi trực tiếp Model hay Adapter của nhau mà chỉ giao tiếp qua `Domain\Contracts\*Port`:

```mermaid
flowchart TD
    Ordering["Ordering\n(Đặt hàng, Trạng thái, Anti-BOLA)"]
    Catalog["Catalog\n(Danh mục, SPU/SKU)"]
    Inventory["Inventory\n(Khóa FOR UPDATE & Giữ chỗ TTL 30p)"]
    Promotion["Promotion\n(Báo giá giỏ & Mã giảm giá)"]
    Payment["Payment\n(VietQR, Stripe, HMAC Inbox)"]
    Identity["Identity\n(Sanctum Token & Quản lý RBAC)"]
    Review["ReviewAnalytics\n(Verified Review & Thống kê GMV)"]

    Ordering -->|"CatalogVariantReadPort"| Catalog
    Ordering -->|"InventoryReservationPort"| Inventory
    Ordering -->|"CouponEvaluatorPort"| Promotion
    Ordering -->|"PaymentInstructionPort"| Payment
    Payment -->|"OrderPaymentSettlementPort"| Ordering
    Identity -->|"OrderAnalyticsReadPort"| Ordering
    Review -->|"OrderAnalyticsReadPort"| Ordering
```

---

## 2. Bảng Phân Quyền Sở Hữu Bảng Dữ Liệu & `Port <-> Adapter`

| Module (`app/Modules/*`) | Bảng DB Sở Hữu Riêng | Interface Cung Cấp (`Domain/Contracts/`) | Adapter Thực Thi (`Infrastructure/Persistence/`) |
| :--- | :--- | :--- | :--- |
| **`Identity`** | `users`, `personal_access_tokens` | `IdentityAuthPort` | `SanctumIdentityAdapter` |
| **`Catalog`** | `catalog_categories`, `catalog_products`, `catalog_variants` | `CatalogRepositoryPort`, `CatalogVariantReadPort` | `EloquentCatalogAdapter` |
| **`Inventory`** | `inventory_stocks`, `inventory_reservations`, `inventory_movements` | `InventoryReservationPort`, `InventoryStockReadPort` | `EloquentInventoryAdapter` |
| **`Promotion`** | `promotion_coupons` | `CouponEvaluatorPort` | `EloquentCouponEvaluatorAdapter` |
| **`Ordering`** | `ordering_orders`, `ordering_order_lines` | `OrderingRepositoryPort`, `OrderPaymentSettlementPort`, `OrderAnalyticsReadPort` | `EloquentOrderingAdapter` |
| **`Payment`** | `payment_transactions`, `inbox_messages` | `PaymentInstructionPort`, `PaymentInboxPort` | `EloquentPaymentAdapter` |
| **`ReviewAnalytics`** | `product_reviews` | `ProductReviewReadPort`, `ReviewRepositoryPort` | `EloquentReviewAdapter` |
| **`Core/SharedKernel`** | `outbox_messages`, `idempotency_keys` | `OutboxPort` + `IdempotencyMiddleware` | `EloquentOutboxAdapter` |

---

## 3. Chu Trình Tiến Trình Nền `php artisan outbox:relay`

```mermaid
flowchart LR
    Tick["outbox-relay container\nChu kỳ 500ms"] --> Sweep["1. Quét inventory_reservations\nstatus='reserved' & expires_at < now()"]
    Sweep -->|"Hoàn trả available_stock\nGhi sổ cái reservation_expired"| Stock[("inventory_stocks")]
    Tick --> Poll["2. Đọc outbox_messages\ndispatched_at IS NULL"]
    Poll -->|"Đẩy sự kiện sang Redis Queue\nĐánh dấu dispatched_at"| Queue["queue-worker\n(Meilisearch / Mailpit)"]
```
