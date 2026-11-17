<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../modules/auth/authStore'
import { ApiError } from '../shared/api/client'
import { useToastStore } from '../shared/state/toastStore'
import AppIcon from '../shared/ui/AppIcon.vue'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const toastStore = useToastStore()

const mode = ref<'login' | 'register'>('login')
const errorMessage = ref<string | null>(null)

const loginForm = reactive({
  email: '',
  password: '',
})

const registerForm = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
})

function switchMode(nextMode: 'login' | 'register'): void {
  mode.value = nextMode
  errorMessage.value = null
}

function fillDemoCustomer(): void {
  mode.value = 'login'
  errorMessage.value = null
  loginForm.email = 'khachhang@cleancommerce.vn'
  loginForm.password = 'Customer@123456'
}

function fillDemoAdmin(): void {
  mode.value = 'login'
  errorMessage.value = null
  loginForm.email = 'admin@cleancommerce.vn'
  loginForm.password = 'Admin@123456'
}

async function handleLoginSubmit(): Promise<void> {
  errorMessage.value = null
  try {
    const user = await authStore.login({
      email: loginForm.email.trim(),
      password: loginForm.password,
    })

    toastStore.push({
      title: `Xin chào ${user.name}`,
      description:
        user.role === 'admin'
          ? 'Đã mở quyền truy cập Quản trị vận hành.'
          : 'Đăng nhập thành công.',
      variant: 'success',
    })

    const redirectTarget =
      typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : user.role === 'admin'
          ? '/admin'
          : '/'

    await router.push(redirectTarget)
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Đăng nhập chưa thành công. Vui lòng kiểm tra lại thông tin.'
  }
}

async function handleRegisterSubmit(): Promise<void> {
  errorMessage.value = null
  if (registerForm.password.length < 8) {
    errorMessage.value = 'Mật khẩu cần tối thiểu 8 ký tự.'
    return
  }

  try {
    const user = await authStore.register({
      name: registerForm.name.trim(),
      email: registerForm.email.trim(),
      password: registerForm.password,
      phone: registerForm.phone.trim() || undefined,
    })

    toastStore.push({
      title: 'Tạo tài khoản thành công',
      description: `Chào mừng ${user.name} đến với CleanCommerce.`,
      variant: 'success',
    })

    const redirectTarget =
      typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : '/'

    await router.push(redirectTarget)
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Đăng ký tài khoản chưa thành công. Vui lòng thử lại.'
  }
}
</script>

