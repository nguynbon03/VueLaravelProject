<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../../../components/base/BaseButton.vue'
import BaseIcon from '../../../../components/base/BaseIcon.vue'
import BaseModal from '../../../../components/base/BaseModal.vue'
import LoadingSpinner from '../../../../components/common/LoadingSpinner.vue'
import { useNotification } from '../../../../composables/useNotification'
import { ApiError } from '../../../../services/axiosClient'
import { productService } from '../../../../services/product.service'
import type { ProductSummary } from '../../../../types/api.types'
import { formatVnd } from '../../../../utils/formatCurrency'
import { getStockSummary } from '../../../../utils/formatDate'
import DataTable, { type TableColumn } from '../../components/DataTable.vue'

const route = useRoute()
const router = useRouter()
const { success } = useNotification()

const products = ref<ProductSummary[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const keyword = ref<string>('')

const adjustModalOpen = ref(false)
const submittingAdjust = ref(false)
const adjustError = ref<string | null>(null)

interface VariantRow {
  variant_id: number
  sku: string
  product_name: string
  brand: string
  color: string
  size: string
  price_minor: number
  available_stock: number
}

const adjustForm = reactive({
  variant_id: 0,
  sku: '',
  product_name: '',
  current_stock: 0,
  delta: 10,
  reason: 'Nhập thêm lô mới từ xưởng sản xuất',
})

const reasonPresets = [
  'Nhập thêm lô mới từ xưởng sản xuất',
  'Kiểm kê kho định kỳ cuối tuần',
  'Trả hàng hoàn kho sau đối soát',
  'Xuất hủy hàng lỗi ngoại quan',
] as const

const columns: readonly TableColumn[] = [
  { key: 'sku', label: 'Mã SKU' },
  { key: 'product', label: 'Sản phẩm (SPU)' },
  { key: 'variant', label: 'Màu / Cỡ' },
  { key: 'price', label: 'Đơn giá' },
  { key: 'stock', label: 'Tồn kho' },
  { key: 'action', label: 'Điều chỉnh sổ cái' },
]

const flattenedRows = computed<VariantRow[]>(() => {
  const q = keyword.value.trim().toLowerCase()
  const list: VariantRow[] = []
  for (const prod of products.value) {
    for (const v of prod.variants) {
      if (
        !q ||
        v.sku.toLowerCase().includes(q) ||
        prod.name.toLowerCase().includes(q)
      ) {
        list.push({
          variant_id: v.id,
          sku: v.sku,
          product_name: prod.name,
          brand: prod.brand,
          color: v.color,
          size: v.size,
          price_minor: v.price_minor,
          available_stock: v.available_stock,
        })
      }
    }
  }
  return list
})

const skuCount = computed(() => flattenedRows.value.length)

async function loadProducts(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  try {
    products.value = await productService.getProducts()
    checkPreselectedRestock()
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError ? err.message : 'Không thể tải danh sách sản phẩm.'
  } finally {
    loading.value = false
  }
}

function openAdjustModal(row: VariantRow): void {
  adjustForm.variant_id = row.variant_id
  adjustForm.sku = row.sku
  adjustForm.product_name = `${row.product_name} (${row.color} / ${row.size})`
  adjustForm.current_stock = row.available_stock
  adjustForm.delta = 10
  adjustForm.reason = 'Nhập thêm lô mới từ xưởng sản xuất'
  adjustError.value = null
  adjustModalOpen.value = true
}

function checkPreselectedRestock(): void {
  const raw = route.query.restock
  if (typeof raw !== 'string') return
  const targetId = Number(raw)
  if (!targetId) return
  const found = flattenedRows.value.find((r) => r.variant_id === targetId)
  if (found) {
    openAdjustModal(found)
    router.replace({ query: {} }).catch(() => null)
  }
}

async function submitAdjust(): Promise<void> {
  adjustError.value = null
  if (!adjustForm.variant_id || adjustForm.delta === 0) {
    adjustError.value = 'Số lượng điều chỉnh cần khác 0.'
    return
  }
  if (adjustForm.reason.trim().length < 3) {
    adjustError.value = 'Vui lòng ghi rõ lý do kiểm toán kho.'
    return
  }

  submittingAdjust.value = true
  try {
    const res = await productService.adjustInventory({
      variant_id: adjustForm.variant_id,
      delta: Math.round(adjustForm.delta),
      reason: adjustForm.reason.trim(),
    })
    success(
      `Đã cập nhật ${res.sku}`,
      `Tồn khả dụng mới: ${res.available_stock} sản phẩm.`,
    )
    adjustModalOpen.value = false
    await loadProducts()
  } catch (err) {
    adjustError.value =
      err instanceof ApiError ? err.message : 'Điều chỉnh tồn kho không thành công.'
  } finally {
    submittingAdjust.value = false
  }
}

watch(
  () => route.query.restock,
  () => {
    checkPreselectedRestock()
  },
)

onMounted(() => {
  void loadProducts()
})
</script>

<template>
  <div class="product-manage-view">
    <div class="view-head">
      <div>
        <h1>Quản lý sản phẩm & Tồn kho ({{ skuCount }})</h1>
        <p class="view-sub">
          Tra cứu biến thể SKU và ghi nhận bút toán nhập xuất trực tiếp vào sổ cái Inventory.
        </p>
      </div>

      <div class="head-actions">
        <button type="button" class="btn btn-secondary btn-sm" @click="loadProducts">
          <BaseIcon name="refresh" />
          <span>Làm mới</span>
        </button>
        <RouterLink to="/admin/products/create" class="btn btn-primary btn-sm">
          <BaseIcon name="plus" />
          <span>Tạo sản phẩm mới</span>
        </RouterLink>
      </div>
    </div>

    <div class="toolbar surface-panel">
      <div class="search-field">
        <span class="s-icon">
          <BaseIcon name="search" />
        </span>
        <input
          v-model="keyword"
          type="search"
          class="input s-input"
          placeholder="Lọc theo tên sản phẩm hoặc mã SKU"
          aria-label="Lọc theo tên sản phẩm hoặc mã SKU"
        />
      </div>
    </div>

    <div v-if="errorMessage" class="callout callout-danger">
      <BaseIcon name="alert" />
      <span>{{ errorMessage }}</span>
    </div>

    <LoadingSpinner v-else-if="loading" label="Đang tải danh mục SKU…" :rows="4" />

    <section v-else class="table-panel surface-panel">
      <DataTable
        :columns="columns"
        :has-rows="flattenedRows.length > 0"
        empty-message="Không có biến thể SKU nào khớp với từ khóa tìm kiếm."
      >
        <tr v-for="row in flattenedRows" :key="row.variant_id">
          <td class="mono-code">{{ row.sku }}</td>
          <td>
            <div class="strong-text">{{ row.product_name }}</div>
            <div class="sub-text">{{ row.brand }}</div>
          </td>
          <td>{{ row.color }} / {{ row.size }}</td>
          <td class="tabular">{{ formatVnd(row.price_minor) }}</td>
          <td>
            <span
              class="badge tabular"
              :class="`badge-${getStockSummary(row.available_stock).tone}`"
            >
              {{ getStockSummary(row.available_stock).label }}
            </span>
          </td>
          <td>
            <button
              type="button"
              class="btn btn-secondary btn-sm"
              @click="openAdjustModal(row)"
            >
              <BaseIcon name="layers" />
              <span>Điều chỉnh kho</span>
            </button>
          </td>
        </tr>
      </DataTable>
    </section>

    <BaseModal
      :open="adjustModalOpen"
      title="Điều chỉnh sổ cái tồn kho"
      max-width="540px"
      @close="adjustModalOpen = false"
    >
      <form class="adjust-form" @submit.prevent="submitAdjust">
        <div class="muted-block target-summary">
          <div class="mono-code">{{ adjustForm.sku }}</div>
          <strong>{{ adjustForm.product_name }}</strong>
          <span class="tabular">Tồn hiện tại: {{ adjustForm.current_stock }} sản phẩm</span>
        </div>

        <div class="delta-group">
          <div class="field delta-input">
            <label for="adj-delta" class="field-label">
              Số lượng thay đổi (dương: nhập kho, âm: xuất giảm)
            </label>
            <input
              id="adj-delta"
              v-model.number="adjustForm.delta"
              type="number"
              step="1"
              class="input tabular"
              required
            />
          </div>

          <div class="delta-presets">
            <button type="button" class="btn btn-secondary btn-sm" @click="adjustForm.delta = 20">
              +20
            </button>
            <button type="button" class="btn btn-secondary btn-sm" @click="adjustForm.delta = 5">
              +5
            </button>
            <button type="button" class="btn btn-secondary btn-sm" @click="adjustForm.delta = -2">
              -2
            </button>
          </div>
        </div>

        <div class="field">
          <label for="adj-reason" class="field-label">Lý do điều chỉnh kho</label>
          <input
            id="adj-reason"
            v-model="adjustForm.reason"
            type="text"
            class="input"
            required
          />
          <div class="reason-list">
            <button
              v-for="preset in reasonPresets"
              :key="preset"
              type="button"
              class="reason-pill"
              @click="adjustForm.reason = preset"
            >
              {{ preset }}
            </button>
          </div>
        </div>

        <p v-if="adjustError" class="field-error">{{ adjustError }}</p>

        <div class="modal-actions">
          <button
            type="button"
            class="btn btn-secondary"
            @click="adjustModalOpen = false"
          >
            Hủy
          </button>
          <BaseButton type="submit" :loading="submittingAdjust">
            <span>Lưu bút toán kho</span>
          </BaseButton>
        </div>
      </form>
    </BaseModal>
  </div>
</template>

<style scoped>
.product-manage-view {
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

.head-actions {
  display: flex;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.toolbar {
  padding: var(--space-4);
}

.search-field {
  position: relative;
  max-width: 420px;
}

.s-icon {
  position: absolute;
  left: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-fg-subtle);
  display: inline-flex;
  pointer-events: none;
}

.s-input {
  padding-left: var(--space-10);
}

.table-panel {
  overflow: hidden;
}

.strong-text {
  font-weight: 600;
}

.sub-text {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.adjust-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.target-summary {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  font-size: var(--text-sm);
}

.delta-group {
  display: flex;
  align-items: flex-end;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.delta-input {
  flex: 1;
  min-width: 180px;
}

.delta-presets {
  display: flex;
  gap: var(--space-2);
  padding-bottom: var(--space-1);
}

.reason-list {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  padding-top: var(--space-1);
}

.reason-pill {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-strong);
  background-color: transparent;
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
  cursor: pointer;
}

.reason-pill:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
  padding-top: var(--space-2);
}
</style>
