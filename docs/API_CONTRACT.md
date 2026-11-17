# Hợp Đồng REST API v1 & Ma Trận Khớp Nối 1:1 (`docs/API_CONTRACT.md`)

> **Nguồn Sự Thật Duy Nhất (`Single Source of Truth`):** Tài liệu này được đặt tại `docs/` chung ở gốc để **không bao giờ phải copy 2 bản trùng lặp** vào `backend/` và `frontend/`.
> Bất kỳ thay đổi nào về đường dẫn, trường JSON hoặc phân quyền đều phải cập nhật tại bảng khớp nối 1:1 dưới đây trước khi sửa code ở hai miền.

---

## 1. Ma Trận Khớp Nối Đầu-Cuối 1:1 (`Frontend <-> REST API <-> Backend <-> Database`)

Toàn bộ **22 Endpoints** tại `backend/routes/api.php` khớp chính xác với tầng `frontend/src/services/*.ts` và các màn hình Vue 3:

| # | Method & Endpoint (`/api/v1/*`) | Auth / Guard | Frontend View & Store tiêu thụ (`frontend/src/`) | Hàm Axios Service (`frontend/src/services/`) | Backend Controller & UseCase Action (`backend/app/`) | Domain Port & Bảng DB sở hữu |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- |
| **1** | `GET /health` | Public | Banner kiểm tra kết nối hệ thống | `axiosClient.get('/health')` | Closure tại `routes/api.php` | Kiểm tra sống còn API |
| **2** | `POST /auth/register` | Public | `modules/auth/views/RegisterView.vue` · `auth.store.ts` | `authService.register()` | `AuthController@register` | `IdentityAuthPort` · `users`, `personal_access_tokens` |
| **3** | `POST /auth/login` | Public | `modules/auth/views/LoginView.vue` · `auth.store.ts` | `authService.login()` | `AuthController@login` | `IdentityAuthPort` · `users`, `personal_access_tokens` |
| **4** | `GET /auth/me` | `auth:sanctum` | `router/index.ts` (Guard khởi tạo) · `auth.store.ts` | `authService.me()` | `AuthController@me` | `IdentityAuthPort` · `users` |
| **5** | `POST /auth/logout` | `auth:sanctum` | `CustomerLayout.vue` · `AdminLayout.vue` · `useAuth.ts` | `authService.logout()` | `AuthController@logout` | `IdentityAuthPort` · `personal_access_tokens` |
| **6** | `GET /catalog/categories` | Public | `HomeView.vue` · `ProductListView.vue` · `ProductFormView.vue` | `productService.getCategories()` | `CatalogController@categories` | `CatalogRepositoryPort` · `catalog_categories` |
| **7** | `GET /catalog/products` | Public | `HomeView.vue` · `ProductListView.vue` · `ProductManageView.vue` | `productService.getProducts()` | `CatalogController@products` | `CatalogRepositoryPort` · `catalog_products`, `catalog_variants` |
| **8** | `GET /catalog/products/{slug}` | Public | `ProductDetailView.vue` · `ProductManageView.vue` (Kho SKU) | `productService.getProductBySlug()` | `CatalogController@show` | `CatalogRepositoryPort`, `InventoryStockReadPort`, `ProductReviewReadPort` |
| **9** | `POST /catalog/products/{slug}/reviews` | `auth:sanctum` | `ProductDetailView.vue` · `ReviewList.vue` | `productService.createReview()` | `ProductReviewController@store` -> `SubmitProductReviewAction` | `ReviewRepositoryPort`, `OrderAnalyticsReadPort` · `product_reviews` |
| **10** | `POST /cart/quote` | Public | `CartView.vue` · `CheckoutView.vue` · `cart.store.ts` | `cartService.quoteCart()` | `CartQuoteController@quote` -> `CalculateCartQuoteAction` | `CatalogVariantReadPort`, `InventoryStockReadPort`, `CouponEvaluatorPort` |
| **11** | `POST /checkout` | `Idempotency-Key` (+ Optional Bearer) | `CheckoutView.vue` · `useCart.ts` | `orderService.checkout()` | `CheckoutController@store` -> `PlaceOrderAction` | `InventoryReservationPort` (`FOR UPDATE`), `OrderingRepositoryPort`, `OutboxPort` |
| **12** | `GET /orders` | `auth:sanctum` | `modules/customer/views/ProfileView.vue` | `orderService.getMyOrders()` | `OrderController@index` | `OrderingRepositoryPort` · `ordering_orders` |
| **13** | `GET /orders/{orderNumber}` | Anti-BOLA Check | `CheckoutView.vue` · `ProfileView.vue` · `OrderDetailView.vue` | `orderService.getOrderByNumber()` | `OrderController@show` | `OrderingRepositoryPort`, `PaymentInstructionPort` |
| **14** | `POST /webhooks/payment/{provider}` | `X-Webhook-Signature` (HMAC) | *(Cổng ngân hàng SePay / Stripe gọi trực tiếp)* | *(Server-to-Server Webhook)* | `PaymentWebhookController@handle` -> `ProcessPaymentWebhookAction` | `PaymentInboxPort` (`inbox_messages`), `OrderPaymentSettlementPort` |
| **15** | `POST /payments/dev-simulate-webhook` | Local/Testing | `CheckoutView.vue` · `ProfileView.vue` (Nút giả lập VietQR 2s) | `orderService.simulateDevWebhook()` | `PaymentWebhookController@simulateDev` -> `SimulateDevWebhookAction` | Ký HMAC nội bộ -> `ProcessPaymentWebhookAction` |
| **16** | `GET /admin/analytics/overview` | `auth:sanctum` + `AdminRole` | `modules/admin/views/DashboardView.vue` | `orderService.getAdminAnalytics()` | `AdminAnalyticsController@overview` -> `GetAdminAnalyticsOverviewAction` | `OrderAnalyticsReadPort`, `InventoryStockReadPort` |
| **17** | `GET /admin/orders` | `auth:sanctum` + `AdminRole` | `DashboardView.vue` · `orders/OrderManageView.vue` | `orderService.getAdminOrders()` | `AdminOrderController@index` | `OrderingRepositoryPort` · `ordering_orders` |
| **18** | `PATCH /admin/orders/{orderNumber}/status` | `auth:sanctum` + `AdminRole` | `orders/OrderManageView.vue` · `orders/OrderDetailView.vue` | `orderService.updateAdminOrderStatus()` | `AdminOrderController@updateStatus` -> `UpdateAdminOrderStatusAction` | `OrderingRepositoryPort`, `InventoryReservationPort` (hoàn kho nếu `cancelled`) |
| **19** | `POST /admin/products` | `auth:sanctum` + `AdminRole` | `products/ProductFormView.vue` | `productService.createProduct()` | `AdminProductController@store` -> `CreateAdminProductAction` | `CatalogRepositoryPort`, `InventoryReservationPort` |
| **20** | `POST /admin/inventory/adjust` | `auth:sanctum` + `AdminRole` | `products/ProductManageView.vue` | `productService.adjustInventory()` | `AdminInventoryController@adjust` -> `AdjustInventoryAction` | `InventoryReservationPort` · `inventory_stocks`, `inventory_movements` |
| **21** | `GET /admin/users` | `auth:sanctum` + `AdminRole` | `users/UserManageView.vue` | `authService.listUsers()` | `AdminUserController@index` | `IdentityAuthPort`, `OrderAnalyticsReadPort` · `users` |
| **22** | `PATCH /admin/users/{id}/role` | `auth:sanctum` + `AdminRole` | `users/UserManageView.vue` | `authService.updateUserRole()` | `AdminUserController@updateRole` | `IdentityAuthPort` (Chặn tự hạ quyền chính mình `422`) |

