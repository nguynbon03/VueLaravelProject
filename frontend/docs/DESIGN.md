# Hệ Thống Thiết Kế & Chuẩn Cây Thư Mục Frontend (`frontend/docs/DESIGN.md`)

> **Phạm vi:** Tài liệu **RIÊNG** của `frontend/` — quy định hệ thống Calm Daylight 60-30-10 SCSS Tokens, Typography tiếng Việt, luật chống AI Slop (`fe-gate.mjs`) và trách nhiệm từng thư mục trong `src/`.
> Hợp đồng API kết nối với Backend xem tại [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md).

---

## 1. Triết Lý Thị Giác & Hệ Màu Calm Daylight `60-30-10` (`src/assets/styles/`)
- **Surface Mode:** Kết hợp **Persuade** (Storefront khám phá sản phẩm thoáng mắt) + **Operate** (Checkout & Admin Dashboard mật độ thông tin rõ nét, thao tác nhanh).
- **Lý do chọn nền sáng cân bằng (`Calm Daylight`):** Giúp khách hàng quan sát trung thực màu sắc vải/sản phẩm và quét mã ngân hàng VietQR độ tương phản cao (`>= 4.5:1` WCAG AA).

| Vai trò (`60-30-10`) | Biến SCSS / CSS (`variables.scss` & `main.scss`) | Mã màu | Phạm vi sử dụng |
| :--- | :--- | :---: | :--- |
| **60% Canvas** | `--color-canvas` | `#f7f9f9` | Nền tổng thể toàn trang |
| **30% Surface & Ink** | `--color-surface` / `--color-fg` / `--color-fg-muted` | `#fcfdfd` / `#102022` / `#465a5d` | Thẻ sản phẩm, bảng dữ liệu, chữ chính & phụ |
| **10% Primary Accent** | `--color-primary` / `--color-primary-hover` | `#0f766e` / `#115e59` | Nút hành động chính (CTA), tab đang chọn, focus ring |
| **Ngữ nghĩa (`Semantic`)** | `--color-success` · `--color-warning` · `--color-danger` | `#15803d` · `#b45309` · `#b91c1c` | Luôn đi kèm nhãn chữ tiếng Việt tường minh |

---

## 2. Typography Tiếng Việt & Quy Tắc Số Học
- **Font chính (`Sans`):** `'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif` (hiển thị trọn vẹn dấu tiếng Việt).
- **Font mã kỹ thuật (`Mono`):** `'JetBrains Mono', ui-monospace, monospace` (chỉ dùng cho mã SKU, mã đơn `ORD-...`, mã giao dịch ngân hàng).
- **Bắt buộc `font-variant-numeric: tabular-nums`:** Áp dụng cho toàn bộ giá tiền (`formatCurrency.ts`), số lượng tồn kho, chiết khấu và chỉ số GMV để các cột số thẳng hàng tuyệt đối.

---

## 3. Chuẩn Kiến Trúc Cây Thư Mục Enterprise Vue 3 (`frontend/src/`)

```text
src/
├── assets/                  # Tài nguyên tĩnh & SCSS toàn cục (variables.scss, main.scss)
├── components/
│   ├── base/                # Nguyên tử UI tái sử dụng (BaseButton, BaseInput, BaseModal, AppIcon)
│   └── common/              # Thành phần nghiệp vụ dùng chung (Pagination, LoadingSpinner, ToastContainer)
├── composables/             # Composition API hooks (useAuth.ts, useCart.ts, useNotification.ts)
├── layouts/                 # 3 khung bố cục tách biệt (CustomerLayout, AdminLayout, AuthLayout)
├── modules/                 # Phân hệ nghiệp vụ cô lập theo miền
│   ├── auth/                # LoginView, RegisterView, DemoCredentialsCard
│   ├── customer/            # HomeView, ProductListView, ProductDetailView, CartView, CheckoutView, ProfileView
│   └── admin/               # DashboardView, products/{Manage,Form}, orders/{Manage,Detail}, users/UserManageView
├── router/                  # index.ts (beforeEach RBAC guard) + customer.routes.ts + admin.routes.ts
├── services/                # axiosClient.ts (Bearer + 401 Interceptor) + auth/product/cart/order.service.ts
├── stores/                  # Pinia state toàn cục (auth.store.ts, cart.store.ts, ui.store.ts)
├── types/                   # Hợp đồng TypeScript đồng bộ với API_CONTRACT.md (api.types.ts)
└── utils/                   # Hàm thuần TS không phụ thuộc Vue (formatCurrency.ts, formatDate.ts, validators.ts)
```

---

## 4. Luật Chống AI Slop (`Kiểm tra tự động bởi fe-gate.mjs`)
- **Cấm tuyệt đối:** Chữ hoặc nền gradient tím-xanh lòe loẹt, dùng Emoji thay cho SVG icon (`AppIcon.vue` nét `1.75px`), `transition: all`, hoặc dữ liệu giả `Lorem ipsum` / `John Doe`.
- **Trạng thái giao diện đầy đủ:** Mọi màn hình gọi API phải có trạng thái đang tải (`LoadingSpinner`), trạng thái rỗng có nút dẫn đường, thông báo lỗi tiếng Việt rõ ràng và khóa nút chống bấm đúp khi gửi đơn.
