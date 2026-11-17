<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '../../../../components/base/BaseButton.vue'
import BaseIcon from '../../../../components/base/BaseIcon.vue'
import BaseInput from '../../../../components/base/BaseInput.vue'
import { useNotification } from '../../../../composables/useNotification'
import { ApiError } from '../../../../services/axiosClient'
import { productService } from '../../../../services/product.service'
import type { AdminCreateVariantInput, CategoryItem } from '../../../../types/api.types'

const router = useRouter()
const { success } = useNotification()

const categories = ref<CategoryItem[]>([])
const submitting = ref(false)
const formError = ref<string | null>(null)

const categoryId = ref<number>(1)
const spuName = ref<string>('')
const spuBrand = ref<string>('CleanWear')
const spuShortDesc = ref<string>('')
const spuDesc = ref<string>('')
const spuImageUrl = ref<string>('')

const variants = ref<AdminCreateVariantInput[]>([
  {
    sku: 'CW-NEW-NAVY-M',
    color: 'Xanh Hải Quân',
    size: 'M',
    price_minor: 590000,
    initial_stock: 16,
  },
])

const variantCount = computed(() => variants.value.length)

function addVariantRow(): void {
  variants.value.push({
    sku: '',
    color: '',
    size: 'L',
    price_minor: 590000,
    initial_stock: 10,
  })
}

function removeVariantRow(idx: number): void {
  if (variants.value.length <= 1) return
  variants.value.splice(idx, 1)
}

function fillSampleProduct(): void {
  const firstCat = categories.value[0]
  if (firstCat) categoryId.value = firstCat.id
  const suffix = Math.floor(100 + Math.random() * 900)
  spuName.value = `Áo Sơ Mi Linen Thoáng Khí Mã ${suffix}`
  spuBrand.value = 'CleanWear'
  spuShortDesc.value =
    'Chất liệu sợi lanh tự nhiên xử lý giảm nhăn, phù hợp đi làm hàng ngày.'
  spuDesc.value =
    'Thiết kế cổ đứng gọn gàng, đường may hai kim chắc chắn và bề mặt vải thoáng mát.'
  spuImageUrl.value = `${window.location.origin}/favicon.svg`
  variants.value = [
    {
      sku: `CW-LINEN-${suffix}-M`,
      color: 'Trắng Ngà',
      size: 'M',
      price_minor: 620000,
      initial_stock: 15,
    },
    {
      sku: `CW-LINEN-${suffix}-L`,
      color: 'Xanh Đá',
      size: 'L',
      price_minor: 620000,
      initial_stock: 12,
    },
  ]
}

async function handleSubmit(): Promise<void> {
  formError.value = null
  const cleanedVariants = variants.value.map((v) => ({
    sku: v.sku.trim(),
    color: v.color.trim(),
    size: v.size.trim(),
    price_minor: Math.max(1000, Math.round(Number(v.price_minor) || 0)),
    initial_stock: Math.max(0, Math.round(Number(v.initial_stock) || 0)),
  }))

  if (
    spuName.value.trim().length < 3 ||
    cleanedVariants.some((v) => !v.sku || !v.color || !v.size)
  ) {
    formError.value = 'Vui lòng điền đầy đủ tên sản phẩm và thông tin từng dòng biến thể SKU.'
    return
  }

  submitting.value = true
  try {
    const created = await productService.createProduct({
      category_id: categoryId.value || categories.value[0]?.id || 1,
      name: spuName.value.trim(),
      brand: spuBrand.value.trim() || 'CleanWear',
      short_description: spuShortDesc.value.trim(),
      description: spuDesc.value.trim(),
      image_url: spuImageUrl.value.trim(),
      variants: cleanedVariants,
    })

    success('Tạo sản phẩm mới thành công', `${created.name} (${created.variants.length} SKU)`)
    await router.push('/admin/products')
  } catch (err) {
    formError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể lưu sản phẩm mới. Hãy kiểm tra mã SKU không bị trùng.'
  } finally {
    submitting.value = false
  }
}

onMounted(async () => {
  try {
    const cats = await productService.getCategories()
    categories.value = cats
    if (cats[0]) categoryId.value = cats[0].id
  } catch {
    // Fallback category id 1 remains active
  }
})
</script>