---

## 2. Chuẩn Phong Bì JSON, Đơn Vị Tiền Tệ & Mã Lỗi Thống Nhất

### 2.1 Quy ước dữ liệu xuyên biên giới FE <-> BE
1. **Phong bì thành công (`ApiDataEnvelope<T>`):** Mọi dữ liệu nghiệp vụ bọc trong `{ "data": ... }` (khớp với `frontend/src/types/api.types.ts`).
2. **Tiền tệ số nguyên (`*_minor`):** Toàn bộ trường tiền (`unit_price_minor`, `subtotal_minor`, `discount_minor`, `shipping_fee_minor`, `grand_total_minor`, `total_gmv_minor`) truyền bằng **số nguyên đồng (`int`)**, định dạng hiển thị sang `890.000 ₫` duy nhất tại `frontend/src/utils/formatCurrency.ts`.
3. **Bảng trạng thái đồng bộ (`OrderStatus` & `PaymentStatus`):**
   - `OrderStatus`: `'pending_payment' | 'paid' | 'processing' | 'shipped' | 'completed' | 'cancelled' | 'refund_required'`
   - `PaymentStatus`: `'unpaid' | 'paid' | 'paid_pending_refund' | 'refunded'`

### 2.2 Quy ước HTTP Status & Xử lý tại `axiosClient.ts`
| HTTP Code | Ý nghĩa từ Backend (`backend/`) | Hành vi tự động tại Frontend (`frontend/`) |
| :-: | :--- | :--- |
| `200` / `201` | Thành công (kèm `X-Idempotent-Replay: true` nếu phát lại đơn cũ) | Giải nén `response.data.data` trả về cho Store / View |
| `401` | Chưa đăng nhập, Token hết hạn, hoặc sai chữ ký HMAC Webhook | `axiosClient.ts` tự xóa Token cũ khỏi `localStorage` và chuyển về `/auth/login` |
| `403` | Vi phạm phân quyền (`customer` gọi `/admin/*` hoặc xem trộm đơn người khác — Anti-BOLA) | Điều hướng an toàn & hiển thị thông báo từ chối truy cập |
| `409` | Trùng `Idempotency-Key` nhưng nội dung giỏ hàng đã bị thay đổi | Tạo khóa `Idempotency-Key` mới sau khi người dùng xác nhận lại giỏ hàng |
| `422` | Lỗi dữ liệu đầu vào, hết tồn kho (`OutOfStock`), chuyển thiếu tiền, hoặc Admin tự hạ quyền | Trích xuất thông điệp tiếng Việt từ `errors` / `message` hiển thị trực tiếp qua `useNotification` |

