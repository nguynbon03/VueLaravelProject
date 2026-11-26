# REST API v1 Contract & 1:1 Integration Matrix (`docs/API_CONTRACT.md`)  
*(Hợp Đồng REST API v1 & Ma Trận Khớp Nối 1:1 (`docs/API_CONTRACT.md`))*

- **Single Source of Truth**: Stored once inside root `docs/` so `backend/` and `frontend/` never duplicate API contracts.  
  *(**Nguồn Sự Thật Duy Nhất**: Đặt tại `docs/` chung ở gốc để `backend/` và `frontend/` không bao giờ phải chép lặp 2 bản hợp đồng API.)*

- **Contract-First Update Rule**: Any route path, JSON field, or RBAC guard change must update the 1:1 table below before modifying backend or frontend code.  
  *(**Quy tắc cập nhật hợp đồng trước**: Mọi thay đổi về đường dẫn, trường JSON hoặc phân quyền đều phải cập nhật tại bảng khớp nối 1:1 dưới đây trước khi sửa code ở hai miền.)*

---

## 1. End-to-End 1:1 Integration Matrix (`Frontend <-> REST API <-> Backend <-> Database`)  
*(Ma Trận Khớp Nối Đầu-Cuối 1:1 (`Frontend <-> REST API <-> Backend <-> Database`))*

All **22 endpoints** in `backend/routes/api.php` map 1:1 to `frontend/src/services/*.ts` and Vue 3 views:  
*(Toàn bộ **22 Endpoints** tại `backend/routes/api.php` khớp chính xác 1:1 với tầng `frontend/src/services/*.ts` và các màn hình Vue 3:)*

