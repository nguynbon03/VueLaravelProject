<script setup lang="ts">
import BaseIcon from '../../../components/base/BaseIcon.vue'

const emit = defineEmits<{
  select: [credentials: { email: string; password: string; roleLabel: string }]
}>()

function pickCustomer(): void {
  emit('select', {
    email: 'khachhang@cleancommerce.vn',
    password: 'Customer@123456',
    roleLabel: 'Khách hàng',
  })
}

function pickAdmin(): void {
  emit('select', {
    email: 'admin@cleancommerce.vn',
    password: 'Admin@123456',
    roleLabel: 'Quản trị Admin',
  })
}
</script>

<template>
  <aside class="demo-card muted-block" aria-label="Tài khoản dùng thử">
    <h2 class="demo-title">Tài khoản kiểm thử nhanh</h2>
    <p class="demo-copy">
      Chọn vai trò dưới đây để điền sẵn thông tin xác thực cho luồng khách mua hàng hoặc quản trị vận hành.
    </p>

    <div class="demo-actions">
      <button type="button" class="demo-choice nowrap" @click="pickCustomer">
        <BaseIcon name="user" />
        <span class="choice-body">
          <strong>Khách hàng</strong>
          <span class="mono-code">khachhang@cleancommerce.vn</span>
        </span>
      </button>

      <button type="button" class="demo-choice nowrap" @click="pickAdmin">
        <BaseIcon name="shield" />
        <span class="choice-body">
          <strong>Quản trị viên</strong>
          <span class="mono-code">admin@cleancommerce.vn</span>
        </span>
      </button>
    </div>

    <p class="demo-hint">
      Sau khi điền nhanh, nhấn nút <strong>Đăng nhập</strong> để nhận khóa phiên từ máy chủ.
    </p>
  </aside>
</template>

<style scoped>
.demo-card {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.demo-title {
  font-size: var(--text-title);
}

.demo-copy {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.demo-actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.demo-choice {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-3) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border-strong);
  background-color: var(--color-surface);
  text-align: left;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) var(--ease-out),
    background-color var(--duration-fast) var(--ease-out);
}

.demo-choice:hover {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
}

.demo-choice:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.demo-choice:active:not(:disabled) {
  transform: translateY(1px);
}

.demo-choice:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

.choice-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.choice-body strong {
  font-size: var(--text-sm);
  color: var(--color-fg);
}

.choice-body span {
  color: var(--color-fg-subtle);
}

.demo-hint {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
}
</style>
