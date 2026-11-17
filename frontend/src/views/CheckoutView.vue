<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../modules/auth/authStore'
import { useCartStore } from '../modules/cart/cartStore'
import { ApiError, apiClient, generateIdempotencyKey } from '../shared/api/client'
import type {
  ApiDataEnvelope,
  CheckoutPayload,
  DevWebhookSimulationResult,
  OrderDetail,
  PaymentMethod,
} from '../shared/api/types'
import { useToastStore } from '../shared/state/toastStore'
import AppIcon from '../shared/ui/AppIcon.vue'
import {
  VIETNAM_PROVINCES,
  formatVnd,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../shared/utils/format'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

const form = reactive({
  customer_name: authStore.user?.name ?? '',
  customer_email: authStore.user?.email ?? '',
  customer_phone: authStore.user?.phone ?? '',
  shipping_province: cartStore.province || 'TP. Hồ Chí Minh',
  shipping_address: '',
  note: '',
  payment_method: 'vietqr' as PaymentMethod,
})

const couponInput = ref<string>(cartStore.couponCode)
const submittingOrder = ref(false)
const checkoutError = ref<string | null>(null)
const idempotencyKey = ref<string>(generateIdempotencyKey())

const placedOrder = ref<OrderDetail | null>(null)
const simulatingWebhook = ref(false)

const couponPresets = [
  { code: 'WELCOME10', desc: 'Giảm 10%' },
  { code: 'VIP200K', desc: 'Giảm 200.000 ₫' },
  { code: 'FREESHIP', desc: 'Miễn phí vận chuyển' },
] as const

const paymentOptions: ReadonlyArray<{
  value: PaymentMethod
  title: string
  description: string
  icon: 'qr' | 'credit-card' | 'truck'
}> = [
  {
    value: 'vietqr',
    title: 'VietQR Chuyển khoản 2 giây',
    description: 'Quét mã QR ngân hàng nội địa, đối soát tự động qua Webhook HMAC.',
    icon: 'qr',
  },
  {
    value: 'stripe',
    title: 'Thẻ quốc tế Stripe',
    description: 'Thanh toán bằng thẻ Visa, Mastercard với cơ chế xác thực chữ ký.',
    icon: 'credit-card',
  },
  {
    value: 'cod',
    title: 'Thanh toán khi nhận hàng COD',
    description: 'Kiểm tra kiện hàng và thanh toán tiền mặt trực tiếp cho bưu tá.',
    icon: 'truck',
  },
]

const subtotalMinor = computed(
  () => cartStore.quote?.subtotal_minor ?? cartStore.fallbackSubtotalMinor,
)
const placedCode = computed(() => placedOrder.value?.order_number ?? '')
const cartQty = computed(() => cartStore.totalQuantity)
const discountMinor = computed(() => cartStore.quote?.discount_minor ?? 0)
const shippingFeeMinor = computed(() => cartStore.quote?.shipping_fee_minor ?? 30000)
const grandTotalMinor = computed(
  () =>
    cartStore.quote?.grand_total_minor ??
    Math.max(0, subtotalMinor.value - discountMinor.value + shippingFeeMinor.value),
)

function fillSampleShipping(): void {
  form.customer_name = authStore.user?.name || 'Nguyễn Minh Quân'
  form.customer_email = authStore.user?.email || 'khachhang@cleancommerce.vn'
  form.customer_phone = authStore.user?.phone || '0901234567'
  form.shipping_province = 'TP. Hồ Chí Minh'
  form.shipping_address = '120 Nguyễn Huệ, Phường Bến Nghé, Quận 1'
  form.note = 'Giao trong giờ hành chính từ thứ Hai đến thứ Sáu'
  void cartStore.setProvince(form.shipping_province)
}

async function handleApplyCoupon(codeToApply?: string): Promise<void> {
  if (codeToApply !== undefined) {
    couponInput.value = codeToApply
  }
  const updatedQuote = await cartStore.applyCoupon(couponInput.value)
  idempotencyKey.value = generateIdempotencyKey()
  if (updatedQuote?.applied_coupon) {
    toastStore.push({
      title: `Đã áp dụng mã ${updatedQuote.applied_coupon.code}`,
      description: updatedQuote.applied_coupon.label,
      variant: 'success',
    })
  }
}

async function handleClearCoupon(): Promise<void> {
  couponInput.value = ''
  await cartStore.applyCoupon('')
  idempotencyKey.value = generateIdempotencyKey()
}

async function handleProvinceChange(): Promise<void> {
  await cartStore.setProvince(form.shipping_province)
  idempotencyKey.value = generateIdempotencyKey()
}

async function handleQtyStep(variantId: number, nextQty: number): Promise<void> {
  await cartStore.updateQuantity(variantId, nextQty)
  idempotencyKey.value = generateIdempotencyKey()
}

async function handleRemoveLine(variantId: number): Promise<void> {
  await cartStore.removeItem(variantId)
  idempotencyKey.value = generateIdempotencyKey()
}

async function submitCheckout(): Promise<void> {
  if (cartStore.items.length === 0) return

  if (
    form.customer_name.trim().length < 2 ||
    !form.customer_email.includes('@') ||
    form.customer_phone.trim().length < 8 ||
    form.shipping_address.trim().length < 5
  ) {
    checkoutError.value =
      'Vui lòng điền đầy đủ họ tên, email hợp lệ, số điện thoại và địa chỉ nhận hàng.'
    return
  }

  submittingOrder.value = true
  checkoutError.value = null

  const payload: CheckoutPayload = {
    customer_name: form.customer_name.trim(),
    customer_email: form.customer_email.trim(),
    customer_phone: form.customer_phone.trim(),
    shipping_address: form.shipping_address.trim(),
    shipping_province: form.shipping_province.trim(),
    payment_method: form.payment_method,
    coupon_code: cartStore.quote?.applied_coupon?.code ?? (couponInput.value.trim() || null),
    note: form.note.trim() || null,
    items: cartStore.items.map((item) => ({
      variant_id: item.variant_id,
      quantity: item.quantity,
    })),
  }

  try {
    const res = await apiClient.post<ApiDataEnvelope<OrderDetail>>('/checkout', payload, {
      headers: {
        'Idempotency-Key': idempotencyKey.value,
      },
    })

    placedOrder.value = res.data
    cartStore.recordPlacedOrder(res.data.order_number)
    cartStore.clearCart()
    idempotencyKey.value = generateIdempotencyKey()

    toastStore.push({
      title: `Đã khởi tạo đơn hàng ${res.data.order_number}`,
      description:
        res.data.payment_method === 'cod'
          ? 'Đơn hàng COD đã được tiếp nhận chuẩn bị giao.'
          : 'Vui lòng hoàn tất bước xác nhận thanh toán.',
      variant: 'success',
    })

    if (res.data.payment_method === 'cod') {
      await router.push(`/orders/${res.data.order_number}`)
    }
  } catch (err) {
    checkoutError.value =
      err instanceof ApiError
        ? err.message
        : 'Đặt hàng chưa thành công. Vui lòng kiểm tra thông tin và thử lại.'
  } finally {
    submittingOrder.value = false
  }
}

async function simulateHmacWebhook(): Promise<void> {
  if (!placedOrder.value) return
  simulatingWebhook.value = true
  checkoutError.value = null
  try {
    const res = await apiClient.post<ApiDataEnvelope<DevWebhookSimulationResult>>(
      '/payments/dev-simulate-webhook',
      {
        order_number: placedOrder.value.order_number,
      },
    )

    placedOrder.value = {
      ...placedOrder.value,
      status: res.data.status,
      payment_status: res.data.payment_status,
    }

    toastStore.push({
      title: 'Đã xác nhận thanh toán qua Webhook HMAC',
      description: `Đơn ${res.data.order_number} đã chuyển sang trạng thái Đã thanh toán.`,
      variant: 'success',
    })

    await router.push(`/orders/${res.data.order_number}`)
  } catch (err) {
    checkoutError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể mô phỏng Webhook thanh toán lúc này.'
  } finally {
    simulatingWebhook.value = false
  }
}

watch(
  () => authStore.user,
  (u) => {
    if (!u) return
    if (!form.customer_name) form.customer_name = u.name
    if (!form.customer_email) form.customer_email = u.email
    if (!form.customer_phone && u.phone) form.customer_phone = u.phone
  },
)

onMounted(() => {
  if (cartStore.items.length > 0) {
    void cartStore.refreshQuote()
  }
})
</script>

<template>
  <div class="checkout-page container">
    <div class="page-header">
      <div>
        <h1>Giỏ hàng và thanh toán</h1>
        <p class="header-sub">
          Báo giá, tồn kho và mã ưu đãi được máy chủ kiểm tra trực tiếp trước khi khóa đơn.
        </p>
      </div>
      <RouterLink to="/" class="btn btn-secondary btn-sm">
        <AppIcon name="arrow-left" />
        <span>Tiếp tục mua sắm</span>
      </RouterLink>
    </div>

    <!-- Post-Checkout VietQR / Stripe Payment Panel -->
    <section v-if="placedOrder" class="payment-panel surface-panel" aria-labelledby="qr-panel-title">
      <div class="payment-panel-head">
        <div>
          <div class="order-badges">
            <span class="badge" :class="`badge-${getOrderStatusTone(placedOrder.status)}`">
              {{ getOrderStatusLabel(placedOrder.status) }}
            </span>
            <span class="badge" :class="`badge-${getPaymentStatusTone(placedOrder.payment_status)}`">
              {{ getPaymentStatusLabel(placedOrder.payment_status) }}
            </span>
          </div>
          <h2 id="qr-panel-title" class="panel-order-title">
            Thanh toán đơn hàng <span class="mono-code">{{ placedCode }}</span>
          </h2>
          <p class="panel-order-sub">
            Phương thức: {{ getPaymentMethodLabel(placedOrder.payment_method) }}
          </p>
        </div>

        <div class="panel-amount-box tabular">
          <span class="amount-caption">Tổng thanh toán</span>
          <strong class="amount-figure">{{ formatVnd(placedOrder.grand_total_minor) }}</strong>
        </div>
      </div>

      <div class="payment-panel-grid">
        <div
          v-if="placedOrder.payment_instruction?.qr_image_url"
          class="qr-visual-column muted-block"
        >
          <img
            :src="placedOrder.payment_instruction.qr_image_url"
            :alt="`Mã VietQR thanh toán đơn ${placedOrder.order_number}`"
            class="qr-code-image"
          />
          <p class="qr-caption">
            Mở ứng dụng ngân hàng bất kỳ và quét mã VietQR để điền sẵn số tiền cùng nội dung chuyển khoản.
          </p>
        </div>

        <div class="bank-details-column">
          <dl class="bank-meta-list">
            <div v-if="placedOrder.payment_instruction?.bank_name" class="bank-row">
              <dt>Ngân hàng thụ hưởng</dt>
              <dd>{{ placedOrder.payment_instruction.bank_name }}</dd>
            </div>
            <div v-if="placedOrder.payment_instruction?.account_number" class="bank-row">
              <dt>Số tài khoản</dt>
              <dd class="mono-code">{{ placedOrder.payment_instruction.account_number }}</dd>
            </div>
            <div v-if="placedOrder.payment_instruction?.account_holder" class="bank-row">
              <dt>Chủ tài khoản</dt>
              <dd>{{ placedOrder.payment_instruction.account_holder }}</dd>
            </div>
            <div class="bank-row">
              <dt>Nội dung chuyển khoản</dt>
              <dd class="mono-code">
                {{ placedOrder.payment_instruction?.transfer_content || placedOrder.order_number }}
              </dd>
            </div>
            <div class="bank-row">
              <dt>Số tiền chính xác</dt>
              <dd class="tabular strong-figure">
                {{ formatVnd(placedOrder.payment_instruction?.amount_minor ?? placedOrder.grand_total_minor) }}
              </dd>
            </div>
          </dl>

          <div class="webhook-sandbox muted-block">
            <div class="sandbox-copy">
              <strong>Kiểm thử thanh toán tự động trên môi trường Local</strong>
              <p>
                Nút dưới đây ký chữ ký HMAC-SHA256 từ khóa bí mật phía máy chủ và gửi qua đúng luồng Webhook Inbox để xác nhận đơn hàng ngay lập tức.
              </p>
            </div>

            <p v-if="checkoutError" class="field-error">{{ checkoutError }}</p>

            <div class="sandbox-actions">
              <button
                type="button"
                class="btn btn-primary btn-lg"
                :disabled="simulatingWebhook || placedOrder.payment_status === 'paid'"
                @click="simulateHmacWebhook"
              >
                <AppIcon name="shield" />
                <span>Xác nhận thanh toán mẫu qua Webhook HMAC</span>
              </button>

              <RouterLink
                :to="`/orders/${placedOrder.order_number}`"
                class="btn btn-secondary btn-lg"
              >
                <span>Xem chi tiết đơn hàng</span>
                <AppIcon name="arrow-right" />
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Empty Cart State -->
    <section v-else-if="cartStore.items.length === 0" class="empty-cart surface-panel">
      <div class="empty-icon-wrap">
        <AppIcon name="bag" />
      </div>
      <h2>Giỏ hàng của bạn đang trống</h2>
      <p class="prose-measure">
        Hãy chọn sản phẩm yêu thích từ cửa hàng để xem báo giá chi tiết và áp dụng mã ưu đãi vận chuyển.
      </p>
      <div class="empty-actions">
        <RouterLink to="/" class="btn btn-primary">
          <span>Khám phá sản phẩm</span>
          <AppIcon name="arrow-right" />
        </RouterLink>
        <RouterLink to="/orders" class="btn btn-secondary">
          <span>Xem đơn hàng đã đặt</span>
        </RouterLink>
      </div>
    </section>

    <!-- Two-Column Checkout Form & Live Server Quote -->
    <form v-else class="checkout-grid" @submit.prevent="submitCheckout">
      <div class="checkout-left surface-panel">
        <div class="section-head-row">
          <h2>Thông tin người nhận</h2>
          <button type="button" class="btn btn-ghost btn-sm" @click="fillSampleShipping">
            <span>Điền nhanh địa chỉ mẫu</span>
          </button>
        </div>

        <div class="fields-grid">
          <div class="field">
            <label for="cust-name" class="field-label">Họ và tên</label>
            <input
              id="cust-name"
              v-model="form.customer_name"
              type="text"
              class="input"
              placeholder="Nguyễn Minh Quân"
              required
            />
          </div>

          <div class="field">
            <label for="cust-phone" class="field-label">Số điện thoại</label>
            <input
              id="cust-phone"
              v-model="form.customer_phone"
              type="tel"
              class="input tabular"
              placeholder="0901234567"
              required
            />
          </div>

          <div class="field">
            <label for="cust-email" class="field-label">Email nhận xác nhận đơn</label>
            <input
              id="cust-email"
              v-model="form.customer_email"
              type="email"
              class="input"
              placeholder="khachhang@cleancommerce.vn"
              required
            />
          </div>

          <div class="field">
            <label for="cust-province" class="field-label">Tỉnh / Thành phố</label>
            <select
              id="cust-province"
              v-model="form.shipping_province"
              class="select"
              @change="handleProvinceChange"
            >
              <option v-for="prov in VIETNAM_PROVINCES" :key="prov" :value="prov">
                {{ prov }}
              </option>
            </select>
          </div>

          <div class="field field-span-2">
            <label for="cust-address" class="field-label">Địa chỉ nhận hàng</label>
            <input
              id="cust-address"
              v-model="form.shipping_address"
              type="text"
              class="input"
              placeholder="Số nhà, tên đường, phường/xã, quận/huyện"
              required
            />
          </div>

          <div class="field field-span-2">
            <label for="cust-note" class="field-label">Ghi chú giao hàng (tùy chọn)</label>
            <input
              id="cust-note"
              v-model="form.note"
              type="text"
              class="input"
              placeholder="Ví dụ: Gọi trước khi giao 15 phút"
            />
          </div>
        </div>

        <div class="payment-section">
          <h2>Phương thức thanh toán</h2>
          <div class="payment-options" role="radiogroup" aria-label="Chọn phương thức thanh toán">
            <label
              v-for="opt in paymentOptions"
              :key="opt.value"
              class="payment-radio-row"
              :class="{ 'is-selected': form.payment_method === opt.value }"
            >
              <input
                v-model="form.payment_method"
                type="radio"
                name="payment_method"
                :value="opt.value"
                class="sr-radio"
                @change="idempotencyKey = generateIdempotencyKey()"
              />
              <span class="payment-icon">
                <AppIcon :name="opt.icon" />
              </span>
              <span class="payment-text">
                <strong class="payment-title">{{ opt.title }}</strong>
                <span class="payment-desc">{{ opt.description }}</span>
              </span>
              <span v-if="form.payment_method === opt.value" class="payment-check">
                <AppIcon name="check-circle" />
              </span>
            </label>
          </div>
        </div>
      </div>

      <aside class="checkout-right surface-panel" aria-label="Tóm tắt giỏ hàng và báo giá">
        <div class="summary-head">
          <h2>Đơn hàng ({{ cartQty }} sản phẩm)</h2>
          <span v-if="cartStore.quoteLoading" class="sync-indicator">Đang tính lại…</span>
        </div>

        <div class="cart-lines">
          <div
            v-for="line in cartStore.displayLines"
            :key="line.variant_id"
            class="cart-line-row"
          >
            <img :src="line.image_url" :alt="line.product_name" class="line-thumb" />

            <div class="line-details">
              <RouterLink :to="`/products/${line.product_slug}`" class="line-title">
                {{ line.product_name }}
              </RouterLink>
              <div class="line-variant">
                <span>{{ line.color }} / {{ line.size }}</span>
                <span class="mono-code">{{ line.sku }}</span>
              </div>

              <div class="line-controls">
                <div class="mini-stepper">
                  <button
                    type="button"
                    class="mini-step-btn"
                    aria-label="Giảm số lượng"
                    @click="handleQtyStep(line.variant_id, line.quantity - 1)"
                  >
                    <AppIcon name="minus" />
                  </button>
                  <span class="mini-step-val tabular">{{ line.quantity }}</span>
                  <button
                    type="button"
                    class="mini-step-btn"
                    aria-label="Tăng số lượng"
                    :disabled="line.quantity >= line.available_stock"
                    @click="handleQtyStep(line.variant_id, line.quantity + 1)"
                  >
                    <AppIcon name="plus" />
                  </button>
                </div>

                <button
                  type="button"
                  class="remove-btn"
                  aria-label="Xóa sản phẩm khỏi giỏ"
                  @click="handleRemoveLine(line.variant_id)"
                >
                  <AppIcon name="trash" />
                </button>
              </div>
            </div>

            <div class="line-price tabular">
              {{ formatVnd(line.line_total_minor) }}
            </div>
          </div>
        </div>

        <div class="coupon-block muted-block">
          <label for="coupon-code-input" class="field-label">Mã ưu đãi</label>
          <div class="coupon-input-row">
            <input
              id="coupon-code-input"
              v-model="couponInput"
              type="text"
              class="input mono-code"
              placeholder="Nhập mã giảm giá"
            />
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="cartStore.quoteLoading"
              @click="handleApplyCoupon()"
            >
              <span>Áp dụng</span>
            </button>
          </div>

          <div class="coupon-chips">
            <button
              v-for="preset in couponPresets"
              :key="preset.code"
              type="button"
              class="preset-chip"
              :class="{ 'is-applied': cartStore.quote?.applied_coupon?.code === preset.code }"
              @click="handleApplyCoupon(preset.code)"
            >
              <AppIcon name="tag" />
              <span class="mono-code">{{ preset.code }}</span>
              <span>({{ preset.desc }})</span>
            </button>
          </div>

          <div v-if="cartStore.quote?.applied_coupon" class="applied-coupon-row">
            <span>
              Đã áp dụng <strong class="mono-code">{{ cartStore.quote.applied_coupon.code }}</strong>:
              {{ cartStore.quote.applied_coupon.label }}
            </span>
            <button type="button" class="clear-coupon-btn" @click="handleClearCoupon">Gỡ mã</button>
          </div>

          <p v-if="cartStore.quote?.coupon_error" class="field-error">
            {{ cartStore.quote.coupon_error }}
          </p>
          <p v-if="cartStore.quoteError" class="field-error">
            {{ cartStore.quoteError }}
          </p>
        </div>

        <dl class="totals-list tabular">
          <div class="total-row">
            <dt>Tạm tính</dt>
            <dd>{{ formatVnd(subtotalMinor) }}</dd>
          </div>
          <div class="total-row">
            <dt>Giảm giá ưu đãi</dt>
            <dd :class="{ 'discount-value': discountMinor > 0 }">
              {{ discountMinor > 0 ? `- ${formatVnd(discountMinor)}` : formatVnd(0) }}
            </dd>
          </div>
          <div class="total-row">
            <dt>Phí vận chuyển ({{ form.shipping_province }})</dt>
            <dd>{{ shippingFeeMinor === 0 ? 'Miễn phí' : formatVnd(shippingFeeMinor) }}</dd>
          </div>
          <div class="total-row grand-row">
            <dt>Tổng cộng</dt>
            <dd>{{ formatVnd(grandTotalMinor) }}</dd>
          </div>
        </dl>

        <div v-if="checkoutError" class="callout callout-danger">
          <AppIcon name="alert" />
          <span>{{ checkoutError }}</span>
        </div>

        <button
          type="submit"
          class="btn btn-primary btn-lg btn-block"
          :disabled="submittingOrder || cartStore.quoteLoading"
        >
          <span>Đặt hàng & Thanh toán</span>
          <AppIcon name="arrow-right" />
        </button>

        <p class="idempotency-note">
          Khóa chống trùng giao dịch (<span class="mono-code">Idempotency-Key</span>) được tự động gắn kèm mỗi lần gửi đơn.
        </p>
      </aside>
    </form>
  </div>
</template>

<style scoped>
.checkout-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.header-sub {
  margin-top: var(--space-1);
  color: var(--color-fg-muted);
  font-size: var(--text-sm);
}

.checkout-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);
  gap: var(--space-6);
  align-items: start;
}

