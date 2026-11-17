<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../components/common/LoadingSpinner.vue'
import { useCart } from '../../../composables/useCart'
import { useNotification } from '../../../composables/useNotification'
import { ApiError } from '../../../services/axiosClient'
import { productService } from '../../../services/product.service'
import type { ProductDetail, ProductReview, ProductVariant } from '../../../types/api.types'
import { formatVnd } from '../../../utils/formatCurrency'
import { getStockSummary } from '../../../utils/formatDate'
import ReviewList from '../components/ReviewList.vue'

const route = useRoute()
const router = useRouter()
const { addItem } = useCart()
const { success } = useNotification()

const product = ref<ProductDetail | null>(null)
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const selectedColor = ref<string>('')
const selectedSize = ref<string>('')
const quantity = ref<number>(1)
const adding = ref(false)

const availableColors = computed<string[]>(() => {
  if (!product.value) return []
  const set = new Set<string>()
  for (const v of product.value.variants) {
    set.add(v.color)
  }
  return Array.from(set)
})

const availableSizes = computed<string[]>(() => {
  if (!product.value) return []
  const set = new Set<string>()
  for (const v of product.value.variants) {
    if (!selectedColor.value || v.color === selectedColor.value) {
      set.add(v.size)
    }
  }
  return Array.from(set)
})

const selectedVariant = computed<ProductVariant | null>(() => {
  if (!product.value || product.value.variants.length === 0) return null
  const exact = product.value.variants.find(
    (v) => v.color === selectedColor.value && v.size === selectedSize.value,
  )
  if (exact) return exact
  const byColor = product.value.variants.find((v) => v.color === selectedColor.value)
  return byColor ?? product.value.variants[0] ?? null
})

const maxQty = computed(() => Math.max(0, selectedVariant.value?.available_stock ?? 0))

function selectColor(color: string): void {
  selectedColor.value = color
  if (!product.value) return
  const matching = product.value.variants.find(
    (v) => v.color === color && v.size === selectedSize.value,
  )
  if (!matching) {
    const first =
      product.value.variants.find((v) => v.color === color && v.available_stock > 0) ??
      product.value.variants.find((v) => v.color === color)
    if (first) {
      selectedSize.value = first.size
    }
  }
  if (maxQty.value > 0 && quantity.value > maxQty.value) {
    quantity.value = maxQty.value
  }
}

function selectSize(size: string): void {
  selectedSize.value = size
  if (maxQty.value > 0 && quantity.value > maxQty.value) {
    quantity.value = maxQty.value
  }
}

function stepQty(delta: number): void {
  const next = quantity.value + delta
  if (next < 1) return
  if (maxQty.value > 0 && next > maxQty.value) return
  quantity.value = next
}

async function fetchDetail(): Promise<void> {
  const slug = String(route.params.slug ?? '')
  if (!slug) return
  loading.value = true
  errorMessage.value = null
  try {
    const detail = await productService.getProductBySlug(slug)
    product.value = detail
    const preferred =
      detail.variants.find((v) => v.available_stock > 0) ?? detail.variants[0]
    if (preferred) {
      selectedColor.value = preferred.color
      selectedSize.value = preferred.size
      quantity.value = 1
    }
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError ? err.message : 'Không thể tải thông tin sản phẩm.'
  } finally {
    loading.value = false
  }
}

async function handlePurchase(buyNow: boolean): Promise<void> {
  if (!product.value || !selectedVariant.value) return
  if (selectedVariant.value.available_stock <= 0) return

  adding.value = true
  try {
    await addItem(
      {
        variant_id: selectedVariant.value.id,
        product_name: product.value.name,
        product_slug: product.value.slug,
        image_url: product.value.image_url,
        sku: selectedVariant.value.sku,
        color: selectedVariant.value.color,
        size: selectedVariant.value.size,
        unit_price_minor: selectedVariant.value.price_minor,
        available_stock: selectedVariant.value.available_stock,
      },
      quantity.value,
    )
    success(
      'Đã thêm vào giỏ hàng',
      `${product.value.name} (${selectedVariant.value.color} / ${selectedVariant.value.size})`,
    )
    if (buyNow) {
      await router.push('/checkout')
    }
  } finally {
    adding.value = false
  }
}

