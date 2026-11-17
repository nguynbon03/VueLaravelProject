<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AdminCreateProductTab from '../modules/admin/AdminCreateProductTab.vue'
import AdminInventoryTab from '../modules/admin/AdminInventoryTab.vue'
import AdminOrdersTab from '../modules/admin/AdminOrdersTab.vue'
import AdminOverviewTab from '../modules/admin/AdminOverviewTab.vue'
import { useAuthStore } from '../modules/auth/authStore'
import { ApiError, apiClient } from '../shared/api/client'
import type {
  AdminInventoryAdjustResult,
  AdminOverviewAnalytics,
  ApiDataEnvelope,
  CategoryItem,
  OrderDetail,
  ProductSummary,
} from '../shared/api/types'
import AppIcon from '../shared/ui/AppIcon.vue'

type AdminTabKey = 'overview' | 'orders' | 'create-product' | 'inventory'

const authStore = useAuthStore()

const activeTab = ref<AdminTabKey>('overview')
const overview = ref<AdminOverviewAnalytics | null>(null)
const adminOrders = ref<OrderDetail[]>([])
const categories = ref<CategoryItem[]>([])
const catalogProducts = ref<ProductSummary[]>([])

const loadingOverview = ref(false)
const loadingOrders = ref(false)
const bannerError = ref<string | null>(null)
const restockVariantId = ref<number | null>(null)

const tabs: ReadonlyArray<{
  key: AdminTabKey
  label: string
  icon: 'chart' | 'package' | 'plus' | 'layers'
}> = [
  { key: 'overview', label: 'Tổng quan & Outbox', icon: 'chart' },
  { key: 'orders', label: 'Quản lý đơn hàng', icon: 'package' },
  { key: 'create-product', label: 'Tạo sản phẩm & SKU', icon: 'plus' },
  { key: 'inventory', label: 'Điều chỉnh tồn kho', icon: 'layers' },
]

async function fetchOverview(): Promise<void> {
  loadingOverview.value = true
  bannerError.value = null
  try {
    const res = await apiClient.get<ApiDataEnvelope<AdminOverviewAnalytics>>(
      '/admin/analytics/overview',
    )
    overview.value = res.data
  } catch (err) {
    bannerError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể tải số liệu quản trị. Hãy đảm bảo tài khoản có quyền Admin.'
  } finally {
    loadingOverview.value = false
  }
}

async function fetchAdminOrders(): Promise<void> {
  loadingOrders.value = true
  try {
    const res = await apiClient.get<ApiDataEnvelope<OrderDetail[]>>('/admin/orders')
    adminOrders.value = res.data
  } catch (err) {
    if (!bannerError.value && err instanceof ApiError) {
      bannerError.value = err.message
    }
  } finally {
    loadingOrders.value = false
  }
}

async function fetchCatalogMetadata(): Promise<void> {
  try {
    const [catRes, prodRes] = await Promise.all([
      apiClient.get<ApiDataEnvelope<CategoryItem[]>>('/catalog/categories'),
      apiClient.get<ApiDataEnvelope<ProductSummary[]>>('/catalog/products'),
    ])
    categories.value = catRes.data
    catalogProducts.value = prodRes.data
  } catch {
    // Handled by primary admin error banner if offline
  }
}

function handleOrderUpdated(updated: OrderDetail): void {
  const idx = adminOrders.value.findIndex((o) => o.order_number === updated.order_number)
  if (idx !== -1) {
    adminOrders.value[idx] = updated
  }
  void fetchOverview()
}

function handleProductCreated(created: ProductSummary): void {
  catalogProducts.value = [created, ...catalogProducts.value]
  void fetchOverview()
  activeTab.value = 'inventory'
}

function handleInventoryAdjusted(_result: AdminInventoryAdjustResult): void {
  void fetchCatalogMetadata()
  void fetchOverview()
}

