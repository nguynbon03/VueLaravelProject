<script setup lang="ts">
import { onMounted, ref } from 'vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../components/common/LoadingSpinner.vue'
import { ApiError } from '../../../services/axiosClient'
import { orderService } from '../../../services/order.service'
import type { AdminOverviewAnalytics, OrderDetail } from '../../../types/api.types'
import { formatNumber, formatVnd } from '../../../utils/formatCurrency'
import { getStockSummary } from '../../../utils/formatDate'
import DataTable, { type TableColumn } from '../components/DataTable.vue'
import RevenueChart from '../components/RevenueChart.vue'
import StatCard from '../components/StatCard.vue'

const overview = ref<AdminOverviewAnalytics | null>(null)
const orders = ref<OrderDetail[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const lowStockColumns: readonly TableColumn[] = [
  { key: 'sku', label: 'Mã SKU' },
  { key: 'product', label: 'Sản phẩm' },
  { key: 'variant', label: 'Phân loại' },
  { key: 'stock', label: 'Tồn khả dụng' },
  { key: 'action', label: 'Thao tác' },
]

async function fetchDashboard(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  try {
    const [analyticsData, ordersData] = await Promise.all([
      orderService.getAdminAnalytics(),
      orderService.getAdminOrders(),
    ])
    overview.value = analyticsData
    orders.value = ordersData
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể tải số liệu tổng quan vận hành.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void fetchDashboard()
})
</script>

<template>
  <div class="dashboard-view">
    <div class="view-head">
      <div>
        <h1>Tổng quan vận hành</h1>
        <p class="view-sub">
          Giám sát doanh thu thực thu, hàng đợi Transactional Outbox và cảnh báo tồn kho thấp.
        </p>
      </div>

      <button
        type="button"
        class="btn btn-secondary btn-sm"
        :disabled="loading"
        @click="fetchDashboard"
      >
        <BaseIcon name="refresh" />
        <span>Làm mới</span>
      </button>
    </div>

    <div v-if="errorMessage" class="callout callout-warning">
      <BaseIcon name="alert" />
      <span>{{ errorMessage }}</span>
    </div>

    <LoadingSpinner v-if="loading && !overview" label="Đang tải chỉ số vận hành…" :rows="4" />

    <template v-else-if="overview">
      <div class="stats-grid">
        <StatCard
          label="Tổng doanh thu GMV"
          :value="formatVnd(overview.gmv_minor)"
          :context="`Tính trên ${formatNumber(overview.paid_orders_count)} đơn đã thu tiền`"
          icon="chart"
        />
        <StatCard
          label="Đơn đã thanh toán"
          :value="`${formatNumber(overview.paid_orders_count)} / ${formatNumber(overview.total_orders_count)}`"
          context="Tỷ lệ hoàn tất thanh toán trên tổng đơn"
          icon="check-circle"
        />
        <StatCard
          label="Giá trị đơn trung bình"
          :value="formatVnd(overview.avg_order_value_minor)"
          :context="`Đang kinh doanh ${formatNumber(overview.products_count)} sản phẩm SPU`"
          icon="bag"
        />
        <StatCard
          label="Hàng đợi Outbox Relay"
          :value="`${formatNumber(overview.outbox_stats.dispatched)} đã phát`"
          :context="`Đang chờ xử lý: ${formatNumber(overview.outbox_stats.pending)} sự kiện`"
          icon="layers"
        />
      </div>

      <RevenueChart :orders="orders" />

      <section class="low-stock-panel surface-panel" aria-labelledby="low-stock-title">
        <div class="panel-head">
          <div>
            <h2 id="low-stock-title">Cảnh báo biến thể SKU sắp hết hàng</h2>
            <p class="view-sub">Các mã biến thể có tồn khả dụng dưới ngưỡng an toàn.</p>
          </div>
          <RouterLink to="/admin/products" class="btn btn-secondary btn-sm">
            <span>Quản lý toàn bộ kho</span>
            <BaseIcon name="arrow-right" />
          </RouterLink>
        </div>

        <DataTable
          :columns="lowStockColumns"
          :has-rows="overview.low_stock_variants.length > 0"
          empty-message="Tất cả biến thể SKU đều đang ở ngưỡng tồn kho an toàn."
        >
          <tr v-for="item in overview.low_stock_variants" :key="item.variant_id">
            <td class="mono-code">{{ item.sku }}</td>
            <td class="strong-name">{{ item.product_name }}</td>
            <td>{{ item.color }} / {{ item.size }}</td>
            <td>
              <span
                class="badge tabular"
                :class="`badge-${getStockSummary(item.available_stock).tone}`"
              >
                {{ getStockSummary(item.available_stock).label }}
              </span>
            </td>
            <td>
              <RouterLink
                :to="{ path: '/admin/products', query: { restock: String(item.variant_id) } }"
                class="btn btn-secondary btn-sm"
              >
                <BaseIcon name="plus" />
                <span>Nhập kho</span>
              </RouterLink>
            </td>
          </tr>
        </DataTable>
      </section>
    </template>
  </div>
</template>

<style scoped>
.dashboard-view {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.view-head,
.panel-head {
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-4);
}

.low-stock-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.low-stock-panel h2 {
  font-size: var(--text-title);
  text-wrap: balance;
}

.dashboard-view :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.dashboard-view button:active:not(:disabled) {
  transform: translateY(1px);
}

.dashboard-view button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

.strong-name {
  font-weight: 600;
}

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .stats-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