<template>
  <div class="product-form-view">
    <div class="view-head">
      <div>
        <h1>Khởi tạo sản phẩm SPU & Biến thể SKU</h1>
        <p class="view-sub">
          Tự động khởi tạo mã SKU và bút toán nhập kho đầu kỳ trên hệ thống.
        </p>
      </div>

      <div class="head-actions">
        <button type="button" class="btn btn-secondary btn-sm" @click="fillSampleProduct">
          <span>Điền dữ liệu mẫu</span>
        </button>
        <RouterLink to="/admin/products" class="btn btn-ghost btn-sm">
          <BaseIcon name="arrow-left" />
          <span>Danh sách sản phẩm</span>
        </RouterLink>
      </div>
    </div>

    <form class="form-panel surface-panel" @submit.prevent="handleSubmit">
      <div class="spu-grid">
        <BaseInput
          id="pf-name"
          v-model="spuName"
          label="Tên sản phẩm (SPU)"
          placeholder="Ví dụ: Áo Khoác Nhẹ Chống Gió"
          required
        />

        <div class="field">
          <label for="pf-cat" class="field-label">Danh mục</label>
          <select id="pf-cat" v-model.number="categoryId" class="select">
            <option v-if="categories.length === 0" :value="1">Danh mục mặc định</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
        </div>

        <BaseInput
          id="pf-brand"
          v-model="spuBrand"
          label="Thương hiệu"
          placeholder="CleanWear"
          required
        />

        <BaseInput
          id="pf-image"
          v-model="spuImageUrl"
          type="url"
          label="Đường dẫn ảnh sản phẩm"
          placeholder="Nhập URL ảnh sản phẩm (https://...)"
          required
        />

        <div class="span-2">
          <BaseInput
            id="pf-short"
            v-model="spuShortDesc"
            label="Mô tả ngắn"
            placeholder="Tóm tắt đặc điểm nổi bật trong 1 câu"
            required
          />
        </div>

        <div class="field span-2">
          <label for="pf-desc" class="field-label">Mô tả chi tiết</label>
          <textarea
            id="pf-desc"
            v-model="spuDesc"
            class="textarea"
            placeholder="Chất liệu, phom dáng và hướng dẫn bảo quản"
            required
          />
        </div>
      </div>

      <div class="variants-section muted-block">
        <div class="variants-head">
          <h2>Biến thể SKU ({{ variantCount }})</h2>
          <button type="button" class="btn btn-secondary btn-sm" @click="addVariantRow">
            <BaseIcon name="plus" />
            <span>Thêm dòng SKU</span>
          </button>
        </div>

        <div class="variant-rows">
          <div v-for="(v, idx) in variants" :key="idx" class="variant-row">
            <div class="field">
              <label :for="`v-sku-${idx}`" class="field-label">Mã SKU</label>
              <input
                :id="`v-sku-${idx}`"
                v-model="v.sku"
                type="text"
                class="input mono-code"
                placeholder="CW-SKU-01"
                required
              />
            </div>

            <div class="field">
              <label :for="`v-color-${idx}`" class="field-label">Màu sắc</label>
              <input
                :id="`v-color-${idx}`"
                v-model="v.color"
                type="text"
                class="input"
                placeholder="Đen Than"
                required
              />
            </div>

            <div class="field">
              <label :for="`v-size-${idx}`" class="field-label">Kích cỡ</label>
              <input
                :id="`v-size-${idx}`"
                v-model="v.size"
                type="text"
                class="input"
                placeholder="M"
                required
              />
            </div>

            <div class="field">
              <label :for="`v-price-${idx}`" class="field-label">Giá bán (VNĐ)</label>
              <input
                :id="`v-price-${idx}`"
                v-model.number="v.price_minor"
                type="number"
                min="1000"
                step="1000"
                class="input tabular"
                required
              />
            </div>

            <div class="field">
              <label :for="`v-stock-${idx}`" class="field-label">Tồn đầu kỳ</label>
              <input
                :id="`v-stock-${idx}`"
                v-model.number="v.initial_stock"
                type="number"
                min="0"
                step="1"
                class="input tabular"
                required
              />
            </div>

            <button
              type="button"
              class="btn btn-danger btn-sm row-del"
              :disabled="variants.length <= 1"
              aria-label="Xóa dòng"
              @click="removeVariantRow(idx)"
            >
              <BaseIcon name="trash" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="formError" class="callout callout-danger">
        <BaseIcon name="alert" />
        <span>{{ formError }}</span>
      </div>

      <div class="submit-bar">
        <RouterLink to="/admin/products" class="btn btn-secondary">Hủy bỏ</RouterLink>
        <BaseButton type="submit" size="lg" :loading="submitting">
          <BaseIcon name="check" />
          <span>Lưu sản phẩm</span>
        </BaseButton>
      </div>
    </form>
  </div>
</template>

<style scoped>
.product-form-view {
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
}

.form-panel {
  padding: var(--space-6);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.spu-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-4);
}

.span-2 {
  grid-column: span 2;
}

.variants-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.variants-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.variants-head h2 {
  font-size: var(--text-title);
  text-wrap: balance;
}

.product-form-view :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.product-form-view button:active:not(:disabled) {
  transform: translateY(1px);
}

.product-form-view button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

.variant-rows {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.variant-row {
  display: grid;
  grid-template-columns: 1.3fr 1fr 0.7fr 1fr 0.8fr auto;
  gap: var(--space-3);
  align-items: end;
  padding-bottom: var(--space-3);
  border-bottom: 1px solid var(--color-border);
}

.variant-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.row-del {
  height: 42px;
}

.submit-bar {
  display: flex;
  justify-content: flex-end;
  gap: var(--space-3);
}

@media (max-width: 960px) {
  .variant-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .spu-grid,
  .variant-row {
    grid-template-columns: minmax(0, 1fr);
  }

  .span-2 {
    grid-column: span 1;
  }
}
</style>
