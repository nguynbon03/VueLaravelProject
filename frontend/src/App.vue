<script setup lang="ts">
import { onMounted } from 'vue'
import { useAuthStore } from './modules/auth/authStore'
import AppNavbar from './shared/ui/AppNavbar.vue'
import ToastContainer from './shared/ui/ToastContainer.vue'

const authStore = useAuthStore()

onMounted(() => {
  if (authStore.token && !authStore.initialized) {
    void authStore.fetchMe()
  }
})
</script>

<template>
  <a href="#main-content" class="skip-link">Chuyển tới nội dung chính</a>
  <AppNavbar />
  <main id="main-content" class="main-content">
    <RouterView />
  </main>
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-brand">
        <strong>CleanCommerce</strong>
        <span>Kiến trúc tách biệt Vue 3.5 TypeScript SPA & REST API v1</span>
      </div>
      <div class="footer-links">
        <RouterLink to="/">Danh mục sản phẩm</RouterLink>
        <RouterLink to="/checkout">Giỏ hàng</RouterLink>
        <RouterLink to="/orders">Tra cứu đơn hàng</RouterLink>
      </div>
    </div>
  </footer>
  <ToastContainer />
</template>

<style scoped>
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

.site-footer {
  border-top: 1px solid var(--color-border);
  background-color: var(--color-surface);
  padding-block: var(--space-6);
}

.footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.footer-brand strong {
  color: var(--color-fg);
}

.footer-links {
  display: flex;
  align-items: center;
  gap: var(--space-5);
  flex-wrap: wrap;
}

.footer-links a:hover {
  color: var(--color-primary);
}
</style>
