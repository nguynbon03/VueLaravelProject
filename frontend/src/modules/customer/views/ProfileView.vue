<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../components/common/LoadingSpinner.vue'
import { useAuth } from '../../../composables/useAuth'
import { useCart } from '../../../composables/useCart'
import { useNotification } from '../../../composables/useNotification'
import { ApiError } from '../../../services/axiosClient'
import { orderService } from '../../../services/order.service'
import type { OrderDetail, OrderStatus } from '../../../types/api.types'
import { formatVnd } from '../../../utils/formatCurrency'
import {
  formatDate,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../../../utils/formatDate'

const route = useRoute()
const router = useRouter()
const { user, isAuthenticated, isAdmin } = useAuth()
const { recentOrderNumbers } = useCart()
const { success, error } = useNotification()

const orders = ref<OrderDetail[]>([])
const activeOrder = ref<OrderDetail | null>(null)
const loadingList = ref(false)
const loadingDetail = ref(false)
const detailError = ref<string | null>(null)
const lookupInput = ref<string>('')
const simulating = ref(false)

const selectedOrderNumber = computed(() => {
  if (typeof route.params.orderNumber === 'string' && route.params.orderNumber.trim() !== '') {
    return route.params.orderNumber.trim()
  }
  if (typeof route.query.order === 'string' && route.query.order.trim() !== '') {
    return route.query.order.trim()
  }
  return ''
})

const detailCode = computed(() => activeOrder.value?.order_number ?? '')
const historyTitle = computed(() =>
  isAuthenticated.value ? 'Lịch sử đơn hàng tài khoản' : 'Đơn hàng gần đây trên thiết bị',
)

const steps: ReadonlyArray<{ key: OrderStatus; label: string }> = [
  { key: 'pending_payment', label: 'Khởi tạo đơn' },
  { key: 'paid', label: 'Đã thanh toán' },
  { key: 'processing', label: 'Chuẩn bị hàng' },
  { key: 'shipped', label: 'Đang giao' },
  { key: 'completed', label: 'Hoàn tất' },
]

function stepIndexOf(status: OrderStatus): number {
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
    default:
      return -1
  }
}

async function loadMyOrders(): Promise<void> {
  if (!isAuthenticated.value) {
    orders.value = []
    return
  }
  loadingList.value = true
  try {
    orders.value = await orderService.getMyOrders()
  } catch {
    orders.value = []
  } finally {
    loadingList.value = false
  }
}

async function loadOrderDetail(code: string): Promise<void> {
  if (!code) {
    activeOrder.value = null
    detailError.value = null
    return
  }
  loadingDetail.value = true
  detailError.value = null
  try {
    activeOrder.value = await orderService.getOrderByNumber(code)
  } catch (err) {
    activeOrder.value = null
    detailError.value =
      err instanceof ApiError ? err.message : `Không tìm thấy đơn hàng ${code}.`
  } finally {
    loadingDetail.value = false
  }
}

async function submitLookup(): Promise<void> {
  const code = lookupInput.value.trim()
  if (!code) return
  await router.push({ path: '/profile', query: { order: code } })
}

async function selectOrder(code: string): Promise<void> {
  await router.push({ path: '/profile', query: { order: code } })
}

async function clearSelectedOrder(): Promise<void> {
  await router.push({ path: '/profile' })
}

async function simulatePayment(): Promise<void> {
  if (!activeOrder.value) return
  simulating.value = true
  try {
    const res = await orderService.simulateDevWebhook(activeOrder.value.order_number)
    success('Xác nhận thanh toán mẫu thành công', `Đơn ${res.order_number} đã thanh toán.`)
    await loadOrderDetail(activeOrder.value.order_number)
    if (isAuthenticated.value) {
      void loadMyOrders()
    }
  } catch (err) {
    error(
      'Không thể mô phỏng thanh toán',
      err instanceof ApiError ? err.message : 'Vui lòng thử lại sau.',
    )
  } finally {
    simulating.value = false
  }
}

watch(
  selectedOrderNumber,
  (code) => {
    void loadOrderDetail(code)
  },
  { immediate: true },
)

watch(isAuthenticated, () => {
  void loadMyOrders()
})

onMounted(() => {
  void loadMyOrders()
})
</script>