.checkout-left,
.checkout-right {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.section-head-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.fields-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-4);
}

.field-span-2 {
  grid-column: span 2;
}

.payment-section {
  padding-top: var(--space-5);
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.payment-options {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.payment-radio-row {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background-color: var(--color-surface);
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out);
}

.payment-radio-row:hover {
  background-color: var(--color-canvas);
  border-color: var(--color-border-strong);
}

.payment-radio-row.is-selected {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
}

.sr-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.payment-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background-color: var(--color-surface);
  color: var(--color-primary);
  flex-shrink: 0;
}

.payment-text {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.payment-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
}

.payment-desc {
  font-size: var(--text-label);
  color: var(--color-fg-muted);
}

.payment-check {
  color: var(--color-primary);
  display: inline-flex;
}

.summary-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

.sync-indicator {
  font-size: var(--text-caption);
  color: var(--color-primary);
  font-weight: 500;
}

.cart-lines {
  display: flex;
  flex-direction: column;
}

.cart-line-row {
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding-block: var(--space-3);
  border-bottom: 1px solid var(--color-border);
}

.cart-line-row:first-child {
  padding-top: 0;
}

.cart-line-row:last-child {
  border-bottom: none;
}

.line-thumb {
  width: 64px;
  height: 64px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  background-color: var(--color-surface-muted);
  flex-shrink: 0;
}

