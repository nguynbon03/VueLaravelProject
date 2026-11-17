<script setup lang="ts">
import { reactive, ref } from 'vue'
import { ApiError, apiClient } from '../../shared/api/client'
import type {
  AdminCreateProductPayload,
  AdminCreateVariantInput,
  ApiDataEnvelope,
  CategoryItem,
  ProductSummary,
} from '../../shared/api/types'
import { useToastStore } from '../../shared/state/toastStore'
import AppIcon from '../../shared/ui/AppIcon.vue'

const props = defineProps<{
  categories: CategoryItem[]
}>()

const emit = defineEmits<{
  productCreated: [created: ProductSummary]
}>()

const toastStore = useToastStore()
const submitting = ref(false)
const formError = ref<string | null>(null)

const productForm = reactive({
  category_id: 0,
  name: '',
  brand: 'CleanWear',
  short_description: '',
  description: '',
  image_url: '',
})

const variants = ref<AdminCreateVariantInput[]>([
  {
    sku: 'CW-LINEN-NAVY-M',
    color: 'Xanh Hải Quân',
    size: 'M',
    price_minor: 590000,
    initial_stock: 18,
  },
])

function addVariantRow(): void {
  variants.value.push({
    sku: '',
    color: '',
    size: 'L',
    price_minor: 590000,
    initial_stock: 10,
  })
}

function removeVariantRow(index: number): void {
  if (variants.value.length <= 1) return
  variants.value.splice(index, 1)
}

