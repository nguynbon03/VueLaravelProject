<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../modules/auth/authStore'
import { useCartStore } from '../modules/cart/cartStore'
import { ApiError, apiClient } from '../shared/api/client'
import type { ApiDataEnvelope, ProductDetail, ProductReview, ProductVariant } from '../shared/api/types'
import { useToastStore } from '../shared/state/toastStore'
import AppIcon from '../shared/ui/AppIcon.vue'
import { formatDate, formatVnd, getStockSummary } from '../shared/utils/format'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const toastStore = useToastStore()

const product = ref<ProductDetail | null>(null)
const loading = ref(true)
const errorMessage = ref<string | null>(null)

const selectedColor = ref<string>('')
const selectedSize = ref<string>('')
const quantity = ref<number>(1)
const addingToCart = ref(false)

const reviewRating = ref<number>(5)
const reviewComment = ref<string>('')
const reviewSubmitting = ref(false)
const reviewError = ref<string | null>(null)

const availableColors = computed<string[]>(() => {
  if (!product.value) return []
  const set = new Set<string>()
  for (const variant of product.value.variants) {
    set.add(variant.color)
  }
  return Array.from(set)
})

const availableSizesForColor = computed<string[]>(() => {
  if (!product.value) return []
  const set = new Set<string>()
  for (const variant of product.value.variants) {
    if (!selectedColor.value || variant.color === selectedColor.value) {
      set.add(variant.size)
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

const maxOrderableQty = computed(() => {
  const stock = selectedVariant.value?.available_stock ?? 0
  return Math.max(0, stock)
})

function selectColor(color: string): void {
  selectedColor.value = color
  if (!product.value) return
  const matching = product.value.variants.find(
    (v) => v.color === color && v.size === selectedSize.value,
  )
  if (!matching) {
    const firstWithStock =
      product.value.variants.find((v) => v.color === color && v.available_stock > 0) ??
      product.value.variants.find((v) => v.color === color)
    if (firstWithStock) {
      selectedSize.value = firstWithStock.size
    }
  }
  clampQuantity()
}

function selectSize(size: string): void {
  selectedSize.value = size
  clampQuantity()
}

function clampQuantity(): void {
  const max = maxOrderableQty.value
  if (max <= 0) {
    quantity.value = 1
  } else if (quantity.value > max) {
    quantity.value = max
  } else if (quantity.value < 1) {
    quantity.value = 1
  }
}

function stepQuantity(delta: number): void {
  const next = quantity.value + delta
  if (next < 1) return
  if (maxOrderableQty.value > 0 && next > maxOrderableQty.value) return
  quantity.value = next
}

async function loadProductDetail(): Promise<void> {
  const slug = String(route.params.slug ?? '')
  if (!slug) return

  loading.value = true
  errorMessage.value = null
  try {
    const res = await apiClient.get<ApiDataEnvelope<ProductDetail>>(`/catalog/products/${slug}`)
    product.value = res.data

    const preferredVariant =
      res.data.variants.find((v) => v.available_stock > 0) ?? res.data.variants[0]
    if (preferredVariant) {
      selectedColor.value = preferredVariant.color
      selectedSize.value = preferredVariant.size
      quantity.value = 1
    }
  } catch (err) {
    errorMessage.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể tải thông tin chi tiết sản phẩm.'
  } finally {
    loading.value = false
  }
}

async function handleAddToCart(redirectToCheckout: boolean): Promise<void> {
  if (!product.value || !selectedVariant.value) return
  if (selectedVariant.value.available_stock <= 0) return

  addingToCart.value = true
  try {
    await cartStore.addItem(
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

    toastStore.push({
      title: 'Đã thêm sản phẩm vào giỏ hàng',
      description: `${product.value.name} (${selectedVariant.value.color} / ${selectedVariant.value.size})`,
      variant: 'success',
    })

    if (redirectToCheckout) {
      await router.push('/checkout')
    }
  } finally {
    addingToCart.value = false
  }
}

async function submitReview(): Promise<void> {
  if (!product.value) return
  if (reviewComment.value.trim().length < 5) {
    reviewError.value = 'Vui lòng nhập nhận xét tối thiểu 5 ký tự.'
    return
  }

  reviewSubmitting.value = true
  reviewError.value = null
  try {
    const res = await apiClient.post<ApiDataEnvelope<ProductReview>>(
      `/catalog/products/${product.value.slug}/reviews`,
      {
        rating: reviewRating.value,
        comment: reviewComment.value.trim(),
      },
    )
    product.value.reviews = [res.data, ...product.value.reviews]
    product.value.reviews_count = product.value.reviews.length
    const totalScore = product.value.reviews.reduce((sum, r) => sum + r.rating, 0)
    product.value.rating_avg = totalScore / product.value.reviews.length

    reviewComment.value = ''
    reviewRating.value = 5
    toastStore.push({
      title: 'Cảm ơn bạn đã gửi đánh giá',
      variant: 'success',
    })
  } catch (err) {
    reviewError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể gửi đánh giá lúc này. Vui lòng thử lại.'
  } finally {
    reviewSubmitting.value = false
  }
}

watch(
  () => route.params.slug,
  () => {
    void loadProductDetail()
  },
)

onMounted(() => {
  void loadProductDetail()
})
</script>

<template>
  <div class="product-detail-page container">
    <nav class="breadcrumb" aria-label="Đường dẫn danh mục">
      <RouterLink to="/" class="breadcrumb-link">Sản phẩm</RouterLink>
      <template v-if="product?.category">
        <AppIcon name="chevron-right" />
        <RouterLink :to="`/?category=${product.category.slug}`" class="breadcrumb-link">
          {{ product.category.name }}
        </RouterLink>
      </template>
      <template v-if="product">
        <AppIcon name="chevron-right" />
        <span class="breadcrumb-current">{{ product.name }}</span>
      </template>
    </nav>

    <div v-if="loading" class="detail-grid" aria-busy="true">
      <div class="skeleton detail-skeleton-image" />
      <div class="detail-skeleton-info">
        <div class="skeleton skeleton-title" />
        <div class="skeleton skeleton-price" />
        <div class="skeleton skeleton-block" />
      </div>
    </div>

    <div v-else-if="errorMessage || !product" class="callout callout-danger">
      <AppIcon name="alert" />
      <div class="error-stack">
        <p class="error-heading">Không tìm thấy sản phẩm</p>
        <p>{{ errorMessage || 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.' }}</p>
        <div>
          <RouterLink to="/" class="btn btn-secondary btn-sm">
            <AppIcon name="arrow-left" />
            <span>Quay lại cửa hàng</span>
          </RouterLink>
        </div>
      </div>
    </div>

    <template v-else>
      <section class="detail-grid surface-panel">
        <div class="media-column">
          <div class="image-frame">
            <img :src="product.image_url" :alt="product.name" class="detail-image" />
          </div>
        </div>

        <div class="info-column">
          <div class="header-meta">
            <span class="brand-tag">{{ product.brand }}</span>
            <div class="rating-inline tabular">
              <span class="star-tone">
                <AppIcon name="star" :filled="true" />
              </span>
              <span class="rating-strong">{{ product.rating_avg.toFixed(1) }}</span>
              <span class="rating-muted">({{ product.reviews_count }} đánh giá)</span>
            </div>
          </div>

          <h1 class="detail-title">{{ product.name }}</h1>

          <div v-if="selectedVariant" class="price-banner">
            <div class="price-row tabular">
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

            <div class="sku-stock-row">
              <span class="sku-label">
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

          <p class="short-desc prose-measure">{{ product.short_description }}</p>

          <div class="variant-selectors">
            <div v-if="availableColors.length > 0" class="selector-group">
              <span class="field-label">Màu sắc: <strong>{{ selectedColor }}</strong></span>
              <div class="option-chips">
                <button
                  v-for="color in availableColors"
                  :key="color"
                  type="button"
                  class="chip-btn"
                  :class="{ 'is-active': selectedColor === color }"
                  @click="selectColor(color)"
                >
                  {{ color }}
                </button>
              </div>
            </div>

            <div v-if="availableSizesForColor.length > 0" class="selector-group">
              <span class="field-label">Kích cỡ: <strong>{{ selectedSize }}</strong></span>
              <div class="option-chips">
                <button
                  v-for="size in availableSizesForColor"
                  :key="size"
                  type="button"
                  class="chip-btn"
                  :class="{ 'is-active': selectedSize === size }"
                  @click="selectSize(size)"
                >
                  {{ size }}
                </button>
              </div>
            </div>
          </div>

          <div class="purchase-controls">
            <div class="stepper" role="group" aria-label="Số lượng đặt mua">
              <button
                type="button"
                class="stepper-btn"
                aria-label="Giảm số lượng"
                :disabled="quantity <= 1 || maxOrderableQty === 0"
                @click="stepQuantity(-1)"
              >
                <AppIcon name="minus" />
              </button>
              <span class="stepper-value tabular">{{ quantity }}</span>
              <button
                type="button"
                class="stepper-btn"
                aria-label="Tăng số lượng"
                :disabled="quantity >= maxOrderableQty"
                @click="stepQuantity(1)"
              >
                <AppIcon name="plus" />
              </button>
            </div>

            <button
              type="button"
              class="btn btn-secondary btn-lg action-flex"
              :disabled="!selectedVariant || selectedVariant.available_stock <= 0 || addingToCart"
              @click="handleAddToCart(false)"
            >
              <AppIcon name="bag" />
              <span>Thêm vào giỏ hàng</span>
            </button>

            <button
              type="button"
              class="btn btn-primary btn-lg action-flex"
              :disabled="!selectedVariant || selectedVariant.available_stock <= 0 || addingToCart"
              @click="handleAddToCart(true)"
            >
              <span>Mua ngay</span>
              <AppIcon name="arrow-right" />
            </button>
          </div>

          <div class="description-section">
            <h2 class="section-subtitle">Mô tả chi tiết</h2>
            <p class="full-desc prose-measure">{{ product.description }}</p>
          </div>
        </div>
      </section>

      <section class="reviews-section surface-panel" aria-labelledby="reviews-heading">
        <div class="reviews-header">
          <div>
            <h2 id="reviews-heading">Đánh giá từ khách hàng</h2>
            <p class="reviews-sub tabular">
              Trung bình {{ product.rating_avg.toFixed(1) }} trên 5 ({{ product.reviews.length }} nhận xét)
            </p>
          </div>
        </div>

        <div class="reviews-layout">
          <div class="reviews-list">
            <div v-if="product.reviews.length === 0" class="muted-block">
              <p>Chưa có nhận xét nào cho sản phẩm này. Hãy là người đầu tiên chia sẻ trải nghiệm thực tế.</p>
            </div>

            <article
              v-for="rev in product.reviews"
              :key="rev.id"
              class="review-item"
            >
              <div class="review-top">
                <div class="reviewer-info">
                  <strong class="reviewer-name">{{ rev.user_name }}</strong>
                  <span v-if="rev.is_verified_purchase" class="badge badge-success">
                    <AppIcon name="check" />
                    <span>Đã mua hàng</span>
                  </span>
                </div>
                <time class="review-date tabular">{{ formatDate(rev.created_at) }}</time>
              </div>

              <div class="review-stars" :aria-label="`Đánh giá ${rev.rating} trên 5 sao`">
                <span
                  v-for="s in 5"
                  :key="s"
                  class="star-item"
                  :class="{ 'is-filled': s <= rev.rating }"
                >
                  <AppIcon name="star" :filled="s <= rev.rating" />
                </span>
              </div>

              <p class="review-comment prose-measure">{{ rev.comment }}</p>
            </article>
          </div>

          <div class="review-form-box muted-block">
            <h3>Viết nhận xét của bạn</h3>

            <template v-if="authStore.isAuthenticated">
              <form class="review-form" @submit.prevent="submitReview">
                <div class="field">
                  <span class="field-label">Mức độ hài lòng</span>
                  <div class="rating-picker" role="radiogroup" aria-label="Chọn số sao">
                    <button
                      v-for="s in 5"
                      :key="s"
                      type="button"
                      class="star-pick-btn"
                      :class="{ 'is-active': s <= reviewRating }"
                      :aria-label="`${s} sao`"
                      @click="reviewRating = s"
                    >
                      <AppIcon name="star" :filled="s <= reviewRating" />
                    </button>
                    <span class="rating-pick-label tabular">{{ reviewRating }} / 5</span>
                  </div>
                </div>

                <div class="field">
                  <label for="review-comment-input" class="field-label">Nhận xét thực tế</label>
                  <textarea
                    id="review-comment-input"
                    v-model="reviewComment"
                    class="textarea"
                    placeholder="Chia sẻ cảm nhận về chất liệu, form dáng và độ hoàn thiện của sản phẩm"
                    required
                  />
                </div>

                <p v-if="reviewError" class="field-error">{{ reviewError }}</p>

                <button
                  type="submit"
                  class="btn btn-primary"
                  :disabled="reviewSubmitting"
                >
                  <span>{{ reviewSubmitting ? 'Đang gửi…' : 'Gửi đánh giá' }}</span>
                </button>
              </form>
            </template>

            <div v-else class="login-prompt">
              <p>Vui lòng đăng nhập tài khoản để gửi đánh giá và ghi nhận trạng thái mua hàng xác thực.</p>
              <RouterLink :to="`/login?redirect=${encodeURIComponent(route.fullPath)}`" class="btn btn-secondary">
                <AppIcon name="user" />
                <span>Đăng nhập để đánh giá</span>
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<style scoped>
.product-detail-page {
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

.breadcrumb-link {
  color: var(--color-fg-muted);
  font-weight: 500;
}

.breadcrumb-link:hover {
  color: var(--color-primary);
}

.breadcrumb-current {
  color: var(--color-fg);
  font-weight: 500;
}

.detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
  gap: var(--space-8);
  padding: var(--space-8);
}

.image-frame {
  aspect-ratio: 1 / 1;
  border-radius: var(--radius-md);
  overflow: hidden;
  background-color: var(--color-surface-muted);
}

.detail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.info-column {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.header-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.brand-tag {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-primary);
}

.rating-inline {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-sm);
}

.star-tone {
  color: var(--color-warning);
  display: inline-flex;
}

.rating-strong {
  font-weight: 600;
}

.rating-muted {
  color: var(--color-fg-subtle);
}

.detail-title {
  font-size: var(--text-h2);
}

.price-banner {
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-4);
  background-color: var(--color-surface-muted);
  border-radius: var(--radius-md);
}

.price-row {
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

.sku-stock-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.short-desc {
  color: var(--color-fg-muted);
}

.variant-selectors {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.selector-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.option-chips {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-2);
}

.chip-btn {
  min-height: 40px;
  min-width: 56px;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
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

.chip-btn:hover {
  border-color: var(--color-border-strong);
  background-color: var(--color-surface-muted);
}

.chip-btn.is-active {
  border-color: var(--color-primary);
  background-color: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
}

.purchase-controls {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  flex-wrap: wrap;
  padding-top: var(--space-2);
}

.stepper {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background-color: var(--color-surface);
  height: 46px;
}

.stepper-btn {
  width: 42px;
  height: 44px;
  border: none;
  background-color: transparent;
  color: var(--color-fg);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.stepper-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.stepper-value {
  min-width: 40px;
  text-align: center;
  font-weight: 600;
}

.action-flex {
  flex: 1;
  min-width: 180px;
}

.description-section {
  padding-top: var(--space-5);
  border-top: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.section-subtitle {
  font-size: var(--text-title);
}

.full-desc {
  color: var(--color-fg-muted);
  white-space: pre-line;
}

.reviews-section {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.reviews-sub {
  margin-top: var(--space-1);
  color: var(--color-fg-muted);
  font-size: var(--text-sm);
}

.reviews-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
  gap: var(--space-8);
  align-items: start;
}

.reviews-list {
  display: flex;
  flex-direction: column;
}

.review-item {
  padding-block: var(--space-4);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.review-item:first-child {
  padding-top: 0;
}

.review-item:last-child {
  border-bottom: none;
}

.review-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.reviewer-info {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.reviewer-name {
  font-size: var(--text-sm);
}

.review-date {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.review-stars {
  display: inline-flex;
  gap: 2px;
}

.star-item {
  color: var(--color-border-strong);
  display: inline-flex;
}

.star-item.is-filled {
  color: var(--color-warning);
}

.review-comment {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.review-form-box {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.review-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.rating-picker {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}

.star-pick-btn {
  width: 38px;
  height: 38px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background-color: var(--color-surface);
  color: var(--color-border-strong);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.star-pick-btn.is-active {
  color: var(--color-warning);
  border-color: var(--color-warning);
}

.rating-pick-label {
  margin-left: var(--space-2);
  font-size: var(--text-sm);
  font-weight: 600;
}

.login-prompt {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.detail-skeleton-image {
  aspect-ratio: 1 / 1;
}

.detail-skeleton-info {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.skeleton-title {
  height: 36px;
  width: 75%;
}

.skeleton-price {
  height: 64px;
  width: 100%;
}

.skeleton-block {
  height: 180px;
  width: 100%;
}

.error-stack {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.error-heading {
  font-weight: 600;
}

@media (max-width: 960px) {
  .detail-grid,
  .reviews-layout {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-5);
  }
}
</style>