<template>
  <div class="auth-page container">
    <div class="auth-layout surface-panel">
      <div class="auth-main">
        <div class="mode-tabs" role="tablist" aria-label="Chế độ tài khoản">
          <button
            type="button"
            role="tab"
            class="mode-tab"
            :class="{ 'is-active': mode === 'login' }"
            :aria-selected="mode === 'login'"
            @click="switchMode('login')"
          >
            Đăng nhập
          </button>
          <button
            type="button"
            role="tab"
            class="mode-tab"
            :class="{ 'is-active': mode === 'register' }"
            :aria-selected="mode === 'register'"
            @click="switchMode('register')"
          >
            Đăng ký tài khoản
          </button>
        </div>

        <template v-if="mode === 'login'">
          <div class="auth-heading">
            <h1>Đăng nhập CleanCommerce</h1>
            <p>Quản lý đơn hàng, lưu thông tin nhận hàng và truy cập khu vực vận hành.</p>
          </div>

          <form class="auth-form" @submit.prevent="handleLoginSubmit">
            <div class="field">
              <label for="login-email" class="field-label">Địa chỉ Email</label>
              <input
                id="login-email"
                v-model="loginForm.email"
                type="email"
                class="input"
                placeholder="khachhang@cleancommerce.vn"
                autocomplete="email"
                required
              />
            </div>

            <div class="field">
              <label for="login-password" class="field-label">Mật khẩu</label>
              <input
                id="login-password"
                v-model="loginForm.password"
                type="password"
                class="input"
                placeholder="Nhập mật khẩu của bạn"
                autocomplete="current-password"
                required
              />
            </div>

            <div v-if="errorMessage" class="callout callout-danger">
              <AppIcon name="alert" />
              <span>{{ errorMessage }}</span>
            </div>

            <button
              type="submit"
              class="btn btn-primary btn-lg btn-block"
              :disabled="authStore.loading"
            >
              <span>Đăng nhập</span>
              <AppIcon name="arrow-right" />
            </button>
          </form>
        </template>

        <template v-else>
          <div class="auth-heading">
            <h1>Tạo tài khoản khách hàng</h1>
            <p>Theo dõi tiến độ giao hàng và gửi đánh giá xác thực cho sản phẩm đã mua.</p>
          </div>

          <form class="auth-form" @submit.prevent="handleRegisterSubmit">
            <div class="field">
              <label for="reg-name" class="field-label">Họ và tên</label>
              <input
                id="reg-name"
                v-model="registerForm.name"
                type="text"
                class="input"
                placeholder="Nguyễn Minh Quân"
                autocomplete="name"
                required
              />
            </div>

            <div class="field">
              <label for="reg-email" class="field-label">Địa chỉ Email</label>
              <input
                id="reg-email"
                v-model="registerForm.email"
                type="email"
                class="input"
                placeholder="khachhang@cleancommerce.vn"
                autocomplete="email"
                required
              />
            </div>

            <div class="field">
              <label for="reg-phone" class="field-label">Số điện thoại (tùy chọn)</label>
              <input
                id="reg-phone"
                v-model="registerForm.phone"
                type="tel"
                class="input tabular"
                placeholder="0901234567"
                autocomplete="tel"
              />
            </div>

            <div class="field">
              <label for="reg-password" class="field-label">Mật khẩu (tối thiểu 8 ký tự)</label>
              <input
                id="reg-password"
                v-model="registerForm.password"
                type="password"
                class="input"
                placeholder="Tối thiểu 8 ký tự"
                autocomplete="new-password"
                required
              />
            </div>

            <div v-if="errorMessage" class="callout callout-danger">
              <AppIcon name="alert" />
              <span>{{ errorMessage }}</span>
            </div>

            <button
              type="submit"
              class="btn btn-primary btn-lg btn-block"
              :disabled="authStore.loading"
            >
              <span>Hoàn tất đăng ký</span>
              <AppIcon name="arrow-right" />
            </button>
          </form>
        </template>
      </div>

      <aside class="demo-sidebar muted-block" aria-label="Tài khoản dùng thử">
        <h2>Tài khoản trải nghiệm nhanh</h2>
        <p class="demo-desc">
          Chọn một trong hai vai trò dưới đây để tự động điền thông tin đăng nhập phục vụ kiểm thử luồng mua hàng và quản trị kho.
        </p>

        <div class="demo-buttons">
          <button type="button" class="btn btn-secondary demo-btn" @click="fillDemoCustomer">
            <AppIcon name="user" />
            <div class="demo-btn-text">
              <strong>Điền nhanh tài khoản Khách hàng</strong>
              <span class="mono-code">khachhang@cleancommerce.vn</span>
            </div>
          </button>

          <button type="button" class="btn btn-secondary demo-btn" @click="fillDemoAdmin">
            <AppIcon name="shield" />
            <div class="demo-btn-text">
              <strong>Điền nhanh tài khoản Quản trị Admin</strong>
              <span class="mono-code">admin@cleancommerce.vn</span>
            </div>
          </button>
        </div>

        <div class="demo-note">
          <p>
            Sau khi bấm điền nhanh, nhấn nút <strong>Đăng nhập</strong> để nhận mã phiên Bearer Token từ máy chủ Identity.
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.auth-page {
  display: flex;
  justify-content: center;
}

.auth-layout {
  width: 100%;
  max-width: 920px;
  padding: var(--space-8);
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.95fr);
  gap: var(--space-8);
  align-items: start;
}

.auth-main {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.mode-tabs {
  display: inline-flex;
  padding: var(--space-1);
  border-radius: var(--radius-md);
  background-color: var(--color-surface-muted);
  align-self: flex-start;
  gap: var(--space-1);
}

.mode-tab {
  min-height: 38px;
  padding: var(--space-2) var(--space-4);
  border: none;
  border-radius: var(--radius-sm);
  background-color: transparent;
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
  cursor: pointer;
}

.mode-tab.is-active {
  background-color: var(--color-surface);
  color: var(--color-fg);
  font-weight: 600;
}

.auth-heading {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.auth-heading h1 {
  font-size: var(--text-h2);
  text-wrap: balance;
}

.auth-heading p {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.demo-sidebar {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.demo-sidebar h2 {
  font-size: var(--text-title);
}

.demo-desc {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.demo-buttons {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.demo-btn {
  justify-content: flex-start;
  text-align: left;
  padding: var(--space-3) var(--space-4);
  height: auto;
  white-space: normal;
}

.demo-btn-text {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.demo-btn-text strong {
  font-size: var(--text-sm);
  color: var(--color-fg);
}

.demo-btn-text span {
  color: var(--color-fg-subtle);
}

.demo-note {
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
}

@media (max-width: 820px) {
  .auth-layout {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-5);
  }
}
</style>
