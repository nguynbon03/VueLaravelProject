<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { ApiError, apiClient } from '../../shared/api/client'
import type {
  AdminInventoryAdjustResult,
  ApiDataEnvelope,
  ProductSummary,
} from '../../shared/api/types'
import { useToastStore } from '../../shared/state/toastStore'
import AppIcon from '../../shared/ui/AppIcon.vue'
import { getStockSummary } from '../../shared/utils/format'

const props = defineProps<{
  products: ProductSummary[]
  preselectedVariantId: number | null
}>()

const emit = defineEmits<{
  adjusted: [result: AdminInventoryAdjustResult]
}>()

const toastStore = useToastStore()
const submitting = ref(false)
const adjustError = ref<string | null>(null)
const lastResult = ref<AdminInventoryAdjustResult | null>(null)

interface FlattenedVariantOption {
  variant_id: number
  sku: string
  product_name: string
  color: string
  size: string
  available_stock: number
}

const flattenedVariants = computed<FlattenedVariantOption[]>(() => {
  const list: FlattenedVariantOption[] = []
  for (const prod of props.products) {
    for (const v of prod.variants) {
      list.push({
        variant_id: v.id,
        sku: v.sku,
        product_name: prod.name,
        color: v.color,
        size: v.size,
        available_stock: v.available_stock,
      })
    }
  }
  return list
})

const form = reactive({
  variant_id: 0,
  delta: 10,
  reason: 'Nhập thêm lô mới từ xưởng sản xuất',
})

const reasonPresets = [
  'Nhập thêm lô mới từ xưởng sản xuất',
  'Kiểm kê kho định kỳ cuối tuần',
  'Trả hàng hoàn kho sau đối soát',
  'Xuất hủy hàng lỗi ngoại quan',
] as const

const selectedVariantInfo = computed(() =>
  flattenedVariants.value.find((item) => item.variant_id === form.variant_id) ?? null,
)

watch(
  () => [props.preselectedVariantId, flattenedVariants.value.length] as const,
  ([preselected]) => {
    if (preselected && flattenedVariants.value.some((v) => v.variant_id === preselected)) {
      form.variant_id = preselected
    } else if (!form.variant_id && flattenedVariants.value[0]) {
      form.variant_id = flattenedVariants.value[0].variant_id
    }
  },
  { immediate: true },
)

