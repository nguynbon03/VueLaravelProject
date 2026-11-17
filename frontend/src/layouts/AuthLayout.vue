<script setup lang="ts">
import logoUrl from '../assets/images/logo.svg'
import BaseIcon from '../components/base/BaseIcon.vue'
</script>

<template>
  <div class="auth-shell">
    <header class="auth-topbar">
      <div class="container auth-topbar-inner">
        <RouterLink to="/" class="auth-brand">
          <img :src="logoUrl" alt="" width="32" height="32" class="auth-logo" />
          <span>CleanCommerce</span>
        </RouterLink>

        <RouterLink to="/products" class="btn btn-ghost btn-sm">
          <BaseIcon name="arrow-left" />
          <span>Về cửa hàng</span>
        </RouterLink>
      </div>
    </header>

    <main class="auth-main container">
      <RouterView />
    </main>

    <footer class="auth-footer">
      <div class="container auth-footer-inner">
        <span>CleanCommerce Identity & Access</span>
        <span>Bảo mật phiên đăng nhập bằng Bearer Token</span>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.auth-shell {
  min-height: 100svh;
  display: flex;
  flex-direction: column;
  background-color: var(--color-canvas);
}

.auth-topbar {
  background-color: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.auth-topbar-inner {
  min-height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
}

.auth-brand {
  display: inline-flex;
  align-items: center;
  gap: var(--space-3);
  font-weight: 700;
  font-size: 1.125rem;
}

.auth-logo {
  width: 32px;
  height: 32px;
  border-radius: var(--radius-sm);
}

.auth-main {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding-block: var(--space-10);
}

.auth-footer {
  border-top: 1px solid var(--color-border);
  background-color: var(--color-surface);
  padding-block: var(--space-4);
}

.auth-footer-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}
</style>
