<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import BaseInput from '../../../components/base/BaseInput.vue'
import { useAuth } from '../../../composables/useAuth'
import { useNotification } from '../../../composables/useNotification'
import { ApiError } from '../../../services/axiosClient'
import { isValidEmail } from '../../../utils/validators'
import DemoCredentialsCard from '../components/DemoCredentialsCard.vue'

const router = useRouter()
const { loading, register } = useAuth()
const { success } = useNotification()

const form = reactive({
  name: '',
  email: '',
  phone: '',
  password: '',
})

const errorMessage = ref<string | null>(null)

async function handleSelectDemo(payload: { email: string }): Promise<void> {
  const demoRole = payload.email.startsWith('admin') ? 'admin' : 'customer'
  await router.push({ path: '/auth/login', query: { demo: demoRole } })
}

async function handleSubmit(): Promise<void> {
  errorMessage.value = null
  if (form.name.trim().length < 2) {
    errorMessage.value = 'Vui lòng nhập họ và tên đầy đủ.'
    return
  }
  if (!isValidEmail(form.email)) {
    errorMessage.value = 'Địa chỉ email chưa hợp lệ.'
    return
  }
  if (form.password.length < 8) {
    errorMessage.value = 'Mật khẩu cần tối thiểu 8 ký tự.'
    return
  }

  try {
    const newUser = await register({
      name: form.name.trim(),
      email: form.email.trim(),
      password: form.password,
      phone: form.phone.trim() || undefined,
    })

    success('Tạo tài khoản thành công', `Chào mừng ${newUser.name} đến với CleanCommerce.`)
    await router.push('/')
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Đăng ký tài khoản chưa thành công. Vui lòng thử lại.'
  }
}
</script>

<template>
  <div class="auth-panel surface-panel">
    <div class="auth-form-col">
      <div class="auth-head">
        <h1>Đăng ký tài khoản mới</h1>
        <p>Lưu lịch sử mua hàng, tra cứu tiến độ vận chuyển và gửi đánh giá sản phẩm.</p>
      </div>

      <form class="form-stack" @submit.prevent="handleSubmit">
        <BaseInput
          id="reg-name"
          v-model="form.name"
          label="Họ và tên"
          placeholder="Nguyễn Minh Quân"
          autocomplete="name"
          required
        />

        <BaseInput
          id="reg-email"
          v-model="form.email"
          type="email"
          label="Địa chỉ Email"
          placeholder="khachhang@cleancommerce.vn"
          autocomplete="email"
          required
        />

        <BaseInput
          id="reg-phone"
          v-model="form.phone"
          type="tel"
          label="Số điện thoại (tùy chọn)"
          placeholder="0901234567"
          autocomplete="tel"
          :tabular="true"
        />

        <BaseInput
          id="reg-password"
          v-model="form.password"
          type="password"
          label="Mật khẩu (tối thiểu 8 ký tự)"
          placeholder="Tối thiểu 8 ký tự"
          autocomplete="new-password"
          required
        />

        <div v-if="errorMessage" class="callout callout-danger">
          <BaseIcon name="alert" />
          <span>{{ errorMessage }}</span>
        </div>

        <BaseButton type="submit" size="lg" :block="true" :loading="loading">
          <span>Hoàn tất đăng ký</span>
          <BaseIcon name="arrow-right" />
        </BaseButton>
      </form>

      <p class="switch-line">
        Đã có tài khoản?
        <RouterLink to="/auth/login" class="switch-link">Đăng nhập ngay</RouterLink>
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