function onReviewAdded(review: ProductReview): void {
  if (!product.value) return
  product.value.reviews = [review, ...product.value.reviews]
  product.value.reviews_count = product.value.reviews.length
  const total = product.value.reviews.reduce((sum, r) => sum + r.rating, 0)
  product.value.rating_avg = total / product.value.reviews.length
}

watch(
  () => route.params.slug,
  () => {
    void fetchDetail()
  },
)

onMounted(() => {
  void fetchDetail()
})
</script>

<template>
  <div class="detail-page container">
    <nav class="breadcrumb" aria-label="Đường dẫn danh mục">
      <RouterLink to="/" class="crumb-link">Trang chủ</RouterLink>
      <BaseIcon name="chevron-right" />
      <RouterLink to="/products" class="crumb-link">Sản phẩm</RouterLink>
      <template v-if="product?.category">
        <BaseIcon name="chevron-right" />
        <RouterLink
          :to="{ path: '/products', query: { category: product.category.slug } }"
          class="crumb-link"
        >
          {{ product.category.name }}
        </RouterLink>
      </template>
      <template v-if="product">
        <BaseIcon name="chevron-right" />
        <span class="crumb-current">{{ product.name }}</span>
      </template>
    </nav>

    <LoadingSpinner v-if="loading" label="Đang tải thông tin sản phẩm…" :rows="4" />

    <div v-else-if="errorMessage || !product" class="callout callout-danger">
      <BaseIcon name="alert" />
      <div class="err-stack">
        <strong>Không tìm thấy sản phẩm</strong>
        <p>{{ errorMessage }}</p>
        <RouterLink to="/products" class="btn btn-secondary btn-sm">
          <BaseIcon name="arrow-left" />
          <span>Quay lại danh mục</span>
        </RouterLink>
      </div>
    </div>

    <template v-else>
      <section class="detail-grid surface-panel">
        <div class="image-wrap">
          <img :src="product.image_url" :alt="product.name" class="main-img" />
        </div>

        <div class="info-col">
          <h1 class="detail-title">{{ product.name }}</h1>

          <div class="brand-rating-row">
            <span class="brand-name">{{ product.brand }}</span>
            <div class="rating-row tabular">
              <span class="star-tone">
                <BaseIcon name="star" :filled="true" />
              </span>
              <strong>{{ product.rating_avg.toFixed(1) }}</strong>
              <span class="muted-text">({{ product.reviews_count }} đánh giá)</span>
            </div>
          </div>

          <div v-if="selectedVariant" class="price-box muted-block">
            <div class="price-line tabular">
              <span class="active-price">{{ formatVnd(selectedVariant.price_minor) }}</span>
              <span
                v-if="
                  selectedVariant.compare_at_minor &&
                  selectedVariant.compare_at_minor > selectedVariant.price_minor
                "
                class="compare-price"
              >
                {{ formatVnd(selectedVariant.compare_at_minor) }}
              </span>
            </div>

            <div class="sku-line">
              <span>
                Mã SKU: <strong class="mono-code">{{ selectedVariant.sku }}</strong>
              </span>
              <span
                class="badge tabular"
                :class="`badge-${getStockSummary(selectedVariant.available_stock).tone}`"
              >
                {{ getStockSummary(selectedVariant.available_stock).label }}
              </span>
            </div>
          </div>

          <p class="short-copy prose-measure">{{ product.short_description }}</p>

          <div class="variant-groups">
            <div v-if="availableColors.length > 0" class="variant-group">
              <span class="field-label">Màu sắc: <strong>{{ selectedColor }}</strong></span>
              <div class="chips-row">
                <button
                  v-for="color in availableColors"
                  :key="color"
                  type="button"
                  class="variant-chip"
                  :class="{ 'is-active': selectedColor === color }"
                  @click="selectColor(color)"
                >
                  {{ color }}
                </button>
              </div>
            </div>

            <div v-if="availableSizes.length > 0" class="variant-group">
              <span class="field-label">Kích cỡ: <strong>{{ selectedSize }}</strong></span>
              <div class="chips-row">
                <button
                  v-for="size in availableSizes"
                  :key="size"
                  type="button"
                  class="variant-chip"
                  :class="{ 'is-active': selectedSize === size }"
                  @click="selectSize(size)"
                >
                  {{ size }}
                </button>
              </div>
            </div>
          </div>

          <div class="buy-bar">
            <div class="qty-stepper" role="group" aria-label="Số lượng">
              <button
                type="button"
                class="step-btn"
                aria-label="Giảm"
                :disabled="quantity <= 1 || maxQty === 0"
                @click="stepQty(-1)"
              >
                <BaseIcon name="minus" />
              </button>
              <span class="step-val tabular">{{ quantity }}</span>
              <button
                type="button"
                class="step-btn"
                aria-label="Tăng"
                :disabled="quantity >= maxQty"
                @click="stepQty(1)"
              >
                <BaseIcon name="plus" />
              </button>
            </div>

            <BaseButton
              variant="secondary"
              size="lg"
              :disabled="!selectedVariant || selectedVariant.available_stock <= 0"
              :loading="adding"
              @click="handlePurchase(false)"
            >
              <BaseIcon name="bag" />
              <span>Thêm vào giỏ</span>
            </BaseButton>

            <BaseButton
              variant="primary"
              size="lg"
              :disabled="!selectedVariant || selectedVariant.available_stock <= 0"
              :loading="adding"
              @click="handlePurchase(true)"
            >
              <span>Mua ngay</span>
              <BaseIcon name="arrow-right" />
            </BaseButton>
          </div>

          <div class="desc-section">
            <h2 class="desc-heading">Chi tiết chất liệu & thiết kế</h2>
            <p class="full-copy prose-measure">{{ product.description }}</p>
          </div>
        </div>
      </section>

      <ReviewList
        :product-slug="product.slug"
        :rating-avg="product.rating_avg"
        :reviews="product.reviews"
        @review-added="onReviewAdded"
      />
    </template>
  </div>
