<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseIcon from '../../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../../components/common/LoadingSpinner.vue'
import { ApiError } from '../../../../services/axiosClient'
import { orderService } from '../../../../services/order.service'
import type { OrderDetail } from '../../../../types/api.types'
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

const orders = ref<OrderDetail[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const statusFilter = ref<string>('all')

const filterTabs: ReadonlyArray<{ value: string; label: string }> = [
  { value: 'all', label: 'Tất cả' },
  { value: 'pending_payment', label: 'Chờ thanh toán' },
  { value: 'paid', label: 'Đã thanh toán' },
  { value: 'processing', label: 'Chuẩn bị hàng' },
  { value: 'shipped', label: 'Đang giao' },
  { value: 'completed', label: 'Hoàn tất' },
  { value: 'cancelled', label: 'Đã hủy' },
]

const columns: readonly TableColumn[] = [
  { key: 'code', label: 'Mã đơn hàng' },
  { key: 'customer', label: 'Khách hàng' },
  { key: 'date', label: 'Thời gian đặt' },
  { key: 'payment', label: 'Thanh toán' },
  { key: 'status', label: 'Trạng thái đơn' },
  { key: 'total', label: 'Tổng tiền' },
  { key: 'action', label: 'Điều phối' },
]

const filteredOrders = computed(() => {
  if (statusFilter.value === 'all') return orders.value
  return orders.value.filter((o) => o.status === statusFilter.value)
})

const totalCount = computed(() => filteredOrders.value.length)

async function loadOrders(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  try {
    orders.value = await orderService.getAdminOrders()
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError ? err.message : 'Không thể tải danh sách đơn hàng.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadOrders()
})
</script>

<template>
  <div class="order-manage-view">
    <div class="view-head">
      <div>
        <h1>Quản lý đơn hàng ({{ totalCount }})</h1>
        <p class="view-sub">
          Theo dõi dòng trạng thái thanh toán và chuyển bước xử lý kho vận.
        </p>
      </div>

      <button type="button" class="btn btn-secondary btn-sm" @click="loadOrders">
        <BaseIcon name="refresh" />
        <span>Tải lại</span>
      </button>
    </div>

    <div class="status-pills" role="tablist" aria-label="Lọc trạng thái đơn hàng">
      <button
        v-for="tab in filterTabs"
        :key="tab.value"
        type="button"
        role="tab"
        class="filter-pill"
        :class="{ 'is-active': statusFilter === tab.value }"
        :aria-selected="statusFilter === tab.value"
        @click="statusFilter = tab.value"
      >
        {{ tab.label }}
      </button>
    </div>

    <div v-if="errorMessage" class="callout callout-danger">
      <BaseIcon name="alert" />
      <span>{{ errorMessage }}</span>
    </div>

    <LoadingSpinner v-else-if="loading" label="Đang tải danh sách đơn hàng…" :rows="4" />

    <section v-else class="table-panel surface-panel">
      <DataTable
        :columns="columns"
        :has-rows="filteredOrders.length > 0"
        empty-message="Không có đơn hàng nào thuộc trạng thái đang chọn."
      >
        <tr v-for="ord in filteredOrders" :key="ord.order_number">
          <td>
            <RouterLink :to="`/admin/orders/${ord.order_number}`" class="mono-code code-link">
              {{ ord.order_number }}
            </RouterLink>
          </td>
          <td>
            <div class="strong-cell">{{ ord.customer_name }}</div>
            <div class="sub-cell">{{ getPaymentMethodLabel(ord.payment_method) }}</div>
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
          <td class="tabular strong-cell">{{ formatVnd(ord.grand_total_minor) }}</td>
          <td>
            <RouterLink
              :to="`/admin/orders/${ord.order_number}`"
              class="btn btn-secondary btn-sm"
            >
              <span>Xử lý đơn</span>
              <BaseIcon name="chevron-right" />
            </RouterLink>
          </td>
        </tr>
      </DataTable>
    </section>
  </div>
</template>

<style scoped>
.order-manage-view {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.view-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.view-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.status-pills {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.filter-pill {
  min-height: 38px;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-pill);
  border: 1px solid var(--color-border-strong);
  background-color: transparent;
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
  white-space: nowrap;
  cursor: pointer;
}

.filter-pill.is-active {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-on-primary);
  font-weight: 600;
}

.table-panel {
  overflow: hidden;
}

.code-link {
  color: var(--color-primary);
  font-weight: 600;
}

.code-link:hover {
  text-decoration: underline;
}

.strong-cell {
  font-weight: 600;
}

.sub-cell {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}
</style>
