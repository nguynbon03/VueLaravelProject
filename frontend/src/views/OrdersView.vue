<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../modules/auth/authStore'
import { useCartStore } from '../modules/cart/cartStore'
import { ApiError, apiClient } from '../shared/api/client'
import type {
  ApiDataEnvelope,
  DevWebhookSimulationResult,
  OrderDetail,
  OrderStatus,
} from '../shared/api/types'
import { useToastStore } from '../shared/state/toastStore'
import AppIcon from '../shared/ui/AppIcon.vue'
import {
  formatDate,
  formatVnd,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../shared/utils/format'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

const ordersList = ref<OrderDetail[]>([])
const activeOrder = ref<OrderDetail | null>(null)
const loadingList = ref(false)
const loadingDetail = ref(false)
const listError = ref<string | null>(null)
const detailError = ref<string | null>(null)
const lookupCode = ref<string>('')
const simulatingWebhook = ref(false)

const currentOrderNumber = computed(() =>
  typeof route.params.orderNumber === 'string' ? route.params.orderNumber.trim() : '',
)
const detailCode = computed(() => activeOrder.value?.order_number ?? '')
const historyHeading = computed(() =>
  authStore.isAuthenticated
    ? 'Lịch sử đơn hàng của bạn'
    : 'Đơn hàng vừa đặt trên trình duyệt này',
)

const progressSteps: ReadonlyArray<{ key: OrderStatus; label: string }> = [
  { key: 'pending_payment', label: 'Khởi tạo đơn' },
  { key: 'paid', label: 'Đã thanh toán' },
  { key: 'processing', label: 'Chuẩn bị hàng' },
  { key: 'shipped', label: 'Đang giao hàng' },
  { key: 'completed', label: 'Hoàn tất' },
]

function getStepIndex(status: OrderStatus): number {
  switch (status) {
    case 'pending_payment':
      return 0
    case 'paid':
      return 1
    case 'processing':
      return 2
    case 'shipped':
      return 3
    case 'completed':
      return 4
    case 'cancelled':
    case 'refund_required':
      return -1
  }
}

async function fetchOrdersList(): Promise<void> {
  if (!authStore.isAuthenticated) {
    ordersList.value = []
    return
  }
  loadingList.value = true
  listError.value = null
  try {
    const res = await apiClient.get<ApiDataEnvelope<OrderDetail[]>>('/orders')
    ordersList.value = res.data
  } catch (err) {
    listError.value =
      err instanceof ApiError ? err.message : 'Không thể tải danh sách đơn hàng.'
  } finally {
    loadingList.value = false
  }
}

async function fetchOrderDetail(orderNum: string): Promise<void> {
  if (!orderNum) {
    activeOrder.value = null
    return
  }
  loadingDetail.value = true
  detailError.value = null
  try {
    const res = await apiClient.get<ApiDataEnvelope<OrderDetail>>(`/orders/${orderNum}`)
    activeOrder.value = res.data
  } catch (err) {
    activeOrder.value = null
    detailError.value =
      err instanceof ApiError
        ? err.message
        : `Không tìm thấy thông tin đơn hàng ${orderNum}.`
  } finally {
    loadingDetail.value = false
  }
}

async function handleLookupSubmit(): Promise<void> {
  const cleaned = lookupCode.value.trim()
  if (!cleaned) return
  await router.push(`/orders/${encodeURIComponent(cleaned)}`)
}

async function handleSimulateWebhook(): Promise<void> {
  if (!activeOrder.value) return
  simulatingWebhook.value = true
  try {
    const res = await apiClient.post<ApiDataEnvelope<DevWebhookSimulationResult>>(
      '/payments/dev-simulate-webhook',
      {
        order_number: activeOrder.value.order_number,
      },
    )
    toastStore.push({
      title: 'Xác nhận thanh toán mẫu thành công',
      description: `Đơn ${res.data.order_number} đã cập nhật trạng thái Đã thanh toán.`,
      variant: 'success',
    })
    await fetchOrderDetail(activeOrder.value.order_number)
    if (authStore.isAuthenticated) {
      void fetchOrdersList()
    }
  } catch (err) {
    toastStore.push({
      title: 'Không thể mô phỏng thanh toán',
      description: err instanceof ApiError ? err.message : 'Vui lòng thử lại sau.',
      variant: 'danger',
    })
  } finally {
    simulatingWebhook.value = false
  }
}

watch(
  currentOrderNumber,
  (orderNum) => {
    if (orderNum) {
      void fetchOrderDetail(orderNum)
    } else {
      activeOrder.value = null
      detailError.value = null
    }
  },
  { immediate: true },
)

watch(
  () => authStore.isAuthenticated,
  () => {
    void fetchOrdersList()
  },
)

onMounted(() => {
  void fetchOrdersList()
})
</script>

<template>
  <div class="orders-page container">
    <div class="orders-header">
      <div>
        <h1>Theo dõi đơn hàng</h1>
        <p class="orders-sub">
          Tra cứu tiến trình xử lý, trạng thái thanh toán và chi tiết sản phẩm theo mã đơn hàng.
        </p>
      </div>

      <form class="lookup-bar" @submit.prevent="handleLookupSubmit">
        <input
          v-model="lookupCode"
          type="search"
          class="input mono-code lookup-input"
          placeholder="Nhập mã đơn (VD: ORD-20261117-AB12)"
          aria-label="Nhập mã đơn hàng cần tra cứu"
        />
        <button type="submit" class="btn btn-secondary">
          <AppIcon name="search" />
          <span>Tra cứu</span>
        </button>
      </form>
    </div>

    <!-- Detailed Single Order View -->
    <section v-if="currentOrderNumber" class="order-detail-panel surface-panel">
      <div class="detail-nav-row">
        <RouterLink to="/orders" class="btn btn-ghost btn-sm">
          <AppIcon name="arrow-left" />
          <span>Danh sách đơn hàng</span>
        </RouterLink>
        <button
          type="button"
          class="btn btn-secondary btn-sm"
          :disabled="loadingDetail"
          @click="fetchOrderDetail(currentOrderNumber)"
        >
          <AppIcon name="refresh" />
          <span>Làm mới trạng thái</span>
        </button>
      </div>

      <div v-if="loadingDetail" class="skeleton detail-skeleton" />

      <div v-else-if="detailError || !activeOrder" class="callout callout-danger">
        <AppIcon name="alert" />
        <div>
          <p class="strong-line">Không tìm thấy đơn hàng {{ currentOrderNumber }}</p>
          <p>{{ detailError }}</p>
        </div>
      </div>

      <template v-else>
        <div class="order-top-summary">
          <div>
            <div class="badge-group">
              <span class="badge" :class="`badge-${getOrderStatusTone(activeOrder.status)}`">
                {{ getOrderStatusLabel(activeOrder.status) }}
              </span>
              <span
                class="badge"
                :class="`badge-${getPaymentStatusTone(activeOrder.payment_status)}`"
              >
                {{ getPaymentStatusLabel(activeOrder.payment_status) }}
              </span>
            </div>
            <h2 class="order-code-heading">
              Đơn hàng <span class="mono-code">{{ detailCode }}</span>
            </h2>
            <p class="order-created tabular">
              Đặt lúc {{ formatDate(activeOrder.created_at) }}
            </p>
          </div>

          <div class="order-grand-box tabular">
            <span class="grand-caption">Tổng giá trị đơn</span>
            <strong class="grand-amount">{{ formatVnd(activeOrder.grand_total_minor) }}</strong>
          </div>
        </div>

        <!-- Step-by-Step Progress Bar -->
        <div v-if="activeOrder.status === 'cancelled'" class="callout callout-danger">
          <AppIcon name="alert" />
          <span>Đơn hàng này đã được hủy và hoàn trả số lượng tồn kho về hệ thống.</span>
        </div>

        <ol v-else class="status-stepper" aria-label="Tiến độ xử lý đơn hàng">
          <li
            v-for="(step, idx) in progressSteps"
            :key="step.key"
            class="step-node"
            :class="{
              'is-done': idx <= getStepIndex(activeOrder.status),
              'is-current': idx === getStepIndex(activeOrder.status),
            }"
          >
            <span class="step-indicator tabular">
              <AppIcon v-if="idx < getStepIndex(activeOrder.status)" name="check" />
              <span v-else>{{ idx + 1 }}</span>
            </span>
            <span class="step-label">{{ step.label }}</span>
          </li>
        </ol>

        <!-- Pending Payment QR / Webhook Box -->
        <div
          v-if="
            activeOrder.payment_status === 'unpaid' &&
            activeOrder.status !== 'cancelled' &&
            activeOrder.payment_method !== 'cod'
          "
          class="unpaid-banner muted-block"
        >
          <div class="unpaid-info">
            <h3>Đơn hàng đang chờ xác nhận thanh toán</h3>
            <p>
              Phương thức: {{ getPaymentMethodLabel(activeOrder.payment_method) }}. Bạn có thể quét mã VietQR hoặc bấm xác nhận mẫu qua Webhook HMAC để kiểm thử luồng đối soát tự động.
            </p>
            <div v-if="activeOrder.payment_instruction?.account_number" class="inline-bank-meta">
              <span>Ngân hàng: <strong>{{ activeOrder.payment_instruction.bank_name }}</strong></span>
              <span>STK: <strong class="mono-code">{{ activeOrder.payment_instruction.account_number }}</strong></span>
              <span>Nội dung: <strong class="mono-code">{{ activeOrder.payment_instruction.transfer_content }}</strong></span>
            </div>
            <div class="unpaid-actions">
              <button
                type="button"
                class="btn btn-primary"
                :disabled="simulatingWebhook"
                @click="handleSimulateWebhook"
              >
                <AppIcon name="shield" />
                <span>Xác nhận thanh toán mẫu qua Webhook HMAC</span>
              </button>
            </div>
          </div>

          <img
            v-if="activeOrder.payment_instruction?.qr_image_url"
            :src="activeOrder.payment_instruction.qr_image_url"
            :alt="`VietQR ${activeOrder.order_number}`"
            class="inline-qr-img"
          />
        </div>

        <div class="order-columns">
          <div class="lines-wrapper">
            <h3>Sản phẩm trong đơn</h3>
            <div class="table-wrap">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Sản phẩm</th>
                    <th>Mã SKU</th>
                    <th>Đơn giá</th>
                    <th>SL</th>
                    <th>Thành tiền</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="line in activeOrder.lines" :key="line.sku">
                    <td>
                      <div class="td-product-name">{{ line.product_name }}</div>
                      <div class="td-variant">{{ line.variant_label }}</div>
                    </td>
                    <td class="mono-code">{{ line.sku }}</td>
                    <td class="tabular">{{ formatVnd(line.unit_price_minor) }}</td>
                    <td class="tabular">{{ line.quantity }}</td>
                    <td class="tabular strong-cell">{{ formatVnd(line.line_total_minor) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="recipient-summary muted-block">
            <h3>Thông tin giao nhận & thanh toán</h3>
            <dl class="meta-pairs">
              <div class="pair-row">
                <dt>Người nhận</dt>
                <dd>{{ activeOrder.customer_name }}</dd>
              </div>
              <div v-if="activeOrder.customer_phone" class="pair-row">
                <dt>Điện thoại</dt>
                <dd class="tabular">{{ activeOrder.customer_phone }}</dd>
              </div>
              <div class="pair-row">
                <dt>Địa chỉ giao</dt>
                <dd>{{ activeOrder.shipping_address }}</dd>
              </div>
              <div class="pair-row">
                <dt>Thanh toán</dt>
                <dd>{{ getPaymentMethodLabel(activeOrder.payment_method) }}</dd>
              </div>
            </dl>

            <dl class="meta-pairs totals-divider tabular">
              <div class="pair-row">
                <dt>Tạm tính</dt>
                <dd>{{ formatVnd(activeOrder.subtotal_minor) }}</dd>
              </div>
              <div class="pair-row">
                <dt>Giảm giá</dt>
                <dd>{{ formatVnd(activeOrder.discount_minor) }}</dd>
              </div>
              <div class="pair-row">
                <dt>Phí vận chuyển</dt>
                <dd>{{ formatVnd(activeOrder.shipping_fee_minor) }}</dd>
              </div>
              <div class="pair-row total-emphasis">
                <dt>Tổng thanh toán</dt>
                <dd>{{ formatVnd(activeOrder.grand_total_minor) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </template>
    </section>

    <!-- Orders List Section -->
    <section class="orders-list-section surface-panel">
      <div class="list-head">
        <h2>{{ historyHeading }}</h2>
        <RouterLink v-if="!authStore.isAuthenticated" to="/login" class="btn btn-secondary btn-sm">
          <AppIcon name="user" />
          <span>Đăng nhập để đồng bộ lịch sử</span>
        </RouterLink>
      </div>

      <div v-if="loadingList" class="skeleton list-skeleton" />

      <div v-else-if="listError" class="callout callout-warning">
        <AppIcon name="alert" />
        <span>{{ listError }}</span>
      </div>

      <template v-else-if="authStore.isAuthenticated">
        <div v-if="ordersList.length === 0" class="muted-block empty-orders">
          <p>Tài khoản của bạn chưa có đơn hàng nào.</p>
          <RouterLink to="/" class="btn btn-primary btn-sm">
            <span>Mua sắm ngay</span>
          </RouterLink>
        </div>

        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Mã đơn hàng</th>
                <th>Ngày đặt</th>
                <th>Phương thức</th>
                <th>Trạng thái đơn</th>
                <th>Thanh toán</th>
                <th>Tổng tiền</th>
                <th>Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ord in ordersList" :key="ord.order_number">
                <td class="mono-code">{{ ord.order_number }}</td>
                <td class="tabular">{{ formatDate(ord.created_at) }}</td>
                <td>{{ getPaymentMethodLabel(ord.payment_method) }}</td>
                <td>
                  <span class="badge" :class="`badge-${getOrderStatusTone(ord.status)}`">
                    {{ getOrderStatusLabel(ord.status) }}
                  </span>
                </td>
                <td>
                  <span class="badge" :class="`badge-${getPaymentStatusTone(ord.payment_status)}`">
                    {{ getPaymentStatusLabel(ord.payment_status) }}
                  </span>
                </td>
                <td class="tabular strong-cell">{{ formatVnd(ord.grand_total_minor) }}</td>
                <td>
                  <RouterLink :to="`/orders/${ord.order_number}`" class="btn btn-secondary btn-sm">
                    <span>Xem chi tiết</span>
                  </RouterLink>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <template v-else>
        <div v-if="cartStore.recentOrderNumbers.length === 0" class="muted-block empty-orders">
          <p>
            Chưa có đơn hàng nào được ghi nhận trên thiết bị này. Bạn có thể nhập mã đơn ở thanh tìm kiếm phía trên hoặc đăng nhập tài khoản.
          </p>
        </div>

        <div v-else class="recent-guest-chips">
          <RouterLink
            v-for="code in cartStore.recentOrderNumbers"
            :key="code"
            :to="`/orders/${code}`"
            class="guest-order-link"
          >
            <AppIcon name="package" />
            <span class="mono-code">{{ code }}</span>
            <AppIcon name="chevron-right" />
          </RouterLink>
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.orders-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.orders-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.orders-sub {
  margin-top: var(--space-1);
  color: var(--color-fg-muted);
  font-size: var(--text-sm);
}

.lookup-bar {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.lookup-input {
  min-width: 270px;
  width: auto;
}

.order-detail-panel,
.orders-list-section {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.detail-nav-row,
.list-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.order-top-summary {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  padding-bottom: var(--space-5);
  border-bottom: 1px solid var(--color-border);
}

.badge-group {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-2);
}

.order-code-heading {
  font-size: var(--text-h2);
}

.order-created {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.order-grand-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.grand-caption {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.grand-amount {
  font-size: 1.5rem;
  color: var(--color-primary);
}

/* Stepper */
.status-stepper {
  list-style: none;
  margin: 0;
  padding: var(--space-4);
  background-color: var(--color-surface-muted);
  border-radius: var(--radius-md);
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: var(--space-3);
}

.step-node {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  color: var(--color-fg-subtle);
  font-size: var(--text-sm);
}

.step-indicator {
  width: 28px;
  height: 28px;
  border-radius: var(--radius-pill);
  border: 1px solid var(--color-border-strong);
  background-color: var(--color-surface);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: var(--text-caption);
  font-weight: 600;
  flex-shrink: 0;
}

.step-node.is-done {
  color: var(--color-fg);
}

.step-node.is-done .step-indicator {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-on-primary);
}

.step-node.is-current .step-label {
  font-weight: 600;
  color: var(--color-primary);
}

.unpaid-banner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-6);
  flex-wrap: wrap;
}

.unpaid-info {
  flex: 1;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.unpaid-info p {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.inline-bank-meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-4);
  font-size: var(--text-sm);
}

.inline-qr-img {
  width: 156px;
  height: 156px;
  object-fit: contain;
  border-radius: var(--radius-md);
  background-color: var(--color-surface);
  padding: var(--space-2);
}

.order-columns {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
  gap: var(--space-6);
  align-items: start;
}

.lines-wrapper {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.td-product-name {
  font-weight: 600;
  color: var(--color-fg);
}

.td-variant {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.strong-cell {
  font-weight: 600;
}

.recipient-summary {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.meta-pairs {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

.pair-row {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.pair-row dt {
  color: var(--color-fg-muted);
}

.pair-row dd {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

.totals-divider {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
}

.total-emphasis {
  font-weight: 700;
  color: var(--color-primary);
}

.total-emphasis dt {
  color: var(--color-fg);
}

.empty-orders {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.recent-guest-chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.guest-order-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background-color: var(--color-surface);
  font-weight: 600;
}

.guest-order-link:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.detail-skeleton,
.list-skeleton {
  height: 180px;
}

.strong-line {
  font-weight: 600;
}

@media (max-width: 960px) {
  .status-stepper {
    grid-template-columns: minmax(0, 1fr);
  }

  .order-columns {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
