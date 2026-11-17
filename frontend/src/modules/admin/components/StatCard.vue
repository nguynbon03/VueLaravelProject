<script setup lang="ts">
import BaseIcon, { type IconName } from '../../../components/base/BaseIcon.vue'

defineProps<{
  label: string
  value: string
  context: string
  icon?: IconName
}>()
</script>

<template>
  <article class="stat-card surface-panel tabular">
    <div class="stat-top">
      <span class="stat-label">{{ label }}</span>
      <span v-if="icon" class="stat-icon">
        <BaseIcon :name="icon" />
      </span>
    </div>
    <strong class="stat-figure">{{ value }}</strong>
    <span class="stat-context">{{ context }}</span>
  </article>
</template>

<style scoped>
.stat-card {
  padding: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.stat-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

.stat-label {
  font-size: var(--text-label);
  font-weight: 500;
  color: var(--color-fg-muted);
}

.stat-icon {
  color: var(--color-primary);
  display: inline-flex;
}

.stat-figure {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-fg);
  line-height: 1.2;
  font-variant-numeric: tabular-nums;
}

.stat-context {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}
</style>
