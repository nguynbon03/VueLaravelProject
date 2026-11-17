<script setup lang="ts">
import type { AdminOverviewAnalytics } from '../../shared/api/types'
import AppIcon from '../../shared/ui/AppIcon.vue'
import { formatNumber, formatVnd, getStockSummary } from '../../shared/utils/format'

defineProps<{
  overview: AdminOverviewAnalytics | null
  loading: boolean
}>()

const emit = defineEmits<{
  refresh: []
  jumpToRestock: [variantId: number]
}>()
</script>

<template>
  <div class="overview-tab">
    <div class="tab-toolbar">
      <div>
        <h2>Chỉ số vận hành & Hàng đợi sự kiện Outbox</h2>
        <p class="tab-sub">
          Số liệu tổng hợp từ các đơn hàng đã thanh toán và sổ cái tồn kho hiện hành.
        </p>
      </div>
      <button
        type="button"
        class="btn btn-secondary btn-sm"
        :disabled="loading"
        @click="emit('refresh')"
      >
        <AppIcon name="refresh" />
        <span>Làm mới số liệu</span>
      </button>
    </div>

    <div v-if="loading && !overview" class="kpi-grid">
      <div v-for="n in 4" :key="n" class="skeleton kpi-skeleton" />
    </div>

    <template v-else-if="overview">
      <div class="kpi-grid tabular" data-ui="admin-kpi-grid">
        <div class="kpi-cell muted-block">
          <span class="kpi-label">Tổng doanh thu GMV</span>
          <strong class="kpi-value">{{ formatVnd(overview.gmv_minor) }}</strong>
          <span class="kpi-context">Tính trên {{ overview.paid_orders_count }} đơn đã thu tiền</span>
        </div>

        <div class="kpi-cell muted-block">
          <span class="kpi-label">Đơn đã thanh toán</span>
          <strong class="kpi-value">
            {{ formatNumber(overview.paid_orders_count) }} / {{ formatNumber(overview.total_orders_count) }}
          </strong>
          <span class="kpi-context">Tỷ lệ hoàn tất thu hộ & VietQR trên tổng đơn</span>
        </div>

        <div class="kpi-cell muted-block">
          <span class="kpi-label">Giá trị đơn trung bình</span>
          <strong class="kpi-value">{{ formatVnd(overview.avg_order_value_minor) }}</strong>
          <span class="kpi-context">Danh mục đang mở bán {{ overview.products_count }} sản phẩm SPU</span>
        </div>

        <div class="kpi-cell muted-block">
          <span class="kpi-label">Hàng đợi Transactional Outbox</span>
          <strong class="kpi-value">
            {{ formatNumber(overview.outbox_stats.dispatched) }} đã phát
          </strong>
          <span class="kpi-context">
            Đang chờ xử lý: {{ formatNumber(overview.outbox_stats.pending) }} sự kiện
          </span>
        </div>
      </div>

      <div class="low-stock-section">
        <div class="section-title-row">
          <h3>Cảnh báo biến thể SKU sắp hết hàng</h3>
          <span class="badge badge-warning tabular">
            {{ overview.low_stock_variants.length }} mã cần chú ý
          </span>
        </div>

        <div v-if="overview.low_stock_variants.length === 0" class="muted-block">
          <p>Tất cả biến thể SKU hiện đều có mức tồn kho khả dụng an toàn.</p>
        </div>

        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Mã SKU</th>
                <th>Tên sản phẩm</th>
                <th>Phân loại</th>
                <th>Tồn khả dụng</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
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
                  <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    @click="emit('jumpToRestock', item.variant_id)"
                  >
                    <AppIcon name="plus" />
                    <span>Nhập thêm kho</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.overview-tab {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
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

.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-4);
}

.kpi-cell {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  padding: var(--space-5);
}

.kpi-label {
  font-size: var(--text-label);
  font-weight: 500;
  color: var(--color-fg-muted);
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-fg);
  line-height: 1.2;
}

.kpi-context {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.kpi-skeleton {
  height: 120px;
}

.low-stock-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.section-title-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.strong-name {
  font-weight: 600;
}

@media (max-width: 1024px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 600px) {
  .kpi-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
