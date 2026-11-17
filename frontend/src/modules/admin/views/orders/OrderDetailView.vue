<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import BaseButton from '../../../../components/base/BaseButton.vue'
import BaseIcon from '../../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../../components/common/LoadingSpinner.vue'
import { useNotification } from '../../../../composables/useNotification'
import { ApiError } from '../../../../services/axiosClient'
import { orderService } from '../../../../services/order.service'
import type { AdminOrderTransitionStatus, OrderDetail } from '../../../../types/api.types'
import { formatVnd } from '../../../../utils/formatCurrency'
import {
  formatDate,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../../../../utils/formatDate'
import DataTable, { type TableColumn } from '../../components/DataTable.vue'

const route = useRoute()
const { success, error } = useNotification()

const order = ref<OrderDetail | null>(null)
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const nextStatus = ref<AdminOrderTransitionStatus>('processing')
const saving = ref(false)

const orderCode = computed(() =>
  typeof route.params.orderNumber === 'string' ? route.params.orderNumber.trim() : '',
)

const isTerminal = computed(
  () => order.value?.status === 'completed' || order.value?.status === 'cancelled',
)

const transitionChoices: ReadonlyArray<{ value: AdminOrderTransitionStatus; label: string }> = [
  { value: 'processing', label: 'Đang chuẩn bị hàng' },
  { value: 'shipped', label: 'Đang giao hàng' },
  { value: 'completed', label: 'Hoàn tất' },
  { value: 'cancelled', label: 'Hủy đơn hàng' },
]

const lineColumns: readonly TableColumn[] = [
  { key: 'product', label: 'Sản phẩm' },
  { key: 'sku', label: 'Mã SKU' },
  { key: 'price', label: 'Đơn giá' },
  { key: 'qty', label: 'Số lượng' },
  { key: 'total', label: 'Thành tiền' },
]

function syncDefaultNextStatus(ord: OrderDetail): void {
  if (ord.status === 'pending_payment' || ord.status === 'paid') {
    nextStatus.value = 'processing'
  } else if (ord.status === 'processing') {
    nextStatus.value = 'shipped'
  } else {
    nextStatus.value = 'completed'
  }
}

async function fetchDetail(): Promise<void> {
  if (!orderCode.value) return
  loading.value = true
  errorMessage.value = null
  try {
    const data = await orderService.getOrderByNumber(orderCode.value)
    order.value = data
    syncDefaultNextStatus(data)
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError ? err.message : 'Không tìm thấy chi tiết đơn hàng.'
  } finally {
    loading.value = false
  }
}

async function handleStatusSave(): Promise<void> {
  if (!order.value) return
  saving.value = true
  try {
    const updated = await orderService.updateAdminOrderStatus(
      order.value.order_number,
      nextStatus.value,
    )
    order.value = updated
    syncDefaultNextStatus(updated)
    success(
      `Đã cập nhật ${updated.order_number}`,
      `Trạng thái mới: ${getOrderStatusLabel(updated.status)}`,
    )
  } catch (err) {
    error(
      'Cập nhật trạng thái không thành công',
      err instanceof ApiError ? err.message : 'Vui lòng kiểm tra quy tắc chuyển bước.',
    )
  } finally {
    saving.value = false
  }
}

watch(orderCode, () => {
  void fetchDetail()
})

onMounted(() => {
  void fetchDetail()
})
</script>

<template>
  <div class="admin-order-detail">
    <div class="top-nav">
      <RouterLink to="/admin/orders" class="btn btn-ghost btn-sm">
        <BaseIcon name="arrow-left" />
        <span>Danh sách đơn hàng</span>
      </RouterLink>
      <button type="button" class="btn btn-secondary btn-sm" @click="fetchDetail">
        <BaseIcon name="refresh" />
        <span>Làm mới</span>
      </button>
    </div>

    <LoadingSpinner v-if="loading" label="Đang tải thông tin đơn hàng…" :rows="4" />

    <div v-else-if="errorMessage || !order" class="callout callout-danger">
      <BaseIcon name="alert" />
      <span>{{ errorMessage }}</span>
    </div>

    <template v-else>
      <section class="summary-panel surface-panel">
        <div class="summary-top">
          <div>
            <div class="badges-row">
              <span class="badge" :class="`badge-${getOrderStatusTone(order.status)}`">
                {{ getOrderStatusLabel(order.status) }}
              </span>
              <span class="badge" :class="`badge-${getPaymentStatusTone(order.payment_status)}`">
                {{ getPaymentStatusLabel(order.payment_status) }}
              </span>
            </div>
            <h1>
              Đơn hàng <span class="mono-code">{{ orderCode }}</span>
            </h1>
            <p class="sub-meta tabular">Tạo lúc {{ formatDate(order.created_at) }}</p>
          </div>

          <form v-if="!isTerminal" class="transition-box" @submit.prevent="handleStatusSave">
            <label for="admin-status-select" class="field-label">Chuyển trạng thái xử lý</label>
            <div class="transition-controls">
              <select id="admin-status-select" v-model="nextStatus" class="select">
                <option
                  v-for="opt in transitionChoices"
                  :key="opt.value"
                  :value="opt.value"
                >
                  {{ opt.label }}
                </option>
              </select>
              <BaseButton type="submit" :loading="saving">
                <span>Cập nhật</span>
              </BaseButton>
            </div>
          </form>
        </div>

        <div class="detail-grid">
          <DataTable :columns="lineColumns" :has-rows="order.lines.length > 0">
            <tr v-for="line in order.lines" :key="line.sku">
              <td>
                <div class="strong-cell">{{ line.product_name }}</div>
                <div class="sub-cell">{{ line.variant_label }}</div>
              </td>
              <td class="mono-code">{{ line.sku }}</td>
              <td class="tabular">{{ formatVnd(line.unit_price_minor) }}</td>
              <td class="tabular">{{ line.quantity }}</td>
              <td class="tabular strong-cell">{{ formatVnd(line.line_total_minor) }}</td>
            </tr>
          </DataTable>

          <div class="side-meta muted-block">
            <h2>Thông tin khách nhận</h2>
            <dl class="meta-dl">
              <div class="m-row">
                <dt>Khách hàng</dt>
                <dd>{{ order.customer_name }}</dd>
              </div>
              <div v-if="order.customer_phone" class="m-row">
                <dt>Điện thoại</dt>
                <dd class="tabular">{{ order.customer_phone }}</dd>
              </div>
              <div class="m-row">
                <dt>Địa chỉ</dt>
                <dd>{{ order.shipping_address }}</dd>
              </div>
              <div class="m-row">
                <dt>Thanh toán</dt>
                <dd>{{ getPaymentMethodLabel(order.payment_method) }}</dd>
              </div>
            </dl>

            <dl class="meta-dl totals-part tabular">
              <div class="m-row">
                <dt>Tạm tính</dt>
                <dd>{{ formatVnd(order.subtotal_minor) }}</dd>
              </div>
              <div class="m-row">
                <dt>Giảm giá</dt>
                <dd>{{ formatVnd(order.discount_minor) }}</dd>
              </div>
              <div class="m-row">
                <dt>Phí giao hàng</dt>
                <dd>{{ formatVnd(order.shipping_fee_minor) }}</dd>
              </div>
              <div class="m-row grand-line">
                <dt>Tổng cộng</dt>
                <dd>{{ formatVnd(order.grand_total_minor) }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.admin-order-detail {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.top-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.summary-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.summary-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  padding-bottom: var(--space-5);
  border-bottom: 1px solid var(--color-border);
}

.badges-row {
  display: flex;
  gap: var(--space-2);
  margin-bottom: var(--space-2);
}

.sub-meta {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.transition-box {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.transition-controls {
  display: flex;
  gap: var(--space-2);
}

.detail-grid {
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

.side-meta {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.side-meta h2 {
  font-size: var(--text-title);
  text-wrap: balance;
}

.admin-order-detail :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.admin-order-detail button:active:not(:disabled) {
  transform: translateY(1px);
}

.admin-order-detail button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

.meta-dl {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  font-size: var(--text-sm);
}

.m-row {
  display: flex;
  justify-content: space-between;
  gap: var(--space-3);
}

.m-row dt {
  color: var(--color-fg-muted);
}

.m-row dd {
  margin: 0;
  font-weight: 500;
  text-align: right;
}

.totals-part {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
}

.grand-line {
  font-weight: 700;
  color: var(--color-primary);
}

.grand-line dt {
  color: var(--color-fg);
}

@media (max-width: 960px) {
  .detail-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
