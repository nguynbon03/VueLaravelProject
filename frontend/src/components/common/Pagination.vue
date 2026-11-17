<script setup lang="ts">
import { computed } from 'vue'
import BaseIcon from '../base/BaseIcon.vue'

const props = defineProps<{
  currentPage: number
  totalPages: number
  totalItems?: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const pages = computed<number[]>(() => {
  const result: number[] = []
  const total = Math.max(1, props.totalPages)
  for (let p = 1; p <= total; p += 1) {
    result.push(p)
  }
  return result
})

function goTo(page: number): void {
  if (page < 1 || page > props.totalPages || page === props.currentPage) return
  emit('change', page)
}
</script>

<template>
  <nav v-if="totalPages > 1" class="pagination-bar" aria-label="Phân trang danh sách">
    <span v-if="totalItems !== undefined" class="pagination-summary tabular">
      Trang {{ currentPage }} / {{ totalPages }} ({{ totalItems }} mục)
    </span>

    <div class="pagination-controls tabular">
      <button
        type="button"
        class="page-btn"
        :disabled="currentPage <= 1"
        aria-label="Trang trước"
        @click="goTo(currentPage - 1)"
      >
        <BaseIcon name="chevron-left" />
      </button>

      <button
        v-for="p in pages"
        :key="p"
        type="button"
        class="page-btn"
        :class="{ 'is-current': p === currentPage }"
        :aria-current="p === currentPage ? 'page' : undefined"
        @click="goTo(p)"
      >
        {{ p }}
      </button>

      <button
        type="button"
        class="page-btn"
        :disabled="currentPage >= totalPages"
        aria-label="Trang tiếp theo"
        @click="goTo(currentPage + 1)"
      >
        <BaseIcon name="chevron-right" />
      </button>
    </div>
  </nav>
</template>

<style scoped>
.pagination-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  padding-top: var(--space-4);
}

.pagination-summary {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.pagination-controls {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
}

.page-btn {
  min-width: 40px;
  height: 40px;
  padding-inline: var(--space-3);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background-color: transparent;
  color: var(--color-fg);
  font-size: var(--text-sm);
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.page-btn:hover:not(:disabled) {
  background-color: var(--color-surface-muted);
  border-color: var(--color-border-strong);
}

.page-btn.is-current {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-on-primary);
  font-weight: 600;
}

.page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.page-btn:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.page-btn:active:not(:disabled) {
  transform: translateY(1px);
}
</style>
