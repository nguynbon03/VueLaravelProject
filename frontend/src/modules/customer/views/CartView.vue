<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import { useCart } from '../../../composables/useCart'
import { useNotification } from '../../../composables/useNotification'
import { formatVnd } from '../../../utils/formatCurrency'
import { VIETNAM_PROVINCES } from '../../../utils/formatDate'

const {
  items,
  couponCode,
  province,
  quote,
  quoteLoading,
  quoteError,
  totalQuantity,
  displayLines,
  subtotalMinor,
  discountMinor,
  shippingFeeMinor,
  grandTotalMinor,
  updateQuantity,
  removeItem,
  applyCoupon,
  setProvince,
  refreshQuote,
} = useCart()

const { success } = useNotification()
const couponInput = ref<string>(couponCode.value)
const selectedProvince = ref<string>(province.value || 'TP. Hồ Chí Minh')
const itemCount = computed(() => totalQuantity.value)

const couponPresets = [
  { code: 'WELCOME10', label: 'Giảm 10%' },
  { code: 'VIP200K', label: 'Giảm 200k' },
  { code: 'FREESHIP', label: 'Miễn ship' },
] as const

async function onApplyCoupon(code?: string): Promise<void> {
  if (code !== undefined) {
    couponInput.value = code
  }
  const updated = await applyCoupon(couponInput.value)
  if (updated?.applied_coupon) {
    success(`Đã áp dụng ${updated.applied_coupon.code}`, updated.applied_coupon.label)
  }
}

async function onClearCoupon(): Promise<void> {
  couponInput.value = ''
  await applyCoupon('')
}

async function onProvinceChange(): Promise<void> {
  await setProvince(selectedProvince.value)
}

onMounted(() => {
  if (items.value.length > 0) {
    void refreshQuote()
  }
})
</script>

<template>
  <div class="cart-page container">
    <div class="cart-header">
      <div>
        <h1>Giỏ hàng của bạn ({{ itemCount }})</h1>
        <p class="cart-sub">
          Kiểm tra số lượng từng mã SKU và thử nghiệm mã ưu đãi trước khi tiến hành thanh toán.
        </p>
      </div>
      <RouterLink to="/products" class="btn btn-secondary btn-sm">
        <BaseIcon name="arrow-left" />
        <span>Mua thêm sản phẩm</span>
      </RouterLink>
    </div>

    <section v-if="items.length === 0" class="empty-cart surface-panel">
      <BaseIcon name="bag" />
      <h2>Giỏ hàng chưa có sản phẩm nào</h2>
      <p class="prose-measure">
        Khám phá bộ sưu tập trang phục và phụ kiện để chọn biến thể màu sắc, kích cỡ phù hợp.
      </p>
      <div class="empty-cta">
        <RouterLink to="/products" class="btn btn-primary">
          <span>Xem sản phẩm</span>
          <BaseIcon name="arrow-right" />
        </RouterLink>
        <RouterLink to="/profile" class="btn btn-secondary">
          <span>Lịch sử đơn hàng</span>
        </RouterLink>
      </div>
    </section>

    <div v-else class="cart-grid">
      <section class="lines-panel surface-panel" aria-label="Danh sách sản phẩm trong giỏ">
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Sản phẩm</th>
                <th>Đơn giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th>Xóa</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="line in displayLines" :key="line.variant_id">
                <td>
                  <div class="line-product">
                    <img :src="line.image_url" :alt="line.product_name" class="line-img" />
                    <div class="line-meta">
                      <RouterLink :to="`/products/${line.product_slug}`" class="line-name">
                        {{ line.product_name }}
                      </RouterLink>
                      <span class="line-sub">
                        {{ line.color }} / {{ line.size }} (<span class="mono-code">{{ line.sku }}</span>)
                      </span>
                    </div>
                  </div>
                </td>
                <td class="tabular">{{ formatVnd(line.unit_price_minor) }}</td>
                <td>
                  <div class="qty-box">
                    <button
                      type="button"
                      class="qty-btn"
                      aria-label="Giảm"
                      @click="updateQuantity(line.variant_id, line.quantity - 1)"
                    >
                      <BaseIcon name="minus" />
                    </button>
                    <span class="qty-num tabular">{{ line.quantity }}</span>
                    <button
                      type="button"
                      class="qty-btn"
                      aria-label="Tăng"
                      :disabled="line.quantity >= line.available_stock"
                      @click="updateQuantity(line.variant_id, line.quantity + 1)"
                    >
                      <BaseIcon name="plus" />
                    </button>
                  </div>
                </td>
                <td class="tabular strong-val">{{ formatVnd(line.line_total_minor) }}</td>
                <td>
                  <button
                    type="button"
                    class="trash-btn"
                    aria-label="Xóa khỏi giỏ"
                    @click="removeItem(line.variant_id)"
                  >
                    <BaseIcon name="trash" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <aside class="quote-panel surface-panel" aria-label="Tạm tính và ưu đãi">
        <h2>Tóm tắt báo giá</h2>

        <div class="field">
          <label for="cart-province" class="field-label">Tỉnh / Thành phố dự kiến giao</label>
          <select
            id="cart-province"
            v-model="selectedProvince"
            class="select"
            @change="onProvinceChange"
          >
            <option v-for="prov in VIETNAM_PROVINCES" :key="prov" :value="prov">
              {{ prov }}
            </option>
          </select>
        </div>

        <div class="coupon-box muted-block">
          <label for="cart-coupon" class="field-label">Mã ưu đãi</label>
          <div class="coupon-row">
            <input
              id="cart-coupon"
              v-model="couponInput"
              type="text"
              class="input mono-code"
              placeholder="Nhập mã giảm giá"
            />
            <button
              type="button"
              class="btn btn-secondary"
              :disabled="quoteLoading"
              @click="onApplyCoupon()"
            >
              <span>Áp dụng</span>
            </button>
          </div>

          <div class="preset-list">
            <button
              v-for="preset in couponPresets"
              :key="preset.code"
              type="button"
              class="preset-btn"
              :class="{ 'is-applied': quote?.applied_coupon?.code === preset.code }"
              @click="onApplyCoupon(preset.code)"
            >
              <BaseIcon name="tag" />
              <span class="mono-code">{{ preset.code }}</span>
              <span>({{ preset.label }})</span>
            </button>
          </div>

          <div v-if="quote?.applied_coupon" class="applied-line">
            <span>
              Đã áp dụng <strong class="mono-code">{{ quote.applied_coupon.code }}</strong>
            </span>
            <button type="button" class="remove-coupon" @click="onClearCoupon">Gỡ mã</button>
          </div>

          <p v-if="quote?.coupon_error" class="field-error">{{ quote.coupon_error }}</p>
          <p v-if="quoteError" class="field-error">{{ quoteError }}</p>
        </div>

        <dl class="quote-totals tabular">
          <div class="q-row">
            <dt>Tạm tính</dt>
            <dd>{{ formatVnd(subtotalMinor) }}</dd>
          </div>
          <div class="q-row">
            <dt>Giảm giá</dt>
            <dd :class="{ 'discount-tone': discountMinor > 0 }">
              {{ discountMinor > 0 ? `- ${formatVnd(discountMinor)}` : formatVnd(0) }}
            </dd>
          </div>
          <div class="q-row">
            <dt>Phí giao hàng</dt>
            <dd>{{ shippingFeeMinor === 0 ? 'Miễn phí' : formatVnd(shippingFeeMinor) }}</dd>
          </div>
          <div class="q-row q-grand">
            <dt>Tổng thanh toán</dt>
            <dd>{{ formatVnd(grandTotalMinor) }}</dd>
          </div>
        </dl>

        <RouterLink to="/checkout" class="btn btn-primary btn-lg btn-block">
          <span>Tiếp tục thanh toán</span>
          <BaseIcon name="arrow-right" />
        </RouterLink>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.cart-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.cart-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.cart-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.empty-cart {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
}

