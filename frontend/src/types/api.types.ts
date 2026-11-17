export type UserRole = 'customer' | 'admin'

export interface UserProfile {
  id: number
  name: string
  email: string
  role: UserRole
  phone: string | null
  created_at?: string
}

export interface AdminUserItem {
  id: number
  name: string
  email: string
  role: UserRole
  phone: string | null
  orders_count?: number
  created_at?: string
}

export interface AuthSessionPayload {
  token: string
  user: UserProfile
}

export interface CategoryItem {
  id: number
  name: string
  slug: string
  description: string | null
  products_count: number
}

export interface ProductCategoryRef {
  id: number
  name: string
  slug: string
}

export interface ProductVariant {
  id: number
  sku: string
  color: string
  size: string
  price_minor: number
  compare_at_minor?: number | null
  available_stock: number
}

export interface ProductSummary {
  id: number
  name: string
  slug: string
  brand: string
  short_description: string
  description: string
  category: ProductCategoryRef | null
  image_url: string
  min_price_minor: number
  currency: string
  rating_avg: number
  reviews_count: number
  total_available_stock: number
  variants: ProductVariant[]
}

export interface ProductReview {
  id: number
  user_name: string
  rating: number
  comment: string
  is_verified_purchase: boolean
  created_at: string
}

export interface ProductDetail extends ProductSummary {
  reviews: ProductReview[]
}

export type CatalogSortOption = 'featured' | 'price_asc' | 'price_desc' | 'newest'

export interface ProductFilterParams {
  q?: string
  category?: string
  sort?: CatalogSortOption
}

export interface CartItemInput {
  variant_id: number
  quantity: number
}

export interface CartItemSnapshot {
  variant_id: number
  quantity: number
  product_name: string
  product_slug: string
  image_url: string
  sku: string
  color: string
  size: string
  unit_price_minor: number
  available_stock: number
}

export interface CartQuoteLine {
  variant_id: number
  product_name: string
  product_slug: string
  image_url: string
  sku: string
  color: string
  size: string
  unit_price_minor: number
  quantity: number
  line_total_minor: number
  available_stock: number
}

export interface AppliedCouponInfo {
  code: string
  label: string
}

export interface CartQuote {
  lines: CartQuoteLine[]
  subtotal_minor: number
  discount_minor: number
  shipping_fee_minor: number
  grand_total_minor: number
  currency: string
  applied_coupon: AppliedCouponInfo | null
  coupon_error: string | null
}

export type PaymentMethod = 'vietqr' | 'stripe' | 'cod'

export type OrderStatus =
  | 'pending_payment'
  | 'paid'
  | 'processing'
  | 'shipped'
  | 'completed'
  | 'cancelled'
  | 'refund_required'
  | 'paid_pending_refund'

export type AdminOrderTransitionStatus = 'processing' | 'shipped' | 'completed' | 'cancelled'

export type PaymentStatus =
  | 'unpaid'
  | 'paid'
  | 'failed'
  | 'refunded'
  | 'refund_required'
  | 'paid_pending_refund'

export interface CheckoutPayload {
  customer_name: string
  customer_email: string
  customer_phone: string
  shipping_address: string
  shipping_province: string
  payment_method: PaymentMethod
  coupon_code?: string | null
  note?: string | null
  items: CartItemInput[]
}

export interface OrderLineItem {
  sku: string
  product_name: string
  variant_label: string
  quantity: number
  unit_price_minor: number
  line_total_minor: number
}

export interface PaymentInstruction {
  provider: 'sepay_vietqr' | 'stripe' | 'cod' | string
  qr_image_url?: string | null
  bank_name?: string | null
  account_number?: string | null
  account_holder?: string | null
  transfer_content?: string | null
  amount_minor: number
  expires_at?: string | null
}

export interface OrderDetail {
  id: number
  order_number: string
  status: OrderStatus
  payment_status: PaymentStatus
  payment_method: PaymentMethod
  subtotal_minor: number
  discount_minor: number
  shipping_fee_minor: number
  grand_total_minor: number
  currency: string
  customer_name: string
  customer_email?: string
  customer_phone?: string
  shipping_address: string
  shipping_province?: string
  note?: string | null
  created_at: string
  lines: OrderLineItem[]
  payment_instruction?: PaymentInstruction | null
}

export interface DevWebhookSimulationResult {
  order_number: string
  status: OrderStatus
  payment_status: PaymentStatus
}

export interface LowStockVariantItem {
  variant_id: number
  sku: string
  product_name: string
  color: string
  size: string
  available_stock: number
}

export interface OutboxStats {
  pending: number
  dispatched: number
}

export interface AdminOverviewAnalytics {
  gmv_minor: number
  paid_orders_count: number
  total_orders_count: number
  avg_order_value_minor: number
  products_count: number
  low_stock_variants: LowStockVariantItem[]
  outbox_stats: OutboxStats
}

export interface AdminCreateVariantInput {
  sku: string
  color: string
  size: string
  price_minor: number
  initial_stock: number
}

export interface AdminCreateProductPayload {
  category_id: number
  name: string
  brand: string
  short_description: string
  description: string
  image_url: string
  variants: AdminCreateVariantInput[]
}

export interface AdminInventoryAdjustPayload {
  variant_id: number
  delta: number
  reason: string
}

export interface AdminInventoryAdjustResult {
  variant_id: number
  sku: string
  available_stock: number
}

export interface ApiDataEnvelope<T> {
  data: T
}