async function handleAdjustSubmit(): Promise<void> {
  adjustError.value = null
  if (!form.variant_id) {
    adjustError.value = 'Vui lòng chọn một biến thể SKU cần điều chỉnh.'
    return
  }
  if (!Number.isFinite(form.delta) || form.delta === 0) {
    adjustError.value = 'Số lượng thay đổi cần khác 0 (số dương để nhập thêm, số âm để xuất giảm).'
    return
  }
  if (form.reason.trim().length < 3) {
    adjustError.value = 'Vui lòng ghi rõ lý do điều chỉnh sổ cái tồn kho.'
    return
  }

  submitting.value = true
  try {
    const res = await apiClient.post<ApiDataEnvelope<AdminInventoryAdjustResult>>(
      '/admin/inventory/adjust',
      {
        variant_id: form.variant_id,
        delta: Math.round(form.delta),
        reason: form.reason.trim(),
      },
    )
    lastResult.value = res.data
    toastStore.push({
      title: `Đã cập nhật tồn kho ${res.data.sku}`,
      description: `Tồn khả dụng mới: ${res.data.available_stock} sản phẩm`,
      variant: 'success',
    })
    emit('adjusted', res.data)
  } catch (err) {
    adjustError.value =
      err instanceof ApiError ? err.message : 'Điều chỉnh tồn kho không thành công.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="inventory-tab">
    <div class="tab-head">
      <h2>Điều chỉnh sổ cái tồn kho (Inventory Ledger)</h2>
      <p class="tab-sub">
        Ghi nhận bút toán cộng hoặc trừ tồn kho kèm lý do kiểm toán rõ ràng cho từng mã SKU.
      </p>
    </div>

    <div class="inventory-layout">
      <form class="adjust-form muted-block" @submit.prevent="handleAdjustSubmit">
        <div class="field">
          <label for="inv-variant" class="field-label">Chọn mã biến thể SKU</label>
          <select id="inv-variant" v-model.number="form.variant_id" class="select">
            <option
              v-for="item in flattenedVariants"
              :key="item.variant_id"
              :value="item.variant_id"
            >
              {{ item.sku }} - {{ item.product_name }} ({{ item.color }} / {{ item.size }}) - Tồn:
              {{ item.available_stock }}
            </option>
          </select>
        </div>

        <div class="delta-row">
          <div class="field">
            <label for="inv-delta" class="field-label">
              Số lượng điều chỉnh (dương: nhập thêm, âm: giảm trừ)
            </label>
            <input
              id="inv-delta"
              v-model.number="form.delta"
              type="number"
              step="1"
              class="input tabular"
              required
            />
          </div>

          <div class="quick-deltas">
            <button type="button" class="btn btn-secondary btn-sm" @click="form.delta = 20">
              +20
            </button>
            <button type="button" class="btn btn-secondary btn-sm" @click="form.delta = 5">
              +5
            </button>
            <button type="button" class="btn btn-secondary btn-sm" @click="form.delta = -2">
              -2
            </button>
          </div>
        </div>

        <div class="field">
          <label for="inv-reason" class="field-label">Lý do ghi nhận bút toán kho</label>
          <input
            id="inv-reason"
            v-model="form.reason"
            type="text"
            class="input"
            placeholder="Nhập thêm lô mới hoặc kiểm kê kho"
            required
          />
          <div class="reason-chips">
            <button
              v-for="preset in reasonPresets"
              :key="preset"
              type="button"
              class="reason-chip"
              @click="form.reason = preset"
            >
              {{ preset }}
            </button>
          </div>
        </div>

        <div v-if="adjustError" class="callout callout-danger">
          <AppIcon name="alert" />
          <span>{{ adjustError }}</span>
        </div>

        <button type="submit" class="btn btn-primary btn-lg" :disabled="submitting">
          <AppIcon name="layers" />
          <span>Xác nhận điều chỉnh tồn kho</span>
        </button>
      </form>

      <div class="preview-column">
        <div v-if="selectedVariantInfo" class="variant-snapshot muted-block">
          <h3>Thông tin mã SKU đang chọn</h3>
          <dl class="snapshot-list">
            <div class="snap-row">
              <dt>Mã định danh SKU</dt>
              <dd class="mono-code">{{ selectedVariantInfo.sku }}</dd>
            </div>
            <div class="snap-row">
              <dt>Sản phẩm</dt>
              <dd>{{ selectedVariantInfo.product_name }}</dd>
            </div>
            <div class="snap-row">
              <dt>Phân loại</dt>
              <dd>{{ selectedVariantInfo.color }} / {{ selectedVariantInfo.size }}</dd>
            </div>
            <div class="snap-row">
              <dt>Tồn khả dụng hiện tại</dt>
              <dd>
                <span
                  class="badge tabular"
                  :class="`badge-${getStockSummary(selectedVariantInfo.available_stock).tone}`"
                >
                  {{ getStockSummary(selectedVariantInfo.available_stock).label }}
                </span>
              </dd>
            </div>
            <div class="snap-row">
              <dt>Dự kiến sau điều chỉnh</dt>
              <dd class="tabular projected-figure">
                {{ Math.max(0, selectedVariantInfo.available_stock + (Number(form.delta) || 0)) }} sản phẩm
              </dd>
            </div>
          </dl>
        </div>

        <div v-if="lastResult" class="callout callout-success">
          <AppIcon name="check-circle" />
          <div>
            <strong>Bút toán gần nhất đã lưu thành công</strong>
            <p class="tabular">
              Mã <span class="mono-code">{{ lastResult.sku }}</span> hiện có
              <strong>{{ lastResult.available_stock }}</strong> sản phẩm khả dụng.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.inventory-tab {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.tab-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.inventory-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(0, 0.9fr);
  gap: var(--space-6);
  align-items: start;
}

.adjust-form {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.delta-row {
  display: flex;
  align-items: flex-end;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.delta-row .field {
  flex: 1;
  min-width: 200px;
}

.quick-deltas {
  display: flex;
  gap: var(--space-2);
  padding-bottom: var(--space-1);
}

.reason-chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
  padding-top: var(--space-1);
}

.reason-chip {
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background-color: var(--color-surface);
  font-size: var(--text-caption);
  color: var(--color-fg-muted);
  cursor: pointer;
}

.reason-chip:hover {
  border-color: var(--color-primary);
  color: var(--color-primary);
}

.preview-column {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.variant-snapshot {
  padding: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.snapshot-list {
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  font-size: var(--text-sm);
}

.snap-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding-bottom: var(--space-2);
  border-bottom: 1px solid var(--color-border);
}

.snap-row:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.snap-row dt {
  color: var(--color-fg-muted);
}

.snap-row dd {
  margin: 0;
  font-weight: 600;
  text-align: right;
}

.projected-figure {
  color: var(--color-primary);
}

@media (max-width: 900px) {
  .inventory-layout {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