| # | Method & Endpoint (`/api/v1/*`) | Auth / Guard | Consuming Frontend View & Store (`frontend/src/`) | Axios Service Method (`frontend/src/services/`) | Backend Controller & UseCase Action (`backend/app/`) | Domain Port & Owned DB Tables |
| :-: | :--- | :--- | :--- | :--- | :--- | :--- |
| **1** | `GET /health` | Public | System connectivity banner | `axiosClient.get('/health')` | Closure in `routes/api.php` | API liveness check |
| **2** | `POST /auth/register` | Public | `modules/auth/views/RegisterView.vue` · `auth.store.ts` | `authService.register()` | `AuthController@register` | `IdentityAuthPort` · `users`, `personal_access_tokens` |
| **3** | `POST /auth/login` | Public | `modules/auth/views/LoginView.vue` · `auth.store.ts` | `authService.login()` | `AuthController@login` | `IdentityAuthPort` · `users`, `personal_access_tokens` |
| **4** | `GET /auth/me` | `auth:sanctum` | `router/index.ts` (boot guard) · `auth.store.ts` | `authService.me()` | `AuthController@me` | `IdentityAuthPort` · `users` |
| **5** | `POST /auth/logout` | `auth:sanctum` | `CustomerLayout.vue` · `AdminLayout.vue` · `useAuth.ts` | `authService.logout()` | `AuthController@logout` | `IdentityAuthPort` · `personal_access_tokens` |
| **6** | `GET /catalog/categories` | Public | `HomeView.vue` · `ProductListView.vue` · `ProductFormView.vue` | `productService.getCategories()` | `CatalogController@categories` | `CatalogRepositoryPort` · `catalog_categories` |
| **7** | `GET /catalog/products` | Public | `HomeView.vue` · `ProductListView.vue` · `ProductManageView.vue` | `productService.getProducts()` | `CatalogController@products` | `CatalogRepositoryPort` · `catalog_products`, `catalog_variants` |
| **8** | `GET /catalog/products/{slug}` | Public | `ProductDetailView.vue` · `ProductManageView.vue` (SKU Stock) | `productService.getProductBySlug()` | `CatalogController@show` | `CatalogRepositoryPort`, `InventoryStockReadPort`, `ProductReviewReadPort` |
| **9** | `POST /catalog/products/{slug}/reviews` | `auth:sanctum` | `ProductDetailView.vue` · `ReviewList.vue` | `productService.createReview()` | `ProductReviewController@store` -> `SubmitProductReviewAction` | `ReviewRepositoryPort`, `OrderAnalyticsReadPort` · `product_reviews` |
| **10** | `POST /cart/quote` | Public | `CartView.vue` · `CheckoutView.vue` · `cart.store.ts` | `cartService.quoteCart()` | `CartQuoteController@quote` -> `CalculateCartQuoteAction` | `CatalogVariantReadPort`, `InventoryStockReadPort`, `CouponEvaluatorPort` |
| **11** | `POST /checkout` | `Idempotency-Key` (+ Optional Bearer) | `CheckoutView.vue` · `useCart.ts` | `orderService.checkout()` | `CheckoutController@store` -> `PlaceOrderAction` | `InventoryReservationPort` (`FOR UPDATE`), `OrderingRepositoryPort`, `OutboxPort` |
| **12** | `GET /orders` | `auth:sanctum` | `modules/customer/views/ProfileView.vue` | `orderService.getMyOrders()` | `OrderController@index` | `OrderingRepositoryPort` · `ordering_orders` |
| **13** | `GET /orders/{orderNumber}` | Anti-BOLA Check | `CheckoutView.vue` · `ProfileView.vue` · `OrderDetailView.vue` | `orderService.getOrderByNumber()` | `OrderController@show` | `OrderingRepositoryPort`, `PaymentInstructionPort` |
| **14** | `POST /webhooks/payment/{provider}` | `X-Webhook-Signature` (HMAC) | *(Direct SePay / Stripe Bank Webhook)* | *(Server-to-Server Webhook)* | `PaymentWebhookController@handle` -> `ProcessPaymentWebhookAction` | `PaymentInboxPort` (`inbox_messages`), `OrderPaymentSettlementPort` |
| **15** | `POST /payments/dev-simulate-webhook` | Local/Testing | `CheckoutView.vue` · `ProfileView.vue` (2s VietQR Simulator) | `orderService.simulateDevWebhook()` | `PaymentWebhookController@simulateDev` -> `SimulateDevWebhookAction` | Internal HMAC sign -> `ProcessPaymentWebhookAction` |
| **16** | `GET /admin/analytics/overview` | `auth:sanctum` + `AdminRole` | `modules/admin/views/DashboardView.vue` | `orderService.getAdminAnalytics()` | `AdminAnalyticsController@overview` -> `GetAdminAnalyticsOverviewAction` | `OrderAnalyticsReadPort`, `InventoryStockReadPort` |
| **17** | `GET /admin/orders` | `auth:sanctum` + `AdminRole` | `DashboardView.vue` · `orders/OrderManageView.vue` | `orderService.getAdminOrders()` | `AdminOrderController@index` | `OrderingRepositoryPort` · `ordering_orders` |
| **18** | `PATCH /admin/orders/{orderNumber}/status` | `auth:sanctum` + `AdminRole` | `orders/OrderManageView.vue` · `orders/OrderDetailView.vue` | `orderService.updateAdminOrderStatus()` | `AdminOrderController@updateStatus` -> `UpdateAdminOrderStatusAction` | `OrderingRepositoryPort`, `InventoryReservationPort` (release stock if `cancelled`) |
| **19** | `POST /admin/products` | `auth:sanctum` + `AdminRole` | `products/ProductFormView.vue` | `productService.createProduct()` | `AdminProductController@store` -> `CreateAdminProductAction` | `CatalogRepositoryPort`, `InventoryReservationPort` |
| **20** | `POST /admin/inventory/adjust` | `auth:sanctum` + `AdminRole` | `products/ProductManageView.vue` | `productService.adjustInventory()` | `AdminInventoryController@adjust` -> `AdjustInventoryAction` | `InventoryReservationPort` · `inventory_stocks`, `inventory_movements` |
| **21** | `GET /admin/users` | `auth:sanctum` + `AdminRole` | `users/UserManageView.vue` | `authService.listUsers()` | `AdminUserController@index` | `IdentityAuthPort`, `OrderAnalyticsReadPort` · `users` |
| **22** | `PATCH /admin/users/{id}/role` | `auth:sanctum` + `AdminRole` | `users/UserManageView.vue` | `authService.updateUserRole()` | `AdminUserController@updateRole` | `IdentityAuthPort` (blocks self-demotion with `422`) |

---