---

## 3. Payload Mẫu Của 3 Giao Dịch Trọng Tâm

### 3.1 Báo giá giỏ hàng (`POST /api/v1/cart/quote`)
```json
{
  "items": [{ "variant_id": 1, "quantity": 2 }],
  "coupon_code": "WELCOME10",
  "province": "TP. Hồ Chí Minh"
}
```

### 3.2 Đặt hàng Idempotent (`POST /api/v1/checkout`)
- **Header bắt buộc:** `Idempotency-Key: <uuid-v4>`
```json
{
  "customer_name": "Nguyễn Minh Quân",
  "customer_email": "khachhang@cleancommerce.vn",
  "customer_phone": "0901234567",
  "shipping_address": "123 Nguyễn Huệ, Quận 1",
  "shipping_province": "TP. Hồ Chí Minh",
  "payment_method": "vietqr",
  "coupon_code": "WELCOME10",
  "items": [{ "variant_id": 1, "quantity": 1 }]
}
```

### 3.3 Webhook Thanh Toán Ngân Hàng (`POST /api/v1/webhooks/payment/{provider}`)
- **Header bắt buộc:** `X-Webhook-Signature: hex(hmac_sha256(raw_body, secret))`
```json
{
  "event_id": "sepay_evt_20261117_001",
  "order_number": "ORD-20261117-AB12CD",
  "amount_minor": 890000,
  "transaction_ref": "FT26321998877"
}
```
