<script setup lang="ts">
import { computed } from 'vue'
import type { OrderDetail, OrderStatus } from '../../../types/api.types'
import { formatVnd } from '../../../utils/formatCurrency'
import { getOrderStatusLabel } from '../../../utils/formatDate'

const props = defineProps<{
  orders: OrderDetail[]
}>()

interface BucketRow {
  status: OrderStatus
  label: string
  count: number
  gmvMinor: number
  ratio: number
}

const TRACKED_STATUSES: readonly OrderStatus[] = [
  'pending_payment',
  'paid',
  'processing',
  'shipped',
  'completed',
  'cancelled',
]

const buckets = computed<BucketRow[]>(() => {
  const map = new Map<OrderStatus, { count: number; gmvMinor: number }>()
  for (const st of TRACKED_STATUSES) {
    map.set(st, { count: 0, gmvMinor: 0 })
  }

  for (const ord of props.orders) {
    const key: OrderStatus = map.has(ord.status) ? ord.status : 'cancelled'
    const entry = map.get(key)
    if (entry) {
      entry.count += 1
      entry.gmvMinor += ord.grand_total_minor
    }
  }

  const maxGmv = Math.max(
    1,
    ...Array.from(map.values()).map((item) => item.gmvMinor),
  )

  return TRACKED_STATUSES.map((st) => {
    const item = map.get(st) ?? { count: 0, gmvMinor: 0 }
    return {
      status: st,
      label: getOrderStatusLabel(st),
      count: item.count,
      gmvMinor: item.gmvMinor,
      ratio: item.gmvMinor / maxGmv,
    }
  })
})
</script>

<template>
  <section class="chart-panel surface-panel" aria-labelledby="revenue-chart-heading">
    <div class="chart-head">
      <div>
        <h2 id="revenue-chart-heading">Phân bổ giá trị đơn hàng theo trạng thái</h2>
        <p class="chart-sub">
          Tổng hợp trực tiếp từ danh sách đơn hàng phát sinh theo từng khâu vận hành.
        </p>
      </div>
    </div>

    <div class="bars-list tabular" data-ui="revenue-status-chart">
      <div v-for="row in buckets" :key="row.status" class="bar-item">
        <div class="bar-meta">
          <span class="bar-name">{{ row.label }}</span>
          <span class="bar-figures">
            <strong>{{ formatVnd(row.gmvMinor) }}</strong>
            <span class="bar-count">({{ row.count }} đơn)</span>
          </span>
        </div>

        <svg
          class="bar-svg"
          viewBox="0 0 400 10"
          preserveAspectRatio="none"
          aria-hidden="true"
        >
          <rect x="0" y="0" width="400" height="10" rx="3" class="bar-track-rect" />
          <rect
            x="0"
            y="0"
            :width="Math.max(row.count > 0 ? 12 : 0, Math.round(row.ratio * 400))"
            height="10"
            rx="3"
            class="bar-fill-rect"
          />
        </svg>
      </div>
    </div>
  </section>
</template>

<style scoped>
.chart-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.chart-head h2 {
  font-size: var(--text-title);
  text-wrap: balance;
}

.chart-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.bars-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.bar-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.bar-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  font-size: var(--text-sm);
}

.bar-name {
  font-weight: 500;
  color: var(--color-fg);
}

.bar-figures {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

.bar-count {
  color: var(--color-fg-subtle);
  font-size: var(--text-caption);
}

.bar-svg {
  width: 100%;
  height: 10px;
  display: block;
}

.bar-track-rect {
  fill: var(--color-surface-muted);
}

.bar-fill-rect {
  fill: var(--color-primary);
}
</style>