.empty-cta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  padding-top: var(--space-2);
}

.cart-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.45fr) minmax(0, 0.95fr);
  gap: var(--space-6);
  align-items: start;
}

.lines-panel {
  overflow: hidden;
}

.line-product {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.line-img {
  width: 60px;
  height: 60px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  background-color: var(--color-surface-muted);
  flex-shrink: 0;
}

.line-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.line-name {
  font-weight: 600;
  color: var(--color-fg);
}

.line-name:hover {
  color: var(--color-primary);
}

.line-sub {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.qty-box {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-sm);
  height: 34px;
}

.qty-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.qty-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.qty-num {
  min-width: 28px;
  text-align: center;
  font-weight: 600;
}

.strong-val {
  font-weight: 600;
}

.trash-btn {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-fg-subtle);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.trash-btn:hover {
  background-color: var(--color-danger-soft);
  color: var(--color-danger);
}

.quote-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.coupon-box {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.coupon-row {
  display: flex;
  gap: var(--space-2);
}

.preset-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.preset-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  min-height: 34px;
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-strong);
  background-color: transparent;
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
  white-space: nowrap;
  cursor: pointer;
}

.preset-btn.is-applied {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.applied-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: var(--text-caption);
  color: var(--color-success);
}

.remove-coupon {
  border: none;
  background: transparent;
  color: var(--color-danger);
  font-size: var(--text-caption);
  font-weight: 600;
  cursor: pointer;
}

.quote-totals {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.q-row {
  display: flex;
  justify-content: space-between;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.q-row dd {
  margin: 0;
  font-weight: 600;
  color: var(--color-fg);
}

.discount-tone {
  color: var(--color-success) !important;
}

.q-grand {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-fg);
}

.q-grand dd {
  font-size: 1.25rem;
  color: var(--color-primary);
}

@media (max-width: 960px) {
  .cart-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

.cart-page :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.cart-page button:active:not(:disabled) {
  transform: translateY(1px);
}

@media (prefers-reduced-motion: reduce) {
  .cart-page * {
    transition-duration: 0.01ms !important;
  }
}
</style>
