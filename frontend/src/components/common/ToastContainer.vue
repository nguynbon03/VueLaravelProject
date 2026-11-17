<script setup lang="ts">
import { useNotification } from '../../composables/useNotification'
import BaseIcon from '../base/BaseIcon.vue'

const { toasts, dismiss } = useNotification()
</script>

<template>
  <div class="toast-region" aria-live="polite" aria-atomic="true">
    <div
      v-for="item in toasts"
      :key="item.id"
      class="toast-card"
      :class="`toast-${item.variant}`"
      role="status"
    >
      <div class="toast-icon">
        <BaseIcon
          :name="
            item.variant === 'success'
              ? 'check-circle'
              : item.variant === 'danger' || item.variant === 'warning'
                ? 'alert'
                : 'package'
          "
        />
      </div>
      <div class="toast-body">
        <p class="toast-title">{{ item.title }}</p>
        <p v-if="item.description" class="toast-desc">{{ item.description }}</p>
      </div>
      <button
        type="button"
        class="toast-close"
        aria-label="Đóng thông báo"
        @click="dismiss(item.id)"
      >
        <BaseIcon name="x" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.toast-region {
  position: fixed;
  bottom: var(--space-6);
  right: var(--space-6);
  z-index: 90;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  max-width: 380px;
  width: calc(100% - var(--space-8));
  pointer-events: none;
}

.toast-card {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  background-color: var(--color-surface);
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lift); /* taste-skill-ignore: intentional subtle elevation from DESIGN.md */
}

.toast-success .toast-icon {
  color: var(--color-success);
}

.toast-warning .toast-icon {
  color: var(--color-warning);
}

.toast-danger .toast-icon {
  color: var(--color-danger);
}

.toast-info .toast-icon {
  color: var(--color-primary);
}

.toast-icon {
  padding-top: 2px;
}

.toast-body {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
}

.toast-desc {
  margin-top: 2px;
  font-size: var(--text-label);
  color: var(--color-fg-muted);
}

.toast-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: none;
  border-radius: var(--radius-sm);
  background-color: transparent;
  color: var(--color-fg-subtle);
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.toast-close:hover {
  background-color: var(--color-surface-muted);
  color: var(--color-fg);
}
</style>