function fillSampleProduct(): void {
  const firstCategory = props.categories[0]
  if (firstCategory) {
    productForm.category_id = firstCategory.id
  }
  const suffix = Math.floor(100 + Math.random() * 900)
  productForm.name = `Áo Sơ Mi Linen Thoáng Khí Phiên Bản ${suffix}`
  productForm.brand = 'CleanWear'
  productForm.short_description =
    'Chất liệu sợi lanh tự nhiên đã xử lý giảm nhăn, phù hợp khí hậu nhiệt đới.'
  productForm.description =
    'Thiết kế cổ đứng gọn gàng, đường may hai kim chắc chắn và bề mặt vải thoáng mát suốt ngày dài làm việc.'
  productForm.image_url = `${window.location.origin}/favicon.svg`
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

async function handleCreateProduct(): Promise<void> {
  formError.value = null
  const categoryId = productForm.category_id || props.categories[0]?.id || 1

  const cleanedVariants = variants.value.map((v) => ({
    sku: v.sku.trim(),
    color: v.color.trim(),
    size: v.size.trim(),
    price_minor: Math.max(1000, Math.round(Number(v.price_minor) || 0)),
    initial_stock: Math.max(0, Math.round(Number(v.initial_stock) || 0)),
  }))

  if (
    productForm.name.trim().length < 3 ||
    cleanedVariants.some((v) => !v.sku || !v.color || !v.size)
  ) {
    formError.value = 'Vui lòng nhập đầy đủ tên sản phẩm và mã SKU, màu sắc, kích cỡ cho từng dòng.'
    return
  }

  const payload: AdminCreateProductPayload = {
    category_id: categoryId,
    name: productForm.name.trim(),
    brand: productForm.brand.trim() || 'CleanWear',
    short_description: productForm.short_description.trim(),
    description: productForm.description.trim(),
    image_url: productForm.image_url.trim(),
    variants: cleanedVariants,
  }

  submitting.value = true
  try {
    const res = await apiClient.post<ApiDataEnvelope<ProductSummary>>('/admin/products', payload)
    toastStore.push({
      title: 'Đã tạo sản phẩm mới thành công',
      description: `${res.data.name} (${res.data.variants.length} mã SKU)`,
      variant: 'success',
    })
    emit('productCreated', res.data)
    productForm.name = ''
    productForm.short_description = ''
    productForm.description = ''
  } catch (err) {
    formError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể khởi tạo sản phẩm mới. Vui lòng kiểm tra mã SKU trùng lặp.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <form class="create-product-tab" @submit.prevent="handleCreateProduct">
    <div class="tab-toolbar">
      <div>
        <h2>Khởi tạo sản phẩm SPU và danh mục biến thể SKU</h2>
        <p class="tab-sub">
          Mỗi biến thể SKU được tự động ghi nhận bút toán nhập kho đầu kỳ vào sổ cái Inventory.
        </p>
      </div>
      <button type="button" class="btn btn-secondary btn-sm" @click="fillSampleProduct">
        <span>Điền dữ liệu mẫu nhanh</span>
      </button>
    </div>

    <div class="spu-grid">
      <div class="field">
        <label for="spu-name" class="field-label">Tên sản phẩm (SPU)</label>
        <input
          id="spu-name"
          v-model="productForm.name"
          type="text"
          class="input"
          placeholder="Ví dụ: Áo Khoác Nhẹ Chống Gió"
          required
        />
      </div>

      <div class="field">
        <label for="spu-cat" class="field-label">Danh mục</label>
        <select id="spu-cat" v-model.number="productForm.category_id" class="select">
          <option v-if="categories.length === 0" :value="1">Danh mục mặc định</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">
            {{ cat.name }}
          </option>
        </select>
      </div>

      <div class="field">
        <label for="spu-brand" class="field-label">Thương hiệu</label>
        <input
          id="spu-brand"
          v-model="productForm.brand"
          type="text"
          class="input"
          placeholder="CleanWear"
          required
        />
      </div>

      <div class="field">
        <label for="spu-image" class="field-label">Đường dẫn ảnh sản phẩm (URL)</label>
        <input
          id="spu-image"
          v-model="productForm.image_url"
          type="url"
          class="input"
          placeholder="Nhập đường dẫn ảnh sản phẩm (https://...)"
          required
        />
      </div>

      <div class="field span-2">
        <label for="spu-short" class="field-label">Mô tả ngắn</label>
        <input
          id="spu-short"
          v-model="productForm.short_description"
          type="text"
          class="input"
          placeholder="Tóm tắt điểm nổi bật trong 1 câu ngắn gọn"
          required
        />
      </div>

      <div class="field span-2">
        <label for="spu-desc" class="field-label">Mô tả chi tiết</label>
        <textarea
          id="spu-desc"
          v-model="productForm.description"
          class="textarea"
          placeholder="Thông số chất liệu, hướng dẫn bảo quản và phom dáng"
          required
        />
      </div>
    </div>

    <div class="variants-block muted-block">
      <div class="variants-head">
        <h3>Danh sách biến thể SKU ({{ variants.length }})</h3>
        <button type="button" class="btn btn-secondary btn-sm" @click="addVariantRow">
          <AppIcon name="plus" />
          <span>Thêm dòng SKU</span>
        </button>
      </div>

      <div class="variant-rows">
        <div
          v-for="(v, idx) in variants"
          :key="idx"
          class="variant-row"
        >
          <div class="field">
            <label :for="`var-sku-${idx}`" class="field-label">Mã SKU</label>
            <input
              :id="`var-sku-${idx}`"
              v-model="v.sku"
              type="text"
              class="input mono-code"
              placeholder="CW-ITEM-BLK-M"
              required
            />
          </div>

          <div class="field">
            <label :for="`var-color-${idx}`" class="field-label">Màu sắc</label>
            <input
              :id="`var-color-${idx}`"
              v-model="v.color"
              type="text"
              class="input"
              placeholder="Đen Than"
              required
            />
          </div>

          <div class="field">
            <label :for="`var-size-${idx}`" class="field-label">Kích cỡ</label>
            <input
              :id="`var-size-${idx}`"
              v-model="v.size"
              type="text"
              class="input"
              placeholder="M"
              required
            />
          </div>

          <div class="field">
            <label :for="`var-price-${idx}`" class="field-label">Giá bán (VNĐ)</label>
            <input
              :id="`var-price-${idx}`"
              v-model.number="v.price_minor"
              type="number"
              min="1000"
              step="1000"
              class="input tabular"
              required
            />
          </div>

          <div class="field">
            <label :for="`var-stock-${idx}`" class="field-label">Tồn đầu kỳ</label>
            <input
              :id="`var-stock-${idx}`"
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
            class="btn btn-danger btn-sm remove-row-btn"
            :disabled="variants.length <= 1"
            aria-label="Xóa dòng biến thể"
            @click="removeVariantRow(idx)"
          >
            <AppIcon name="trash" />
          </button>
        </div>
      </div>
    </div>

    <div v-if="formError" class="callout callout-danger">
      <AppIcon name="alert" />
      <span>{{ formError }}</span>
    </div>

    <div>
      <button type="submit" class="btn btn-primary btn-lg" :disabled="submitting">
        <AppIcon name="check" />
        <span>Lưu sản phẩm & Khởi tạo tồn kho</span>
      </button>
    </div>
  </form>
</template>

<style scoped>
.create-product-tab {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.tab-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.tab-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.spu-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-4);
}

.span-2 {
  grid-column: span 2;
}

.variants-block {
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

.remove-row-btn {
  height: 42px;
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