function handleJumpToRestock(variantId: number): void {
  restockVariantId.value = variantId
  activeTab.value = 'inventory'
}

onMounted(() => {
  if (authStore.isAdmin) {
    void fetchOverview()
    void fetchAdminOrders()
    void fetchCatalogMetadata()
  }
})
</script>

<template>
  <div class="admin-page container">
    <header class="admin-header">
      <div>
        <h1> Trung tâm Quản trị Vận hành</h1>
        <p class="admin-sub">
          Giám sát doanh thu thực thu, hàng đợi Transactional Outbox, điều phối đơn và sổ cái tồn kho.
        </p>
      </div>
      <div v-if="authStore.user" class=" badge badge-primary">
        <AppIcon name="shield" />
        <span>Quản trị viên: {{ authStore.user.email }}</span>
      </div>
    </header>

    <div v-if="!authStore.isAdmin" class="callout callout-danger">
      <AppIcon name="alert" />
      <div class="denied-box">
        <strong>Khu vực giới hạn quyền Quản trị Admin</strong>
        <p>Vui lòng đăng nhập bằng tài khoản quản trị để thao tác đơn hàng và tồn kho.</p>
        <RouterLink to="/login?redirect=/admin" class="btn btn-primary btn-sm">
          <span>Đi tới trang Đăng nhập</span>
        </RouterLink>
      </div>
    </div>

    <template v-else>
      <div v-if="bannerError" class="callout callout-warning">
        <AppIcon name="alert" />
        <span>{{ bannerError }}</span>
      </div>

      <div class="admin-shell surface-panel">
        <nav class="admin-tabs" role="tablist" aria-label="Phân hệ quản trị">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            type="button"
            role="tab"
            class="admin-tab-btn"
            :class="{ 'is-active': activeTab === tab.key }"
            :aria-selected="activeTab === tab.key"
            @click="activeTab = tab.key"
          >
            <AppIcon :name="tab.icon" />
            <span>{{ tab.label }}</span>
          </button>
        </nav>

        <div class="admin-tab-body">
          <AdminOverviewTab
            v-if="activeTab === 'overview'"
            :overview="overview"
            :loading="loadingOverview"
            @refresh="fetchOverview"
            @jump-to-restock="handleJumpToRestock"
          />

          <AdminOrdersTab
            v-else-if="activeTab === 'orders'"
            :orders="adminOrders"
            :loading="loadingOrders"
            @refresh="fetchAdminOrders"
            @order-updated="handleOrderUpdated"
          />

          <AdminCreateProductTab
            v-else-if="activeTab === 'create-product'"
            :categories="categories"
            @product-created="handleProductCreated"
          />

          <AdminInventoryTab
            v-else-if="activeTab === 'inventory'"
            :products="catalogProducts"
            :preselected-variant-id="restockVariantId"
            @adjusted="handleInventoryAdjusted"
          />
        </div>
      </div>
    </template>
  </div>
</template>

<style scoped>
.admin-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.admin-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.admin-sub {
  margin-top: var(--space-1);
  color: var(--color-fg-muted);
  font-size: var(--text-sm);
}

.denied-box {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-2);
}

.admin-shell {
  overflow: hidden;
}

.admin-tabs {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-5);
  background-color: var(--color-surface-muted);
  border-bottom: 1px solid var(--color-border);
  overflow-x: auto;
}

.admin-tab-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid transparent;
  background-color: transparent;
  color: var(--color-fg-muted);
  font-size: var(--text-sm);
  font-weight: 500;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.admin-tab-btn:hover {
  color: var(--color-fg);
  background-color: var(--color-surface);
}

.admin-tab-btn.is-active {
  background-color: var(--color-surface);
  border-color: var(--color-border);
  color: var(--color-primary);
  font-weight: 600;
}

.admin-tab-body {
  padding: var(--space-6);
}

@media (max-width: 640px) {
  .admin-tab-body {
    padding: var(--space-4);
  }
}
</style>
