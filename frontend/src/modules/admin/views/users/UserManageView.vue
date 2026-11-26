<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import BaseIcon from '../../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../../components/common/LoadingSpinner.vue'
import { useAuth } from '../../../../composables/useAuth'
import { useNotification } from '../../../../composables/useNotification'
import { authService } from '../../../../services/auth.service'
import { ApiError } from '../../../../services/axiosClient'
import type { AdminUserItem, UserRole } from '../../../../types/api.types'
import { formatDate } from '../../../../utils/formatDate'
import DataTable, { type TableColumn } from '../../components/DataTable.vue'

const { user: currentAdmin } = useAuth()
const { success, error } = useNotification()

const users = ref<AdminUserItem[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const updatingId = ref<number | null>(null)

const userCount = computed(() => users.value.length)

const columns: readonly TableColumn[] = [
  { key: 'id', label: 'ID' },
  { key: 'name', label: 'Họ và tên' },
  { key: 'email', label: 'Email' },
  { key: 'phone', label: 'Số điện thoại' },
  { key: 'role', label: 'Vai trò hiện tại' },
  { key: 'created', label: 'Ngày tạo' },
  { key: 'action', label: 'Phân quyền' },
]

async function fetchUsers(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  try {
    users.value = await authService.listUsers()
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể tải danh sách người dùng từ máy chủ.'
  } finally {
    loading.value = false
  }
}

async function toggleRole(targetUser: AdminUserItem): Promise<void> {
  const nextRole: UserRole = targetUser.role === 'admin' ? 'customer' : 'admin'
  updatingId.value = targetUser.id
  try {
    const updated = await authService.updateUserRole(targetUser.id, nextRole)
    const idx = users.value.findIndex((u) => u.id === targetUser.id)
    if (idx !== -1) {
      users.value[idx] = updated
    }
    success(
      `Đã cập nhật quyền ${updated.name}`,
      `Vai trò mới: ${updated.role === 'admin' ? 'Quản trị Admin' : 'Khách hàng'}`,
    )
  } catch (err) {
    error(
      'Cập nhật vai trò không thành công',
      err instanceof ApiError ? err.message : 'Vui lòng thử lại.',
    )
  } finally {
    updatingId.value = null
  }
}

onMounted(() => {
  void fetchUsers()
})
</script>

<template>
  <div class="user-manage-view">
    <div class="view-head">
      <div>
        <h1>Quản lý người dùng ({{ userCount }})</h1>
        <p class="view-sub">
          Danh sách tài khoản thực tế trên hệ thống và phân quyền truy cập Quản trị Backoffice.
        </p>
      </div>

      <button type="button" class="btn btn-secondary btn-sm" @click="fetchUsers">
        <BaseIcon name="refresh" />
        <span>Làm mới</span>
      </button>
    </div>

    <div v-if="errorMessage" class="callout callout-warning">
      <BaseIcon name="alert" />
      <span>{{ errorMessage }}</span>
    </div>

    <LoadingSpinner v-else-if="loading" label="Đang tải danh sách tài khoản…" :rows="4" />

    <section v-else class="table-panel surface-panel">
      <DataTable
        :columns="columns"
        :has-rows="users.length > 0"
        empty-message="Chưa có tài khoản người dùng nào."
      >
        <tr v-for="u in users" :key="u.id">
          <td class="tabular">#{{ u.id }}</td>
          <td class="strong-cell">{{ u.name }}</td>
          <td class="mono-code">{{ u.email }}</td>
          <td class="tabular">{{ u.phone || 'Chưa cập nhật' }}</td>
          <td>
            <span class="badge" :class="u.role === 'admin' ? 'badge-primary' : 'badge-neutral'">
              {{ u.role === 'admin' ? 'Quản trị Admin' : 'Khách hàng' }}
            </span>
          </td>
          <td class="tabular">{{ formatDate(u.created_at) }}</td>
          <td>
            <span v-if="currentAdmin && currentAdmin.id === u.id" class="self-note">
              Phiên hiện tại
            </span>
            <button
              v-else
              type="button"
              class="btn btn-secondary btn-sm nowrap"
              :disabled="updatingId === u.id"
              @click="toggleRole(u)"
            >
              <BaseIcon name="shield" />
              <span>{{ u.role === 'admin' ? 'Hạ quyền' : 'Cấp Admin' }}</span>
            </button>
          </td>
        </tr>
      </DataTable>
    </section>
  </div>
</template>

<style scoped>
.user-manage-view {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.view-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.view-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.table-panel {
  overflow: hidden;
}

.strong-cell {
  font-weight: 600;
}

.self-note {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.user-manage-view :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.user-manage-view button:active:not(:disabled) {
  transform: translateY(1px);
}

.user-manage-view button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}
</style>
