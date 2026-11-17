<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ApiError, apiClient } from '../../shared/api/client'
import type {
  AdminOrderTransitionStatus,
  ApiDataEnvelope,
  OrderDetail,
} from '../../shared/api/types'
import { useToastStore } from '../../shared/state/toastStore'
import AppIcon from '../../shared/ui/AppIcon.vue'
import {
  formatDate,
  formatVnd,
  getOrderStatusLabel,
  getOrderStatusTone,
  getPaymentMethodLabel,
  getPaymentStatusLabel,
  getPaymentStatusTone,
} from '../../shared/utils/format'

defineProps<{
  orders: OrderDetail[]
  loading: boolean
}>()

const emit = defineEmits<{
  refresh: []
  orderUpdated: [updated: OrderDetail]
}>()

const toastStore = useToastStore()
const selectedNextStatus = reactive<Record<string, AdminOrderTransitionStatus>>({})
const updatingOrderNumber = ref<string | null>(null)

const transitionChoices: ReadonlyArray<{ value: AdminOrderTransitionStatus; label: string }> = [
  { value: 'processing', label: 'Đang chuẩn bị hàng' },
  { value: 'shipped', label: 'Đang giao hàng' },
  { value: 'completed', label: 'Hoàn tất' },
  { value: 'cancelled', label: 'Hủy đơn hàng' },
]

function getSelectedFor(order: OrderDetail): AdminOrderTransitionStatus {
  const existing = selectedNextStatus[order.order_number]
  if (existing) return existing
  if (order.status === 'pending_payment' || order.status === 'paid') return 'processing'
  if (order.status === 'processing') return 'shipped'
  if (order.status === 'shipped') return 'completed'
  return 'completed'
}

function setSelectedFor(orderNumber: string, event: Event): void {
  const target = event.target as HTMLSelectElement
  selectedNextStatus[orderNumber] = target.value as AdminOrderTransitionStatus
}

async function applyStatusUpdate(order: OrderDetail): Promise<void> {
  const nextStatus = getSelectedFor(order)
  updatingOrderNumber.value = order.order_number
  try {
    const res = await apiClient.patch<ApiDataEnvelope<OrderDetail>>(
      `/admin/orders/${order.order_number}/status`,
      {
        status: nextStatus,
      },
    )
    emit('orderUpdated', res.data)
    toastStore.push({
      title: `Đã cập nhật đơn ${order.order_number}`,
      description: `Trạng thái mới: ${getOrderStatusLabel(res.data.status)}`,
      variant: 'success',
    })
  } catch (err) {
    toastStore.push({
      title: 'Cập nhật trạng thái không thành công',
      description: err instanceof ApiError ? err.message : 'Vui lòng kiểm tra quy tắc chuyển trạng thái.',
      variant: 'danger',
    })
  } finally {
    updatingOrderNumber.value = null
  }
}
</script>

<template>
  <div class="orders-tab">
    <div class="tab-toolbar">
      <div>
        <h2>Điều phối và xử lý đơn hàng ({{ orders.length }})</h2>
        <p class="tab-sub">
          Chuyển trạng thái vận hành từ chuẩn bị hàng, bàn giao vận chuyển đến hoàn tất hoặc hủy đơn.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-secondary btn-sm"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <AppIcon name="refresh" />
        <span>Tải lại danh sách</span>
      </button>
    </div>

    <div v-if="loading && orders.length === 0" class="skeleton orders-skeleton" />

    <div v-else-if="orders.length === 0" class="muted-block">
      <p>Hiện chưa có đơn hàng nào phát sinh trên hệ thống.</p>
    </div>

    <div v-else class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Mã đơn</th>
            <th>Khách hàng</th>
            <th>Ngày tạo</th>
            <th>Thanh toán</th>
            <th>Trạng thái đơn</th>
            <th>Tổng tiền</th>
            <th>Cập nhật trạng thái</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="ord in orders" :key="ord.order_number">
            <td>
              <RouterLink :to="`/orders/${ord.order_number}`" class="mono-code order-link">
                {{ ord.order_number }}
              </RouterLink>
            </td>
            <td>
              <div class="cust-name">{{ ord.customer_name }}</div>
              <div class="cust-method">{{ getPaymentMethodLabel(ord.payment_method) }}</div>
            </td>
            <td class="tabular">{{ formatDate(ord.created_at) }}</td>
            <td>
              <span class="badge" :class="`badge-${getPaymentStatusTone(ord.payment_status)}`">
                {{ getPaymentStatusLabel(ord.payment_status) }}
              </span>
            </td>
            <td>
              <span class="badge" :class="`badge-${getOrderStatusTone(ord.status)}`">
                {{ getOrderStatusLabel(ord.status) }}
              </span>
            </td>
            <td class="tabular strong-amount">{{ formatVnd(ord.grand_total_minor) }}</td>
            <td>
              <div
                v-if="ord.status !== 'completed' && ord.status !== 'cancelled'"
                class="transition-controls"
              >
                <select
                  class="select select-compact"
                  :value="getSelectedFor(ord)"
                  :aria-label="`Chọn trạng thái cho đơn ${ord.order_number}`"
                  @change="setSelectedFor(ord.order_number, $event)"
                >
                  <option
                    v-for="choice in transitionChoices"
                    :key="choice.value"
                    :value="choice.value"
                  >
                    {{ choice.label }}
                  </option>
                </select>
                <button
                  type="button"
                  class="btn btn-primary btn-sm"
                  :disabled="updatingOrderNumber === ord.order_number"
                  @click="applyStatusUpdate(ord)"
                >
                  <span>Lưu</span>
                </button>
              </div>
              <span v-else class="closed-note">Đã đóng quy trình</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.orders-tab {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.tab-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.tab-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.order-link {
  color: var(--color-primary);
  font-weight: 600;
}

.order-link:hover {
  text-decoration: underline;
}

.cust-name {
  font-weight: 600;
  color: var(--color-fg);
}

.cust-method {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.strong-amount {
  font-weight: 600;
}

.transition-controls {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.select-compact {
  min-height: 36px;
  padding: var(--space-1) var(--space-2);
  font-size: var(--text-label);
  min-width: 164px;
}

.closed-note {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.orders-skeleton {
  height: 220px;
}
</style>
