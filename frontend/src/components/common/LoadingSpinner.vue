<script setup lang="ts">
withDefaults(
  defineProps<{
    label?: string
    rows?: number
  }>(),
  {
    label: 'Đang tải dữ liệu…',
    rows: 3,
  },
)
</script>

<template>
  <div class="loading-box" role="status" aria-live="polite">
    <p class="loading-label">{{ label }}</p>
    <div class="loading-rows">
      <div v-for="n in rows" :key="n" class="skeleton loading-bar" />
    </div>
  </div>
</template>

<style scoped>
.loading-box {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-5);
  background-color: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.loading-label {
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
}

.loading-rows {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.loading-bar {
  height: 20px;
  width: 100%;
}

.loading-bar:last-child {
  width: 68%;
}
</style>
