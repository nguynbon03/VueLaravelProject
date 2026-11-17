<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import BaseInput from '../../../components/base/BaseInput.vue'
import BaseModal from '../../../components/base/BaseModal.vue'
import { useAuth } from '../../../composables/useAuth'
import { useCart } from '../../../composables/useCart'
import { useNotification } from '../../../composables/useNotification'
import { ApiError, generateIdempotencyKey } from '../../../services/axiosClient'
import { orderService } from '../../../services/order.service'
import type { CheckoutPayload, OrderDetail, PaymentMethod } from '../../../types/api.types'
import { formatVnd } from '../../../utils/formatCurrency'
import {
  VIETNAM_PROVINCES,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../../../utils/formatDate'
import { validateCheckoutForm } from '../../../utils/validators'

const router = useRouter()
const { user } = useAuth()
const {
  items,
  couponCode,
  province,
  quote,
  quoteLoading,
  totalQuantity,
  displayLines,
  subtotalMinor,
  discountMinor,
  shippingFeeMinor,
  grandTotalMinor,
  applyCoupon,
  setProvince,
  refreshQuote,
  clearCart,
  recordPlacedOrder,
} = useCart()
const { success } = useNotification()

const form = reactive({
  customer_name: user.value?.name ?? '',
  customer_email: user.value?.email ?? '',
  customer_phone: user.value?.phone ?? '',
  shipping_province: province.value || 'TP. Hồ Chí Minh',
  shipping_address: '',
  note: '',
  payment_method: 'vietqr' as PaymentMethod,
})

const fieldErrors = ref<Record<string, string>>({})
const couponInput = ref<string>(couponCode.value)
const submitting = ref(false)
const submitError = ref<string | null>(null)
const idempotencyKey = ref<string>(generateIdempotencyKey())

const placedOrder = ref<OrderDetail | null>(null)
const paymentModalOpen = ref(false)
const simulatingWebhook = ref(false)

const cartQty = computed(() => totalQuantity.value)
const modalTitle = computed(() =>
  placedOrder.value
    ? `Thanh toán đơn ${placedOrder.value.order_number}`
    : 'Thanh toán đơn hàng',
)

const paymentMethods: ReadonlyArray<{
  value: PaymentMethod
  title: string
  description: string
  icon: 'qr' | 'credit-card' | 'truck'
}> = [
  {
    value: 'vietqr',
    title: 'VietQR Chuyển khoản 2 giây',
    description: 'Quét mã QR ngân hàng nội địa, xác nhận tự động qua chữ ký HMAC.',
    icon: 'qr',
  },
  {
    value: 'stripe',
    title: 'Thẻ quốc tế Stripe',
    description: 'Thanh toán thẻ Visa, Mastercard với cơ chế đối soát sự kiện Inbox.',
    icon: 'credit-card',
  },
  {
    value: 'cod',
    title: 'Thanh toán khi nhận hàng COD',
    description: 'Kiểm tra kiện hàng và thanh toán tiền mặt cho nhân viên giao vận.',
    icon: 'truck',
  },
]

function fillSampleRecipient(): void {
  form.customer_name = user.value?.name || 'Nguyễn Minh Quân'
  form.customer_email = user.value?.email || 'khachhang@cleancommerce.vn'
  form.customer_phone = user.value?.phone || '0901234567'
  form.shipping_province = 'TP. Hồ Chí Minh'
  form.shipping_address = '120 Nguyễn Huệ, Phường Bến Nghé, Quận 1'
  form.note = 'Giao trong giờ hành chính từ thứ Hai đến thứ Sáu'
  fieldErrors.value = {}
  void setProvince(form.shipping_province)
}

async function handleProvinceChange(): Promise<void> {
  await setProvince(form.shipping_province)
  idempotencyKey.value = generateIdempotencyKey()
}

async function handleCouponApply(preset?: string): Promise<void> {
  if (preset !== undefined) couponInput.value = preset
  const updated = await applyCoupon(couponInput.value)
  idempotencyKey.value = generateIdempotencyKey()
  if (updated?.applied_coupon) {
    success(`Đã áp dụng ${updated.applied_coupon.code}`, updated.applied_coupon.label)
  }
}

async function handlePlaceOrder(): Promise<void> {
  if (items.value.length === 0) return

  const validation = validateCheckoutForm(form)
  fieldErrors.value = validation
  if (Object.keys(validation).length > 0) {
    submitError.value = 'Vui lòng kiểm tra lại các trường thông tin giao hàng được đánh dấu.'
    return
  }

  submitting.value = true
  submitError.value = null

  const payload: CheckoutPayload = {
    customer_name: form.customer_name.trim(),
    customer_email: form.customer_email.trim(),
    customer_phone: form.customer_phone.trim(),
    shipping_address: form.shipping_address.trim(),
    shipping_province: form.shipping_province.trim(),
    payment_method: form.payment_method,
    coupon_code: quote.value?.applied_coupon?.code ?? (couponInput.value.trim() || null),
    note: form.note.trim() || null,
    items: items.value.map((item) => ({
      variant_id: item.variant_id,
      quantity: item.quantity,
    })),
  }

  try {
    const created = await orderService.checkout(payload, idempotencyKey.value)
    placedOrder.value = created
    recordPlacedOrder(created.order_number)
    clearCart()
    idempotencyKey.value = generateIdempotencyKey()

    if (created.payment_method === 'cod') {
      success(`Đặt đơn ${created.order_number} thành công`, 'Đơn hàng COD đang được chuẩn bị.')
      await router.push({ path: '/profile', query: { order: created.order_number } })
    } else {
      paymentModalOpen.value = true
      success(
        `Đã khởi tạo đơn ${created.order_number}`,
        'Vui lòng hoàn tất xác nhận thanh toán trong hộp thoại.',
      )
    }
  } catch (err) {
    submitError.value =
      err instanceof ApiError
        ? err.message
        : 'Đặt hàng chưa thành công. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}

async function handleSimulateWebhook(): Promise<void> {
  if (!placedOrder.value) return
  simulatingWebhook.value = true
  submitError.value = null
  try {
    const res = await orderService.simulateDevWebhook(placedOrder.value.order_number)
    placedOrder.value = {
      ...placedOrder.value,
      status: res.status,
      payment_status: res.payment_status,
    }
    paymentModalOpen.value = false
    success(
      'Đã xác nhận thanh toán qua Webhook HMAC',
      `Đơn ${res.order_number} đã chuyển sang trạng thái Đã thanh toán.`,
    )
    await router.push({ path: '/profile', query: { order: res.order_number } })
  } catch (err) {
    submitError.value =
      err instanceof ApiError ? err.message : 'Không thể mô phỏng Webhook lúc này.'
  } finally {
    simulatingWebhook.value = false
  }
}

async function closeModalAndViewOrder(): Promise<void> {
  paymentModalOpen.value = false
  if (placedOrder.value) {
    await router.push({ path: '/profile', query: { order: placedOrder.value.order_number } })
  }
}

watch(user, (u) => {
  if (!u) return
  if (!form.customer_name) form.customer_name = u.name
  if (!form.customer_email) form.customer_email = u.email
  if (!form.customer_phone && u.phone) form.customer_phone = u.phone
})

onMounted(() => {
  if (items.value.length > 0) {
    void refreshQuote()
  }
})
</script>

<template>
  <div class="checkout-page container">
    <div class="page-head">
      <div>
        <h1>Thanh toán đơn hàng</h1>
        <p class="head-sub">
          Mọi đơn hàng được gắn khóa chống trùng <span class="mono-code">Idempotency-Key</span> và đối soát tồn kho trực tiếp.
        </p>
      </div>
      <RouterLink to="/cart" class="btn btn-secondary btn-sm">
        <BaseIcon name="arrow-left" />
        <span>Quay lại giỏ hàng</span>
      </RouterLink>
    </div>

    <section v-if="items.length === 0 && !placedOrder" class="empty-panel surface-panel">
      <BaseIcon name="bag" />
      <h2>Chưa có sản phẩm để thanh toán</h2>
      <p>Hãy thêm sản phẩm vào giỏ hàng trước khi điền thông tin giao nhận.</p>
      <RouterLink to="/products" class="btn btn-primary">
        <span>Khám phá sản phẩm</span>
      </RouterLink>
    </section>

    <form v-else-if="items.length > 0" class="checkout-grid" @submit.prevent="handlePlaceOrder">
      <div class="left-panel surface-panel">
        <div class="panel-top">
          <h2>Thông tin giao nhận</h2>
          <button type="button" class="btn btn-ghost btn-sm" @click="fillSampleRecipient">
            <span>Điền địa chỉ mẫu</span>
          </button>
        </div>

        <div class="form-grid">
          <BaseInput
            id="co-name"
            v-model="form.customer_name"
            label="Họ và tên người nhận"
            placeholder="Nguyễn Minh Quân"
            :error="fieldErrors.customer_name"
            required
          />

          <BaseInput
            id="co-phone"
            v-model="form.customer_phone"
            type="tel"
            label="Số điện thoại"
            placeholder="0901234567"
            :tabular="true"
            :error="fieldErrors.customer_phone"
            required
          />

          <BaseInput
            id="co-email"
            v-model="form.customer_email"
            type="email"
            label="Email nhận thông báo"
            placeholder="khachhang@cleancommerce.vn"
            :error="fieldErrors.customer_email"
            required
          />

          <div class="field">
            <label for="co-province" class="field-label">Tỉnh / Thành phố</label>
            <select
              id="co-province"
              v-model="form.shipping_province"
              class="select"
              @change="handleProvinceChange"
            >
              <option v-for="prov in VIETNAM_PROVINCES" :key="prov" :value="prov">
                {{ prov }}
              </option>
            </select>
          </div>

          <div class="span-2">
            <BaseInput
              id="co-address"
              v-model="form.shipping_address"
              label="Địa chỉ chi tiết"
              placeholder="Số nhà, tên đường, phường/xã, quận/huyện"
              :error="fieldErrors.shipping_address"
              required
            />
          </div>

          <div class="span-2">
            <BaseInput
              id="co-note"
              v-model="form.note"
              label="Ghi chú giao hàng (tùy chọn)"
              placeholder="Ví dụ: Giao trong giờ hành chính"
            />
          </div>
        </div>

        <div class="method-section">
          <h2>Phương thức thanh toán</h2>
          <div class="method-list" role="radiogroup" aria-label="Phương thức thanh toán">
            <label
              v-for="m in paymentMethods"
              :key="m.value"
              class="method-card"
              :class="{ 'is-selected': form.payment_method === m.value }"
            >
              <input
                v-model="form.payment_method"
                type="radio"
                name="payment_method"
                :value="m.value"
                class="sr-radio"
                @change="idempotencyKey = generateIdempotencyKey()"
              />
              <span class="method-icon">
                <BaseIcon :name="m.icon" />
              </span>
              <span class="method-copy">
                <strong>{{ m.title }}</strong>
                <span>{{ m.description }}</span>
              </span>
              <span v-if="form.payment_method === m.value" class="method-check">
                <BaseIcon name="check-circle" />
              </span>
            </label>
          </div>
        </div>
      </div>

      <aside class="right-panel surface-panel" aria-label="Tóm tắt thanh toán">
        <h2>Đơn hàng ({{ cartQty }} SP)</h2>

        <div class="mini-lines">
          <div v-for="line in displayLines" :key="line.variant_id" class="mini-row">
            <div class="mini-info">
              <strong>{{ line.product_name }}</strong>
              <span class="tabular">
                {{ line.color }} / {{ line.size }} (x{{ line.quantity }})
              </span>
            </div>
            <span class="tabular mini-total">{{ formatVnd(line.line_total_minor) }}</span>
          </div>
        </div>

        <div class="coupon-inline muted-block">
          <div class="coupon-bar">
            <input
              v-model="couponInput"
              type="text"
              class="input mono-code"
              placeholder="Mã giảm giá (WELCOME10)"
              aria-label="Mã giảm giá"
            />
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              :disabled="quoteLoading"
              @click="handleCouponApply()"
            >
              <span>Áp dụng</span>
            </button>
          </div>
          <div class="quick-coupons">
            <button type="button" class="chip-code mono-code" @click="handleCouponApply('WELCOME10')">
              WELCOME10
            </button>
            <button type="button" class="chip-code mono-code" @click="handleCouponApply('VIP200K')">
              VIP200K
            </button>
            <button type="button" class="chip-code mono-code" @click="handleCouponApply('FREESHIP')">
              FREESHIP
            </button>
          </div>
          <p v-if="quote?.coupon_error" class="field-error">{{ quote.coupon_error }}</p>
        </div>

        <dl class="summary-totals tabular">
          <div class="s-row">
            <dt>Tạm tính</dt>
            <dd>{{ formatVnd(subtotalMinor) }}</dd>
          </div>
          <div class="s-row">
            <dt>Giảm giá ưu đãi</dt>
            <dd :class="{ 'discount-val': discountMinor > 0 }">
              {{ discountMinor > 0 ? `- ${formatVnd(discountMinor)}` : formatVnd(0) }}
            </dd>
          </div>
          <div class="s-row">
            <dt>Phí vận chuyển</dt>
            <dd>{{ shippingFeeMinor === 0 ? 'Miễn phí' : formatVnd(shippingFeeMinor) }}</dd>
          </div>
          <div class="s-row s-grand">
            <dt>Tổng thanh toán</dt>
            <dd>{{ formatVnd(grandTotalMinor) }}</dd>
          </div>
        </dl>

        <div v-if="submitError" class="callout callout-danger">
          <BaseIcon name="alert" />
          <span>{{ submitError }}</span>
        </div>

        <BaseButton type="submit" size="lg" :block="true" :loading="submitting">
          <span>Đặt hàng & Thanh toán</span>
          <BaseIcon name="arrow-right" />
        </BaseButton>
      </aside>
    </form>

    <!-- VietQR / Stripe Payment Modal -->
    <BaseModal :open="paymentModalOpen" :title="modalTitle" max-width="760px" @close="closeModalAndViewOrder">
      <div v-if="placedOrder" class="qr-modal-content">
        <div class="qr-status-row">
          <div class="badges-inline">
            <span class="badge" :class="`badge-${getOrderStatusTone(placedOrder.status)}`">
              {{ getOrderStatusLabel(placedOrder.status) }}
            </span>
            <span class="badge" :class="`badge-${getPaymentStatusTone(placedOrder.payment_status)}`">
              {{ getPaymentStatusLabel(placedOrder.payment_status) }}
            </span>
          </div>
          <span class="method-label">{{ getPaymentMethodLabel(placedOrder.payment_method) }}</span>
        </div>

        <div class="qr-columns">
          <div v-if="placedOrder.payment_instruction?.qr_image_url" class="qr-box muted-block">
            <img
              :src="placedOrder.payment_instruction.qr_image_url"
              :alt="`VietQR ${placedOrder.order_number}`"
              class="qr-img"
            />
            <span class="qr-note">Quét mã bằng ứng dụng ngân hàng để điền sẵn nội dung</span>
          </div>

          <div class="bank-col">
            <dl class="bank-list">
              <div v-if="placedOrder.payment_instruction?.bank_name" class="b-row">
                <dt>Ngân hàng</dt>
                <dd>{{ placedOrder.payment_instruction.bank_name }}</dd>
              </div>
              <div v-if="placedOrder.payment_instruction?.account_number" class="b-row">
                <dt>Số tài khoản</dt>
                <dd class="mono-code">{{ placedOrder.payment_instruction.account_number }}</dd>
              </div>
              <div v-if="placedOrder.payment_instruction?.account_holder" class="b-row">
                <dt>Chủ tài khoản</dt>
                <dd>{{ placedOrder.payment_instruction.account_holder }}</dd>
              </div>
              <div class="b-row">
                <dt>Nội dung CK</dt>
                <dd class="mono-code">
                  {{ placedOrder.payment_instruction?.transfer_content || placedOrder.order_number }}
                </dd>
              </div>
              <div class="b-row">
                <dt>Số tiền</dt>
                <dd class="tabular strong-teal">
                  {{ formatVnd(placedOrder.payment_instruction?.amount_minor ?? placedOrder.grand_total_minor) }}
                </dd>
              </div>
            </dl>

            <div class="webhook-box muted-block">
              <strong>Mô phỏng Webhook HMAC (Môi trường Local)</strong>
              <p>
                Ký chữ ký HMAC-SHA256 từ máy chủ và đẩy qua hàng đợi Inbox để xác nhận thanh toán tức thì.
              </p>
              <p v-if="submitError" class="field-error">{{ submitError }}</p>
              <BaseButton
                variant="primary"
                :loading="simulatingWebhook"
                @click="handleSimulateWebhook"
              >
                <BaseIcon name="shield" />
                <span>Xác nhận Webhook HMAC</span>
              </BaseButton>
            </div>
          </div>
        </div>
      </div>

      <template #footer>
        <button type="button" class="btn btn-secondary" @click="closeModalAndViewOrder">
          <span>Xem chi tiết đơn hàng</span>
          <BaseIcon name="arrow-right" />
        </button>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.checkout-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.page-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.head-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.empty-panel {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
}

.checkout-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.95fr);
  gap: var(--space-6);
  align-items: start;
}

.left-panel,
.right-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.panel-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-4);
}

