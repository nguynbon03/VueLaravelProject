<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import logoUrl from '../assets/images/logo.svg'
import BaseIcon from '../components/base/BaseIcon.vue'
import { useAuth } from '../composables/useAuth'
import { useCart } from '../composables/useCart'
import { useNotification } from '../composables/useNotification'
import { productService } from '../services/product.service'
import type { CategoryItem } from '../types/api.types'

const route = useRoute()
const router = useRouter()
const { user, isAuthenticated, isAdmin, loading, logout } = useAuth()
const { totalQuantity } = useCart()
const { info } = useNotification()

const categories = ref<CategoryItem[]>([])

async function handleLogout(): Promise<void> {
  await logout()
  info('Đã đăng xuất tài khoản')
  await router.push('/')
}

onMounted(async () => {
  try {
    categories.value = await productService.getCategories()
  } catch {
    // Categories nav degrades gracefully when API is offline
  }
})
</script>

<template>
  <div class="customer-shell">
    <a href="#customer-main" class="skip-link">Chuyển tới nội dung chính</a>

    <header class="customer-header">
      <div class="container header-top">
        <div class="header-left">
          <RouterLink to="/" class="brand-link" aria-label="Trang chủ CleanCommerce">
            <img :src="logoUrl" alt="" width="34" height="34" class="brand-logo" />
            <span class="brand-name">CleanCommerce</span>
          </RouterLink>

          <nav class="primary-nav" aria-label="Điều hướng cửa hàng">
            <RouterLink
              to="/"
              class="nav-item"
              :class="{ 'is-active': route.path === '/' }"
            >
              Trang chủ
            </RouterLink>
            <RouterLink
              to="/products"
              class="nav-item"
              :class="{
                'is-active':
                  route.path === '/products' || route.path.startsWith('/products/'),
              }"
            >
              Sản phẩm
            </RouterLink>
            <RouterLink
              to="/profile"
              class="nav-item"
              :class="{ 'is-active': route.path.startsWith('/profile') }"
            >
              Đơn hàng & Hồ sơ
            </RouterLink>
            <RouterLink
              v-if="isAdmin"
              to="/admin"
              class="nav-item"
              :class="{ 'is-active': route.path.startsWith('/admin') }"
            >
              <BaseIcon name="shield" />
              <span>Quản trị</span>
            </RouterLink>
          </nav>
        </div>

        <div class="header-actions">
          <RouterLink to="/cart" class="cart-link" aria-label="Giỏ hàng">
            <BaseIcon name="bag" />
            <span class="cart-text">Giỏ hàng</span>
            <span class="cart-badge tabular">{{ totalQuantity }}</span>
          </RouterLink>

          <template v-if="isAuthenticated && user">
            <RouterLink to="/profile" class="user-pill">
              <BaseIcon name="user" />
              <span class="user-text">{{ user.name }}</span>
            </RouterLink>
            <button
              type="button"
              class="btn btn-ghost btn-sm"
              :disabled="loading"
              @click="handleLogout"
            >
              <BaseIcon name="logout" />
              <span>Đăng xuất</span>
            </button>
          </template>

          <RouterLink v-else to="/auth/login" class="btn btn-secondary btn-sm">
            <BaseIcon name="user" />
            <span>Đăng nhập</span>
          </RouterLink>
        </div>
      </div>

      <div v-if="categories.length > 0" class="category-strip">
        <div class="container category-strip-inner">
          <RouterLink
            to="/products"
            class="cat-link"
            :class="{ 'is-active': route.path === '/products' && !route.query.category }"
          >
            Tất cả danh mục
          </RouterLink>
          <RouterLink
            v-for="cat in categories"
            :key="cat.id"
            :to="{ path: '/products', query: { category: cat.slug } }"
            class="cat-link"
            :class="{ 'is-active': route.query.category === cat.slug }"
          >
            <span>{{ cat.name }}</span>
            <span class="cat-count tabular">({{ cat.products_count }})</span>
          </RouterLink>
        </div>
      </div>
    </header>

    <main id="customer-main" class="customer-main">
      <RouterView />
    </main>

    <footer class="customer-footer">
      <div class="container footer-grid">
        <div class="footer-col">
          <strong class="footer-brand">CleanCommerce</strong>
          <p class="footer-desc prose-measure">
            Hệ thống thương mại điện tử kiến trúc tách biệt Vue 3 TypeScript SPA và Laravel Modular Monolith API v1.
          </p>
        </div>

        <div class="footer-links">
          <RouterLink to="/products">Danh mục sản phẩm</RouterLink>
          <RouterLink to="/cart">Giỏ hàng</RouterLink>
          <RouterLink to="/checkout">Thanh toán</RouterLink>
          <RouterLink to="/profile">Tra cứu đơn hàng</RouterLink>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.customer-shell {
  min-height: 100svh;
  display: flex;
  flex-direction: column;
}

.skip-link {
  position: absolute;
  left: -9999px;
  top: var(--space-2);
  z-index: 100;
  padding: var(--space-2) var(--space-4);
  background-color: var(--color-primary);
  color: var(--color-on-primary);
  border-radius: var(--radius-sm);
  font-weight: 600;
}

.skip-link:focus {
  left: var(--space-4);
}

.customer-header {
  position: sticky;
  top: 0;
  z-index: 40;
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.header-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  min-height: 68px;
  padding-block: var(--space-2);
  flex-wrap: wrap;
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

.brand-logo {
  width: 34px;
  height: 34px;
  border-radius: var(--radius-sm);
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
  white-space: nowrap;
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

.cart-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border-strong);
  background-color: transparent;
  font-size: var(--text-sm);
  font-weight: 600;
  white-space: nowrap;
  transition: background-color var(--duration-fast) var(--ease-out);
}

.cart-link:hover {
  background-color: var(--color-surface-muted);
}

.cart-badge {
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

.user-pill {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-md);
  background-color: var(--color-surface-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  white-space: nowrap;
}

.user-text {
  max-width: 140px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.category-strip {
  border-top: 1px solid var(--color-border);
  background-color: var(--color-canvas);
}

.category-strip-inner {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  min-height: 42px;
  overflow-x: auto;
  padding-block: var(--space-1);
}

.cat-link {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-label);
  font-weight: 500;
  color: var(--color-fg-muted);
  white-space: nowrap;
  padding-block: var(--space-1);
}

.cat-link:hover,
.cat-link.is-active {
  color: var(--color-primary);
  font-weight: 600;
}

.cat-count {
  color: var(--color-fg-subtle);
}

.customer-main {
  flex: 1;
  padding-block: var(--space-8) var(--space-16);
}

.customer-footer {
  border-top: 1px solid var(--color-border);
  background-color: var(--color-surface);
  padding-block: var(--space-8);
}

.footer-grid {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-6);
  flex-wrap: wrap;
}

.footer-col {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.footer-brand {
  font-size: var(--text-base);
  color: var(--color-fg);
}

.footer-desc {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.footer-links {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.footer-links a:hover {
  color: var(--color-primary);
}

@media (max-width: 640px) {
  .cart-text,
  .user-text {
    display: none;
  }

  .header-left {
    gap: var(--space-3);
  }
}
</style>
