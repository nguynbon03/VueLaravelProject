# OpenAPI v1 REST Contract (`docs/api/OPENAPI_V1.md`)

> Hợp đồng JSON duy nhất kết nối `frontend/` (`VITE_API_BASE_URL=http://localhost:8080/api/v1`) và `backend/` (`routes/api.php`). Mọi thay đổi trường dữ liệu phải cập nhật tại đây trước khi sửa code.

---

## 1. Danh Mục 20 Endpoints (`/api/v1/*`)

| Phân hệ | Method & Path | Auth | Mô tả |
| :--- | :--- | :--- | :--- |
| **Health** | `GET /api/v1/health` | Public | Trạng thái sống & kiến trúc hệ thống |
| **Identity** | `POST /api/v1/auth/register` | Public | Đăng ký tài khoản & cấp Sanctum Bearer Token |
| **Identity** | `POST /api/v1/auth/login` | Public | Đăng nhập & nhận Bearer Token |
| **Identity** | `GET /api/v1/auth/me` | Bearer | Thông tin người dùng hiện tại (`customer` / `admin`) |
| **Identity** | `POST /api/v1/auth/logout` | Bearer | Thu hồi Bearer Token hiện tại |
| **Catalog** | `GET /api/v1/catalog/categories` | Public | Danh sách danh mục kèm số lượng sản phẩm |
| **Catalog** | `GET /api/v1/catalog/products` | Public | Lọc theo `q`, `category`, `sort` (`featured`, `price_asc`, `price_desc`, `newest`) |
| **Catalog** | `GET /api/v1/catalog/products/{slug}` | Public | Chi tiết sản phẩm, ma trận SKU, tồn kho khả dụng & đánh giá |
| **Reviews** | `POST /api/v1/catalog/products/{slug}/reviews` | Bearer | Gửi đánh giá (tự động gắn `is_verified_purchase` nếu đã mua) |
| **Promotion** | `POST /api/v1/cart/quote` | Public | Tính tiền giỏ hàng, kiểm tra tồn kho, áp mã giảm giá & phí giao hàng |
| **Ordering** | `POST /api/v1/checkout` | Optional Bearer + `Idempotency-Key` | Khóa tồn kho `FOR UPDATE`, tạo đơn ACID, ghi Outbox & sinh mã VietQR |
| **Ordering** | `GET /api/v1/orders` | Bearer | Lịch sử đơn hàng của khách đang đăng nhập |
| **Ordering** | `GET /api/v1/orders/{orderNumber}` | Anti-BOLA | Chi tiết đơn hàng, tiến trình & hướng dẫn thanh toán |
| **Payment** | `POST /api/v1/webhooks/payment/{provider}` | `X-Webhook-Signature` | Nhận Webhook `sepay` / `stripe` (HMAC-SHA256 + `inbox_messages`) |
| **Payment** | `POST /api/v1/payments/dev-simulate-webhook` | Local/Dev | Ký HMAC nội bộ để mô phỏng xác nhận thanh toán VietQR 2 giây trên UI |
| **Admin** | `GET /api/v1/admin/analytics/overview` | Admin Bearer | Thống kê GMV, AOV, số đơn đã thanh toán, Outbox & cảnh báo kho thấp |
| **Admin** | `GET /api/v1/admin/orders` | Admin Bearer | Toàn bộ danh sách đơn hàng toàn sàn |
| **Admin** | `PATCH /api/v1/admin/orders/{orderNumber}/status` | Admin Bearer | Chuyển trạng thái (`processing`, `shipped`, `completed`, `cancelled` -> hoàn kho) |
| **Admin** | `POST /api/v1/admin/products` | Admin Bearer | Tạo sản phẩm SPU + danh sách biến thể SKU + tồn kho ban đầu |
| **Admin** | `POST /api/v1/admin/inventory/adjust` | Admin Bearer | Điều chỉnh tồn kho `+/-` kèm lý do lưu vào sổ cái `inventory_movements` |

---

## 2. Cấu Trúc Payload Trọng Tâm

### 2.1 Báo giá giỏ hàng (`POST /api/v1/cart/quote`)
- **Request:**
  ```json
  {
    "items": [{ "variant_id": 1, "quantity": 2 }],
    "coupon_code": "WELCOME10",
    "province": "TP. Hồ Chí Minh"
  }
  ```
- **Response `200`:**
  ```json
  {
    "data": {
      "lines": [
        {
          "variant_id": 1,
          "product_name": "Áo Khoác Kỹ Thuật Chống Nước Urban Shell",
          "product_slug": "ao-khoac-ky-thuat-urban-shell",
          "sku": "CW-SHELL-BLK-M",
          "color": "Đen Than",
          "size": "M",
          "unit_price_minor": 890000,
          "quantity": 2,
          "line_total_minor": 1780000,
          "available_stock": 14
        }
      ],
      "subtotal_minor": 1780000,
      "discount_minor": 150000,
      "shipping_fee_minor": 0,
      "grand_total_minor": 1630000,
      "currency": "VND",
      "applied_coupon": { "code": "WELCOME10", "label": "Giảm 10% tối đa 150.000đ" },
      "coupon_error": null
    }
  }
  ```

### 2.2 Đặt hàng Idempotent (`POST /api/v1/checkout`)
- **Headers:** `Idempotency-Key: <uuid-v4>` (Bắt buộc để chống tạo đơn kép khi mạng chập chờn).
- **Body:** `{ "customer_name", "customer_email", "customer_phone", "shipping_address", "shipping_province", "payment_method": "vietqr" | "stripe" | "cod", "coupon_code"?, "note"?, "items": [{ "variant_id", "quantity" }] }`
- **Response `201` (hoặc `200` kèm `X-Idempotent-Replay: true`):** Trả về `OrderDetail` kèm `payment_instruction` (`qr_image_url`, `bank_name`, `account_number`, `transfer_content`, `amount_minor`).

### 2.3 Xác thực Webhook Thanh Toán (`POST /api/v1/webhooks/payment/{provider}`)
- **Headers:** `X-Webhook-Signature: hex(hmac_sha256(raw_json_body, secret))`
- **Body:** `{ "event_id": "evt_unique_01", "order_number": "ORD-...", "amount_minor": 1630000, "transaction_ref": "FT26321..." }`
- **Response `200`:** `{ "data": { "acknowledged": true, "duplicate": false, "order_status": "processing", "payment_status": "paid" } }`
