# Frontend Design System & Directory Standard (`frontend/docs/DESIGN.md`)  
*(Hệ Thống Thiết Kế & Chuẩn Cây Thư Mục Frontend (`frontend/docs/DESIGN.md`))*

- **Scope**: Internal specification for `frontend/` — defines Calm Daylight 60-30-10 SCSS tokens, Vietnamese-ready typography, anti-slop rules (`fe-gate.mjs`), and folder responsibilities inside `src/`.  
  *(**Phạm vi**: Tài liệu RIÊNG của `frontend/` — quy định hệ thống Calm Daylight 60-30-10 SCSS Tokens, Typography tiếng Việt, luật chống AI Slop (`fe-gate.mjs`) và trách nhiệm từng thư mục trong `src/`.)*

- **Shared API Contract**: See [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md) for backend integration endpoints.  
  *(**Hợp đồng API chung**: Xem [../../docs/API_CONTRACT.md](../../docs/API_CONTRACT.md) cho các endpoint kết nối với Backend.)*

---

## 1. Visual Philosophy & Calm Daylight `60-30-10` Color Scale (`src/assets/styles/`)  
*(Triết Lý Thị Giác & Hệ Màu Calm Daylight `60-30-10` (`src/assets/styles/`))*

- **Surface Mode**: Combines **Persuade** (airy storefront discovery) with **Operate** (high-clarity checkout & admin dashboard operations).  
  *(**Surface Mode**: Kết hợp **Persuade** (Storefront khám phá sản phẩm thoáng mắt) + **Operate** (Checkout & Admin Dashboard mật độ thông tin rõ nét, thao tác nhanh).)*

- **Why Balanced Light Mode (`Calm Daylight`)**: Preserves true product/fabric colors and guarantees high-contrast VietQR banking code scanning (`>= 4.5:1` WCAG AA).  
  *(**Lý do chọn nền sáng cân bằng (`Calm Daylight`)**: Giúp khách hàng quan sát trung thực màu sắc vải/sản phẩm và quét mã ngân hàng VietQR độ tương phản cao (`>= 4.5:1` WCAG AA).)*

| Role (`60-30-10`) | SCSS / CSS Variable (`variables.scss` & `main.scss`) | Hex Code | Usage Scope |
| :--- | :--- | :---: | :--- |
| **60% Canvas** | `--color-canvas` | `#f7f9f9` | Overall page background *(Nền tổng thể toàn trang)* |
| **30% Surface & Ink** | `--color-surface` / `--color-fg` / `--color-fg-muted` | `#fcfdfd` / `#102022` / `#465a5d` | Product cards, data tables, primary & muted text *(Thẻ sản phẩm, bảng dữ liệu, chữ chính & phụ)* |
| **10% Primary Accent** | `--color-primary` / `--color-primary-hover` | `#0f766e` / `#115e59` | Primary CTA buttons, active tabs, focus rings *(Nút hành động chính (CTA), tab đang chọn, focus ring)* |
| **Semantic** | `--color-success` · `--color-warning` · `--color-danger` | `#15803d` · `#b45309` · `#b91c1c` | Always paired with explicit text labels *(Luôn đi kèm nhãn chữ tường minh)* |

---

## 2. Vietnamese Typography & Numeric Alignment Rules  
*(Typography Tiếng Việt & Quy Tắc Số Học)*

- **Primary Font (`Sans`)**: `'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif` (renders full Vietnamese diacritics cleanly).  
  *(**Font chính (`Sans`)**: `'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, sans-serif` (hiển thị trọn vẹn dấu tiếng Việt).)*

- **Technical Font (`Mono`)**: `'JetBrains Mono', ui-monospace, monospace` (used strictly for SKU codes, `ORD-...` order numbers, and bank transaction references).  
  *(**Font mã kỹ thuật (`Mono`)**: `'JetBrains Mono', ui-monospace, monospace` (chỉ dùng cho mã SKU, mã đơn `ORD-...`, mã giao dịch ngân hàng).)*

- **Mandatory `font-variant-numeric: tabular-nums`**: Applied to all prices (`formatCurrency.ts`), stock quantities, discounts, and GMV metrics so numeric columns align vertically.  
  *(**Bắt buộc `font-variant-numeric: tabular-nums`**: Áp dụng cho toàn bộ giá tiền (`formatCurrency.ts`), số lượng tồn kho, chiết khấu và chỉ số GMV để các cột số thẳng hàng tuyệt đối.)*

---

## 3. Enterprise Vue 3 Directory Architecture (`frontend/src/`)  
*(Chuẩn Kiến Trúc Cây Thư Mục Enterprise Vue 3 (`frontend/src/`))*

```text
src/
├── assets/                  # Static assets & global SCSS (variables.scss, main.scss)
├── components/
│   ├── base/                # Reusable UI atoms (BaseButton, BaseInput, BaseModal, AppIcon)
│   └── common/              # Shared domain components (Pagination, LoadingSpinner, ToastContainer)
├── composables/             # Composition API hooks (useAuth.ts, useCart.ts, useNotification.ts)
├── layouts/                 # 3 isolated layout shells (CustomerLayout, AdminLayout, AuthLayout)
├── modules/                 # Domain-isolated feature modules
│   ├── auth/                # LoginView, RegisterView, DemoCredentialsCard
│   ├── customer/            # HomeView, ProductListView, ProductDetailView, CartView, CheckoutView, ProfileView
│   └── admin/               # DashboardView, products/{Manage,Form}, orders/{Manage,Detail}, users/UserManageView
├── router/                  # index.ts (beforeEach RBAC guard) + customer.routes.ts + admin.routes.ts
├── services/                # axiosClient.ts (Bearer + 401 Interceptor) + auth/product/cart/order.service.ts
├── stores/                  # Global Pinia stores (auth.store.ts, cart.store.ts, ui.store.ts)
├── types/                   # TypeScript contracts synced with API_CONTRACT.md (api.types.ts)
└── utils/                   # Pure TS helpers without Vue coupling (formatCurrency.ts, formatDate.ts, validators.ts)
```

---

## 4. Anti-AI-Slop Laws (`Enforced by fe-gate.mjs`)  
*(Luật Chống AI Slop (`Kiểm tra tự động bởi fe-gate.mjs`))*

- **Strictly Forbidden**: Neon purple/blue gradient text or backgrounds, emojis in place of SVG icons (`AppIcon.vue` `1.75px` stroke), `transition: all`, or `Lorem ipsum` / `John Doe` placeholder copy.  
  *(**Cấm tuyệt đối**: Chữ hoặc nền gradient tím-xanh lòe loẹt, dùng Emoji thay cho SVG icon (`AppIcon.vue` nét `1.75px`), `transition: all`, hoặc dữ liệu giả `Lorem ipsum` / `John Doe`.)*

- **Complete UI States**: Every API-driven view must render loading (`LoadingSpinner`), actionable empty states, clear error notifications, and double-click prevention on order submission.  
  *(**Trạng thái giao diện đầy đủ**: Mọi màn hình gọi API phải có trạng thái đang tải (`LoadingSpinner`), trạng thái rỗng có nút dẫn đường, thông báo lỗi rõ ràng và khóa nút chống bấm đúp khi gửi đơn.)*