.line-details {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}

.line-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
}

.line-title:hover {
  color: var(--color-primary);
}

.line-variant {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.line-controls {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding-top: var(--space-1);
}

.mini-stepper {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  height: 32px;
}

.mini-step-btn {
  width: 30px;
  height: 30px;
  border: none;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.mini-step-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.mini-step-val {
  min-width: 28px;
  text-align: center;
  font-size: var(--text-label);
  font-weight: 600;
}

.remove-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-fg-subtle);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.remove-btn:hover {
  background-color: var(--color-danger-soft);
  color: var(--color-danger);
}

.line-price {
  font-size: var(--text-sm);
  font-weight: 600;
  white-space: nowrap;
}

.coupon-block {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.coupon-input-row {
  display: flex;
  gap: var(--space-2);
}

.coupon-chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.preset-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  min-height: 34px;
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background-color: transparent;
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
  white-space: nowrap;
  cursor: pointer;
}

.preset-chip:hover {
  border-color: var(--color-border-strong);
  color: var(--color-fg);
}

.preset-chip.is-applied {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.applied-coupon-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
  font-size: var(--text-caption);
  color: var(--color-success);
}

.clear-coupon-btn {
  border: none;
  background: transparent;
  color: var(--color-danger);
  font-size: var(--text-caption);
  font-weight: 600;
  cursor: pointer;
}

.totals-list {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.total-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.total-row dd {
  margin: 0;
  font-weight: 500;
  color: var(--color-fg);
}

.discount-value {
  color: var(--color-success) !important;
}

.grand-row {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
  font-size: 1.0625rem;
  font-weight: 700;
  color: var(--color-fg);
}

.grand-row dd {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--color-primary);
}

.idempotency-note {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
  text-align: center;
}

.empty-cart {
  padding: var(--space-10);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-4);
}

