<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import logoUrl from '../assets/images/logo.svg'
import BaseIcon, { type IconName } from '../components/base/BaseIcon.vue'
import { useAuth } from '../composables/useAuth'
import { useNotification } from '../composables/useNotification'
import { useUiStore } from '../stores/ui.store'

const route = useRoute()
const router = useRouter()
const uiStore = useUiStore()
const { user, logout } = useAuth()
const { info } = useNotification()

interface AdminNavItem {
  to: string
  label: string
  icon: IconName
  exact?: boolean
}

const navItems: readonly AdminNavItem[] = [
  { to: '/admin', label: 'Dashboard', icon: 'chart', exact: true },
  { to: '/admin/products', label: 'Quản lý sản phẩm', icon: 'layers' },
  { to: '/admin/orders', label: 'Quản lý đơn hàng', icon: 'package' },
  { to: '/admin/users', label: 'Quản lý người dùng', icon: 'users' },
]

const currentPageTitle = computed(() => {
  if (typeof route.meta.breadcrumb === 'string') return route.meta.breadcrumb
  if (typeof route.meta.title === 'string') return route.meta.title
  return 'Tổng quan vận hành'
})

function isNavActive(item: AdminNavItem): boolean {
  if (item.exact) return route.path === item.to
  return route.path === item.to || route.path.startsWith(`${item.to}/`)
}

async function handleAdminLogout(): Promise<void> {
  await logout()
  info('Đã đăng xuất phiên Quản trị')
  await router.push('/auth/login')
}
</script>

<template>
  <div class="admin-shell">
    <aside
      class="admin-sidebar"
      :class="{ 'is-open': uiStore.adminSidebarOpen }"
      aria-label="Điều hướng quản trị"
    >
      <div class="sidebar-brand">
        <RouterLink to="/admin" class="brand-row" @click="uiStore.closeAdminSidebar()">
          <img :src="logoUrl" alt="" width="32" height="32" class="brand-logo" />
          <div class="brand-copy">
            <strong>CleanCommerce</strong>
            <span>Merchant Backoffice</span>
          </div>
        </RouterLink>
      </div>

      <nav class="sidebar-nav">
        <RouterLink
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="sidebar-link"
          :class="{ 'is-active': isNavActive(item) }"
          @click="uiStore.closeAdminSidebar()"
        >
          <BaseIcon :name="item.icon" />
          <span>{{ item.label }}</span>
        </RouterLink>
      </nav>

      <div class="sidebar-footer">
        <RouterLink to="/" class="sidebar-link" @click="uiStore.closeAdminSidebar()">
          <BaseIcon name="home" />
          <span>Về cửa hàng</span>
        </RouterLink>
      </div>
    </aside>

    <div
      v-if="uiStore.adminSidebarOpen"
      class="sidebar-backdrop"
      @click="uiStore.closeAdminSidebar()"
    />

    <div class="admin-workspace">
      <header class="admin-topbar">
        <div class="topbar-left">
          <button
            type="button"
            class="mobile-menu-btn"
            aria-label="Mở thanh điều hướng"
            @click="uiStore.toggleAdminSidebar()"
          >
            <BaseIcon name="menu" />
          </button>

          <nav class="admin-breadcrumb" aria-label="Đường dẫn phân hệ">
            <RouterLink to="/admin" class="crumb-root">Quản trị</RouterLink>
            <BaseIcon name="chevron-right" />
            <span class="crumb-current">{{ currentPageTitle }}</span>
          </nav>
        </div>

        <div class="topbar-right">
          <span v-if="user" class="badge badge-primary">
            <BaseIcon name="shield" />
            <span>{{ user.name }}</span>
          </span>
          <button type="button" class="btn btn-ghost btn-sm" @click="handleAdminLogout">
            <BaseIcon name="logout" />
            <span>Đăng xuất</span>
          </button>
        </div>
      </header>

      <main class="admin-content">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  min-height: 100svh;
  display: grid;
  grid-template-columns: 256px minmax(0, 1fr);
  background-color: var(--color-canvas);
}

.admin-sidebar {
  min-height: 100svh;
  background-color: var(--color-surface);
  border-right: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  padding: var(--space-5) var(--space-4);
  z-index: 50;
}

.sidebar-brand {
  padding-bottom: var(--space-5);
  border-bottom: 1px solid var(--color-border);
}

.brand-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.brand-logo {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
}

.brand-copy {
  display: flex;
  flex-direction: column;
}

.brand-copy strong {
  font-size: var(--text-base);
  color: var(--color-fg);
}

.brand-copy span {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.sidebar-nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding-block: var(--space-5);
}

.sidebar-link {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 42px;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
  white-space: nowrap;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.sidebar-link:hover {
  background-color: var(--color-surface-muted);
  color: var(--color-fg);
}

.sidebar-link.is-active {
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.sidebar-footer {
  padding-top: var(--space-4);
  border-top: 1px solid var(--color-border);
}

.admin-workspace {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.admin-topbar {
  min-height: 64px;
  padding: var(--space-3) var(--space-6);
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.mobile-menu-btn {
  display: none;
  width: 40px;
  height: 40px;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background-color: transparent;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.admin-breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-size: var(--text-sm);
  color: var(--color-fg-subtle);
}

.crumb-root {
  color: var(--color-fg-muted);
  font-weight: 500;
}

.crumb-current {
  color: var(--color-fg);
  font-weight: 600;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.admin-content {
  flex: 1;
  padding: var(--space-6);
  max-width: 1280px;
  width: 100%;
  margin-inline: auto;
}

.sidebar-backdrop {
  display: none;
}

@media (max-width: 960px) {
  .admin-shell {
    grid-template-columns: minmax(0, 1fr);
  }

  .admin-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 268px;
    transform: translateX(-100%);
    transition: transform var(--duration-normal) var(--ease-out);
  }

  .admin-sidebar.is-open {
    transform: translateX(0);
  }

  .sidebar-backdrop {
    display: block;
    position: fixed;
    inset: 0;
    z-index: 45;
    background-color: var(--color-backdrop);
  }

  .mobile-menu-btn {
    display: inline-flex;
  }

  .admin-content {
    padding: var(--space-4);
  }
}

.admin-shell :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.admin-shell button:active:not(:disabled) {
  transform: translateY(1px);
}

.admin-shell button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

@media (prefers-reduced-motion: reduce) {
  .admin-sidebar,
  .sidebar-link {
    transition-duration: 0.01ms !important;
  }
}
</style>