.span-2 {
  grid-column: span 2;
}

.method-section {
  padding-top: var(--space-5);
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.method-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.method-card {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-4);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out);
}

.method-card:hover {
  background-color: var(--color-canvas);
}

.method-card.is-selected {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
}

.sr-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.method-icon {
  width: 38px;
  height: 38px;
  border-radius: var(--radius-sm);
  background-color: var(--color-surface);
  color: var(--color-primary);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.method-copy {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.method-copy strong {
  font-size: var(--text-sm);
}

.method-copy span {
  font-size: var(--text-label);
  color: var(--color-fg-muted);
}

.method-check {
  color: var(--color-primary);
  display: inline-flex;
}

.mini-lines {
  display: flex;
  flex-direction: column;
}

.mini-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-3);
  padding-block: var(--space-3);
  border-bottom: 1px solid var(--color-border);
  font-size: var(--text-sm);
}

.mini-row:first-child {
  padding-top: 0;
}

.mini-row:last-child {
  border-bottom: none;
}

.mini-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.mini-info span {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.mini-total {
  font-weight: 600;
  white-space: nowrap;
}

.coupon-inline {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.coupon-bar {
  display: flex;
  gap: var(--space-2);
}

.quick-coupons {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.chip-code {
  padding: var(--space-1) var(--space-2);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-sm);
  background-color: transparent;
  cursor: pointer;
}

.chip-code:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.summary-totals {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.s-row {
  display: flex;
  justify-content: space-between;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.s-row dd {
  margin: 0;
  font-weight: 600;
  color: var(--color-fg);
}

.discount-val {
  color: var(--color-success) !important;
}

.s-grand {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-fg);
}

.s-grand dd {
  font-size: 1.25rem;
  color: var(--color-primary);
}

/* Modal content */
.qr-modal-content {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.qr-status-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.badges-inline {
  display: flex;
  gap: var(--space-2);
}

.method-label {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.qr-columns {
  display: grid;
  grid-template-columns: 220px minmax(0, 1fr);
  gap: var(--space-6);
  align-items: start;
}

.qr-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-2);
  text-align: center;
}

.qr-img {
  width: 180px;
  height: 180px;
  object-fit: contain;
  background-color: var(--color-surface);
  padding: var(--space-2);
  border-radius: var(--radius-sm);
}

.qr-note {
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
}

.bank-col {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.bank-list {
  margin: 0;
  display: flex;
  flex-direction: column;
}

.b-row {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
  padding-block: var(--space-2);
  border-bottom: 1px solid var(--color-border);
  font-size: var(--text-sm);
}

.b-row dt {
  color: var(--color-fg-muted);
}

.b-row dd {
  margin: 0;
  font-weight: 600;
}

.strong-teal {
  color: var(--color-primary);
}

.webhook-box {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

.webhook-box p {
  color: var(--color-fg-muted);
}

@media (max-width: 960px) {
  .checkout-grid,
  .qr-columns {
    grid-template-columns: minmax(0, 1fr);
  }
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .span-2 {
    grid-column: span 1;
  }
}
</style>