## 2. Unified JSON Envelope, Monetary Minor Units & Error Codes  
*(Chuẩn Phong Bì JSON, Đơn Vị Tiền Tệ & Mã Lỗi Thống Nhất)*

1. **Success Envelope (`ApiDataEnvelope<T>`)**: All business payloads wrap inside `{ "data": ... }` (synced with `frontend/src/types/api.types.ts`).  
   *(**Phong bì thành công (`ApiDataEnvelope<T>`)**: Mọi dữ liệu nghiệp vụ bọc trong `{ "data": ... }` (khớp với `frontend/src/types/api.types.ts`).)*

2. **Integer Minor Units (`*_minor`)**: All monetary fields (`unit_price_minor`, `subtotal_minor`, `discount_minor`, `shipping_fee_minor`, `grand_total_minor`, `total_gmv_minor`) travel as **integer VND (`int`)** and format to `890.000 ₫` exclusively inside `frontend/src/utils/formatCurrency.ts`.  
   *(**Tiền tệ số nguyên (`*_minor`)**: Toàn bộ trường tiền truyền bằng **số nguyên đồng (`int`)** và định dạng sang `890.000 ₫` duy nhất tại `frontend/src/utils/formatCurrency.ts`.)*

3. **Synchronized State Enums (`OrderStatus` & `PaymentStatus`)**:  
   *(**Bảng trạng thái đồng bộ (`OrderStatus` & `PaymentStatus`)**:)*

   - `OrderStatus`: `'pending_payment' | 'paid' | 'processing' | 'shipped' | 'completed' | 'cancelled' | 'refund_required'`  
     *(Các trạng thái đơn hàng từ chờ thanh toán đến hoàn tất hoặc cần hoàn tiền do chuyển khoản trễ.)*

   - `PaymentStatus`: `'unpaid' | 'paid' | 'paid_pending_refund' | 'refunded'`  
     *(Các trạng thái thanh toán từ chưa trả đến đã trả hoặc chờ hoàn tiền.)*

| HTTP Code | Backend Meaning (`backend/`) | Frontend Automatic Behavior (`frontend/`) |
| :-: | :--- | :--- |
| `200` / `201` | Success (includes `X-Idempotent-Replay: true` on replayed order) | Unwraps `response.data.data` for Store / View |
| `401` | Unauthenticated, expired token, or invalid webhook HMAC signature | `axiosClient.ts` clears `localStorage` token and redirects to `/auth/login` |
| `403` | RBAC / Anti-BOLA violation (`customer` calling `/admin/*` or another user's order) | Safe navigation & access-denied notification |
| `409` | Reused `Idempotency-Key` with a mutated cart payload | Generates a fresh `Idempotency-Key` after user re-confirms cart |
| `422` | Validation error, `OutOfStock`, underpayment, or admin self-demotion | Surfaces error message via `useNotification` |

---

## 3. Sample Payloads for 3 Core Transactions  
*(Payload Mẫu Của 3 Giao Dịch Trọng Tâm)*

### 3.1 Cart Quote (`POST /api/v1/cart/quote`)  
*(Báo giá giỏ hàng (`POST /api/v1/cart/quote`))*

```json
{
  "items": [{ "variant_id": 1, "quantity": 2 }],
  "coupon_code": "WELCOME10",
  "province": "TP. Hồ Chí Minh"
}
```

### 3.2 Idempotent Checkout (`POST /api/v1/checkout`)  
*(Đặt hàng Idempotent (`POST /api/v1/checkout`))*

- **Required Header**: `Idempotency-Key: <uuid-v4>`  
  *(**Header bắt buộc**: `Idempotency-Key: <uuid-v4>`)*

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

### 3.3 Bank Payment Webhook (`POST /api/v1/webhooks/payment/{provider}`)  
*(Webhook Thanh Toán Ngân Hàng (`POST /api/v1/webhooks/payment/{provider}`))*

- **Required Header**: `X-Webhook-Signature: hex(hmac_sha256(raw_body, secret))`  
  *(**Header bắt buộc**: `X-Webhook-Signature: hex(hmac_sha256(raw_body, secret))`)*

```json
{
  "event_id": "sepay_evt_20261117_001",
  "order_number": "ORD-20261117-AB12CD",
  "amount_minor": 890000,
  "transaction_ref": "FT26321998877"
}
```