<template>
  <div class="profile-page container">
    <section class="profile-banner surface-panel">
      <div class="profile-info">
        <h1>Hồ sơ & Theo dõi đơn hàng</h1>
        <template v-if="isAuthenticated && user">
          <div class="identity-row">
            <strong>{{ user.name }}</strong>
            <span class="mono-code">{{ user.email }}</span>
            <span class="badge" :class="isAdmin ? 'badge-primary' : 'badge-neutral'">
              {{ isAdmin ? 'Quản trị Admin' : 'Khách hàng' }}
            </span>
          </div>
        </template>
        <p v-else class="guest-copy">
          Bạn có thể tra cứu trực tiếp bằng mã đơn hàng hoặc đăng nhập để đồng bộ toàn bộ lịch sử mua sắm.
        </p>
      </div>

      <form class="lookup-form" @submit.prevent="submitLookup">
        <input
          v-model="lookupInput"
          type="search"
          class="input mono-code lookup-field"
          placeholder="Nhập mã đơn (ORD-...)"
          aria-label="Nhập mã đơn hàng"
        />
        <button type="submit" class="btn btn-secondary">
          <BaseIcon name="search" />
          <span>Tra cứu</span>
        </button>
      </form>
    </section>

    <!-- Active Order Detail -->
    <section v-if="selectedOrderNumber" class="order-detail surface-panel">
      <div class="detail-bar">
        <button type="button" class="btn btn-ghost btn-sm" @click="clearSelectedOrder">
          <BaseIcon name="arrow-left" />
          <span>Đóng chi tiết</span>
        </button>
        <button
          type="button"
          class="btn btn-secondary btn-sm"
          :disabled="loadingDetail"
          @click="loadOrderDetail(selectedOrderNumber)"
        >
          <BaseIcon name="refresh" />
          <span>Làm mới</span>
        </button>
      </div>

      <LoadingSpinner v-if="loadingDetail" label="Đang tải chi tiết đơn hàng…" :rows="3" />

      <div v-else-if="detailError || !activeOrder" class="callout callout-danger">
        <BaseIcon name="alert" />
        <span>{{ detailError }}</span>
      </div>

      <template v-else>
        <div class="detail-summary">
          <div>
            <div class="badges-row">
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
            <h2>
              Đơn hàng <span class="mono-code">{{ detailCode }}</span>
            </h2>
            <p class="date-note tabular">Ngày tạo: {{ formatDate(activeOrder.created_at) }}</p>
          </div>

          <div class="grand-box tabular">
            <span>Tổng thanh toán</span>
            <strong>{{ formatVnd(activeOrder.grand_total_minor) }}</strong>
          </div>
        </div>

        <div
          v-if="
            activeOrder.status === 'cancelled' ||
            activeOrder.status === 'refund_required' ||
            activeOrder.status === 'paid_pending_refund'
          "
          class="callout callout-warning"
        >
          <BaseIcon name="alert" />
          <span>
            Trạng thái hiện tại: <strong>{{ getOrderStatusLabel(activeOrder.status) }}</strong>.
          </span>
        </div>

        <ol v-else class="progress-track" aria-label="Tiến độ xử lý đơn">
          <li
            v-for="(step, idx) in steps"
            :key="step.key"
            class="track-step"
            :class="{
              'is-done': idx <= stepIndexOf(activeOrder.status),
              'is-current': idx === stepIndexOf(activeOrder.status),
            }"
          >
            <span class="step-dot tabular">
              <BaseIcon v-if="idx < stepIndexOf(activeOrder.status)" name="check" />
              <span v-else>{{ idx + 1 }}</span>
            </span>
            <span>{{ step.label }}</span>
          </li>
        </ol>

        <div
          v-if="
            activeOrder.payment_status === 'unpaid' &&
            activeOrder.status === 'pending_payment' &&
            activeOrder.payment_method !== 'cod'
          "
          class="unpaid-box muted-block"
        >
          <div class="unpaid-text">
            <h3>Chờ xác nhận thanh toán</h3>
            <p>
              Quét mã VietQR hoặc bấm xác nhận Webhook HMAC để kiểm thử luồng đối soát tự động trên môi trường Local.
            </p>
            <BaseButton :loading="simulating" @click="simulatePayment">
              <BaseIcon name="shield" />
              <span>Xác nhận Webhook HMAC</span>
            </BaseButton>
          </div>

          <img
            v-if="activeOrder.payment_instruction?.qr_image_url"
            :src="activeOrder.payment_instruction.qr_image_url"
            :alt="`VietQR ${activeOrder.order_number}`"
            class="small-qr"
          />
        </div>

        <div class="detail-cols">
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
                    <div class="strong-cell">{{ line.product_name }}</div>
                    <div class="sub-cell">{{ line.variant_label }}</div>
                  </td>
                  <td class="mono-code">{{ line.sku }}</td>
                  <td class="tabular">{{ formatVnd(line.unit_price_minor) }}</td>
                  <td class="tabular">{{ line.quantity }}</td>
                  <td class="tabular strong-cell">{{ formatVnd(line.line_total_minor) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="shipping-card muted-block">
            <h3>Thông tin người nhận</h3>
            <dl class="ship-dl">
              <div class="ship-row">
                <dt>Họ tên</dt>
                <dd>{{ activeOrder.customer_name }}</dd>
              </div>
              <div class="ship-row">
                <dt>Địa chỉ</dt>
                <dd>{{ activeOrder.shipping_address }}</dd>
              </div>
              <div class="ship-row">
                <dt>Phương thức</dt>
                <dd>{{ getPaymentMethodLabel(activeOrder.payment_method) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </template>
    </section>

    <!-- Order History List -->
    <section class="history-panel surface-panel">
      <div class="history-head">
        <h2>{{ historyTitle }}</h2>
        <RouterLink v-if="!isAuthenticated" to="/auth/login" class="btn btn-secondary btn-sm">
          <BaseIcon name="user" />
          <span>Đăng nhập</span>
        </RouterLink>
      </div>

      <LoadingSpinner v-if="loadingList" label="Đang tải danh sách đơn hàng…" :rows="3" />

      <template v-else-if="isAuthenticated">
        <div v-if="orders.length === 0" class="muted-block empty-row">
          <p>Tài khoản của bạn chưa có đơn hàng nào.</p>
          <RouterLink to="/products" class="btn btn-primary btn-sm">
            <span>Mua sắm ngay</span>
          </RouterLink>
        </div>

        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Mã đơn</th>
                <th>Ngày đặt</th>
                <th>Phương thức</th>
                <th>Trạng thái</th>
                <th>Thanh toán</th>
                <th>Tổng tiền</th>
                <th>Chi tiết</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="ord in orders" :key="ord.order_number">
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
                  <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    @click="selectOrder(ord.order_number)"
                  >
                    <span>Xem đơn</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>

      <template v-else>
        <div v-if="recentOrderNumbers.length === 0" class="muted-block">
          <p>Chưa có mã đơn hàng nào lưu trên trình duyệt này.</p>
        </div>
        <div v-else class="guest-codes">
          <button
            v-for="code in recentOrderNumbers"
            :key="code"
            type="button"
            class="code-chip"
            @click="selectOrder(code)"
          >
            <BaseIcon name="package" />
            <span class="mono-code">{{ code }}</span>
            <BaseIcon name="chevron-right" />
          </button>
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.profile-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.profile-banner,
.order-detail,
.history-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.profile-banner {
  flex-direction: row;
  align-items: flex-end;
  justify-content: space-between;
  flex-wrap: wrap;
}

.profile-info {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.identity-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
  font-size: var(--text-sm);
}

.guest-copy {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.lookup-form {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.lookup-field {
  min-width: 240px;
  width: auto;
}

.detail-bar,
.history-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.detail-summary {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  padding-bottom: var(--space-4);
  border-bottom: 1px solid var(--color-border);
}

.badges-row {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-2);
}

.date-note {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.grand-box {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.grand-box span {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.grand-box strong {
  font-size: 1.5rem;
  color: var(--color-primary);
}

.progress-track {
  list-style: none;
  margin: 0;
  padding: var(--space-4);
  background-color: var(--color-surface-muted);
  border-radius: var(--radius-md);
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: var(--space-3);
}

.track-step {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-fg-subtle);
}

.step-dot {
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

.track-step.is-done {
  color: var(--color-fg);
}

.track-step.is-done .step-dot {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-on-primary);
}

.track-step.is-current {
  font-weight: 600;
  color: var(--color-primary);
}

.unpaid-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-6);
  flex-wrap: wrap;
}

.unpaid-text {
  flex: 1;
  min-width: 240px;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
}

.unpaid-text p {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.small-qr {
  width: 148px;
  height: 148px;
  object-fit: contain;
  background-color: var(--color-surface);
  padding: var(--space-2);
  border-radius: var(--radius-sm);
}

.detail-cols {
  display: grid;
  grid-template-columns: minmax(0, 1.4fr) minmax(0, 0.9fr);
  gap: var(--space-6);
  align-items: start;
}

.strong-cell {
  font-weight: 600;
}

.sub-cell {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.shipping-card {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.ship-dl {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

.ship-row {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.ship-row dt {
  color: var(--color-fg-muted);
}

.ship-row dd {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

.empty-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.guest-codes {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
}

.code-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-4);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  background-color: transparent;
  font-weight: 600;
  cursor: pointer;
}

.code-chip:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

@media (max-width: 960px) {
  .progress-track,
  .detail-cols {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