</template>

<style scoped>
.detail-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-8);
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  color: var(--color-fg-subtle);
}

.crumb-link {
  color: var(--color-fg-muted);
  font-weight: 500;
}

.crumb-link:hover {
  color: var(--color-primary);
}

.crumb-current {
  color: var(--color-fg);
  font-weight: 600;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
  gap: var(--space-8);
  padding: var(--space-8);
}

.image-wrap {
  aspect-ratio: 1 / 1;
  border-radius: var(--radius-md);
  overflow: hidden;
  background-color: var(--color-surface-muted);
}

.main-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info-col {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.detail-title {
  font-size: var(--text-h2);
}

.brand-rating-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.brand-name {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-primary);
}

.rating-row {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-sm);
}

.star-tone {
  color: var(--color-warning);
  display: inline-flex;
}

.muted-text {
  color: var(--color-fg-subtle);
}

.price-box {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.price-line {
  display: flex;
  align-items: baseline;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.active-price {
  font-size: 1.625rem;
  font-weight: 700;
  color: var(--color-fg);
  font-variant-numeric: tabular-nums;
}

.compare-price {
  font-size: var(--text-base);
  color: var(--color-fg-subtle);
  text-decoration: line-through;
}

.sku-line {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.short-copy {
  color: var(--color-fg-muted);
}

.variant-groups {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.variant-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.chips-row {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.variant-chip {
  min-height: 40px;
  min-width: 56px;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border-strong);
  background-color: transparent;
  color: var(--color-fg);
  font-size: var(--text-sm);
  font-weight: 500;
  white-space: nowrap;
  cursor: pointer;
  transition:
    background-color var(--duration-fast) var(--ease-out),
    border-color var(--duration-fast) var(--ease-out),
    color var(--duration-fast) var(--ease-out);
}

.variant-chip:hover {
  background-color: var(--color-surface-muted);
}

.variant-chip.is-active {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.buy-bar {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.qty-stepper {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-md);
  height: 46px;
}

.step-btn {
  width: 42px;
  height: 44px;
  border: none;
  background-color: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.step-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.step-val {
  min-width: 40px;
  text-align: center;
  font-weight: 600;
}

.desc-section {
  padding-top: var(--space-5);
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.desc-heading {
  font-size: var(--text-title);
}

.full-copy {
  color: var(--color-fg-muted);
  white-space: pre-line;
}

.err-stack {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-2);
}

@media (max-width: 960px) {
  .detail-grid {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-5);
  }
}
</style>
