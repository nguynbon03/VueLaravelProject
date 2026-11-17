<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import BaseInput from '../../../components/base/BaseInput.vue'
import { useAuth } from '../../../composables/useAuth'
import { useNotification } from '../../../composables/useNotification'
import { ApiError } from '../../../services/axiosClient'
import DemoCredentialsCard from '../components/DemoCredentialsCard.vue'

const route = useRoute()
const router = useRouter()
const { loading, login } = useAuth()
const { success, info } = useNotification()

const form = reactive({
  email: '',
  password: '',
})

const errorMessage = ref<string | null>(null)

function handleSelectDemo(payload: { email: string; password: string; roleLabel: string }): void {
  form.email = payload.email
  form.password = payload.password
  errorMessage.value = null
  info(`Đã điền tài khoản ${payload.roleLabel}`)
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = null
  try {
    const loggedInUser = await login({
      email: form.email.trim(),
      password: form.password,
    })

    success(
      `Xin chào ${loggedInUser.name}`,
      loggedInUser.role === 'admin'
        ? 'Đã mở quyền truy cập Quản trị vận hành.'
        : 'Đăng nhập thành công.',
    )

    const redirectTarget =
      typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
        ? route.query.redirect
        : loggedInUser.role === 'admin'
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

onMounted(() => {
  if (typeof route.query.demo === 'string') {
    if (route.query.demo === 'admin') {
      form.email = 'admin@cleancommerce.vn'
      form.password = 'Admin@123456'
    } else if (route.query.demo === 'customer') {
      form.email = 'khachhang@cleancommerce.vn'
      form.password = 'Customer@123456'
    }
  }
})
</script>

<template>
  <div class="auth-panel surface-panel">
    <div class="auth-form-col">
      <div class="auth-head">
        <h1>Đăng nhập tài khoản</h1>
        <p>Theo dõi đơn hàng, quản lý sổ địa chỉ và truy cập phân hệ vận hành.</p>
      </div>

      <form class="form-stack" @submit.prevent="handleSubmit">
        <BaseInput
          id="login-email"
          v-model="form.email"
          type="email"
          label="Địa chỉ Email"
          placeholder="khachhang@cleancommerce.vn"
          autocomplete="email"
          required
        />

        <BaseInput
          id="login-password"
          v-model="form.password"
          type="password"
          label="Mật khẩu"
          placeholder="Nhập mật khẩu của bạn"
          autocomplete="current-password"
          required
        />

        <div v-if="errorMessage" class="callout callout-danger">
          <BaseIcon name="alert" />
          <span>{{ errorMessage }}</span>
        </div>

        <BaseButton type="submit" size="lg" :block="true" :loading="loading">
          <span>Đăng nhập</span>
          <BaseIcon name="arrow-right" />
        </BaseButton>
      </form>

      <p class="switch-line">
        Chưa có tài khoản?
        <RouterLink to="/auth/register" class="switch-link">Tạo tài khoản mới</RouterLink>
      </p>
    </div>

    <DemoCredentialsCard @select="handleSelectDemo" />
  </div>
</template>

<style scoped>
.auth-panel {
  width: 100%;
  max-width: 920px;
  padding: var(--space-8);
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.95fr);
  gap: var(--space-8);
  align-items: start;
}

.auth-form-col {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.auth-head {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.auth-head h1 {
  font-size: var(--text-h2);
  text-wrap: balance;
}

.auth-panel :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.auth-panel button:active:not(:disabled) {
  transform: translateY(1px);
}

.auth-panel button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

.auth-head p {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.form-stack {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.switch-line {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.switch-link {
  color: var(--color-primary);
  font-weight: 600;
  margin-left: var(--space-1);
}

.switch-link:hover {
  text-decoration: underline;
}

@media (max-width: 820px) {
  .auth-panel {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-5);
  }
}
</style>