.empty-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  background-color: var(--color-surface-muted);
  color: var(--color-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.empty-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

/* Post-checkout Payment Panel */
.payment-panel {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.payment-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  padding-bottom: var(--space-5);
  border-bottom: 1px solid var(--color-border);
}

.order-badges {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-2);
}

.panel-order-title {
  font-size: var(--text-h2);
}

.panel-order-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.panel-amount-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 2px;
}

.amount-caption {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.amount-figure {
  font-size: 1.5rem;
  color: var(--color-primary);
  font-variant-numeric: tabular-nums;
}

.payment-panel-grid {
  display: grid;
  grid-template-columns: minmax(240px, 320px) minmax(0, 1fr);
  gap: var(--space-8);
  align-items: start;
}

.qr-visual-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  text-align: center;
}

.qr-code-image {
  width: 100%;
  max-width: 240px;
  border-radius: var(--radius-md);
  background-color: var(--color-surface);
  padding: var(--space-2);
}

.qr-caption {
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
}

.bank-details-column {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.bank-meta-list {
  margin: 0;
  display: flex;
  flex-direction: column;
}

.bank-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  padding-block: var(--space-3);
  border-bottom: 1px solid var(--color-border);
  font-size: var(--text-sm);
}

.bank-row dt {
  color: var(--color-fg-muted);
}

.bank-row dd {
  margin: 0;
  font-weight: 600;
  color: var(--color-fg);
}

.strong-figure {
  color: var(--color-primary) !important;
  font-size: var(--text-base);
}

.webhook-sandbox {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.sandbox-copy {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  font-size: var(--text-sm);
}

.sandbox-copy p {
  color: var(--color-fg-muted);
}

.sandbox-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

@media (max-width: 980px) {
  .checkout-grid,
  .payment-panel-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 640px) {
  .fields-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .field-span-2 {
    grid-column: span 1;
  }

  .checkout-left,
  .checkout-right,
  .payment-panel {
    padding: var(--space-4);
  }
}
</style>
