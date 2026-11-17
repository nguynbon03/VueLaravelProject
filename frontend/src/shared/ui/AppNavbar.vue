<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../../modules/auth/authStore'
import { useCartStore } from '../../modules/cart/cartStore'
import { useToastStore } from '../state/toastStore'
import AppIcon from './AppIcon.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

async function handleLogout(): Promise<void> {
  await authStore.logout()
  toastStore.push({
    title: 'Đã đăng xuất tài khoản',
    variant: 'info',
  })
  if (route.path.startsWith('/admin')) {
    await router.push('/login')
  }
}
</script>

<template>
  <header class="site-header">
    <div class="container header-inner">
      <div class="header-left">
        <RouterLink to="/" class="brand-link" aria-label="Trang chủ CleanCommerce">
          <span class="brand-mark">
            <AppIcon name="bag" />
          </span>
          <span class="brand-name">CleanCommerce</span>
        </RouterLink>

        <nav class="primary-nav" aria-label="Điều hướng chính">
          <RouterLink
            to="/"
            class="nav-item"
            :class="{ 'is-active': route.path === '/' || route.path.startsWith('/products/') }"
          >
            Sản phẩm
          </RouterLink>
          <RouterLink
            to="/orders"
            class="nav-item"
            :class="{ 'is-active': route.path.startsWith('/orders') }"
          >
            Đơn hàng
          </RouterLink>
          <RouterLink
            v-if="authStore.isAdmin"
            to="/admin"
            class="nav-item nav-admin"
            :class="{ 'is-active': route.path.startsWith('/admin') }"
          >
            <AppIcon name="shield" />
            <span>Quản trị vận hành</span>
          </RouterLink>
        </nav>
      </div>

      <div class="header-actions">
        <RouterLink to="/checkout" class="cart-button" aria-label="Giỏ hàng và thanh toán">
          <AppIcon name="bag" />
          <span class="cart-label">Giỏ hàng</span>
          <span class="cart-count tabular">{{ cartStore.totalQuantity }}</span>
        </RouterLink>

        <template v-if="authStore.isAuthenticated && authStore.user">
          <div class="user-chip">
            <AppIcon name="user" />
            <span class="user-name">{{ authStore.user.name }}</span>
            <span v-if="authStore.isAdmin" class="badge badge-primary">Admin</span>
          </div>
          <button
            type="button"
            class="btn btn-ghost btn-sm"
            :disabled="authStore.loading"
            @click="handleLogout"
          >
            <AppIcon name="logout" />
            <span>Đăng xuất</span>
          </button>
        </template>

        <RouterLink v-else to="/login" class="btn btn-secondary btn-sm">
          <AppIcon name="user" />
          <span>Đăng nhập</span>
        </RouterLink>
      </div>
    </div>
  </header>
</template>

<style scoped>
.site-header {
  position: sticky;
  top: 0;
  z-index: 40;
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  min-height: 68px;
  flex-wrap: wrap;
  padding-block: var(--space-2);
}

.header-left {
  display: flex;
  align-items: center;
  gap: var(--space-8);
  flex-wrap: wrap;
}

.brand-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-3);
  font-weight: 700;
  font-size: 1.125rem;
  color: var(--color-fg);
}

.brand-mark {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: var(--radius-md);
  background-color: var(--color-primary);
  color: var(--color-on-primary);
}

.brand-name {
  letter-spacing: -0.02em;
}

.primary-nav {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.nav-item {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
  transition:
    background-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.nav-item:hover {
  background-color: var(--color-surface-muted);
  color: var(--color-fg);
}

.nav-item.is-active {
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.cart-button {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background-color: var(--color-surface);
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out);
}

.cart-button:hover {
  background-color: var(--color-surface-muted);
  border-color: var(--color-border-strong);
}

.cart-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 22px;
  padding-inline: var(--space-2);
  border-radius: var(--radius-pill);
  background-color: var(--color-primary);
  color: var(--color-on-primary);
  font-size: var(--text-caption);
  font-weight: 600;
}

.user-chip {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-md);
  background-color: var(--color-surface-muted);
  font-size: var(--text-sm);
  color: var(--color-fg);
}

.user-name {
  font-weight: 500;
  max-width: 150px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 640px) {
  .cart-label,
  .user-name {
    display: none;
  }

  .header-left {
    gap: var(--space-3);
  }
}
</style>
