<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCartStore } from '../modules/cart/cartStore'
import { ApiError, apiClient } from '../shared/api/client'
import type {
  ApiDataEnvelope,
  CatalogSortOption,
  CategoryItem,
  ProductSummary,
} from '../shared/api/types'
import { useToastStore } from '../shared/state/toastStore'
import AppIcon from '../shared/ui/AppIcon.vue'
import { formatVnd, getStockSummary } from '../shared/utils/format'

const route = useRoute()
const router = useRouter()
const cartStore = useCartStore()
const toastStore = useToastStore()

const categories = ref<CategoryItem[]>([])
const products = ref<ProductSummary[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const isOffline = ref(false)

const searchQuery = ref<string>(typeof route.query.q === 'string' ? route.query.q : '')
const selectedCategory = ref<string>(
  typeof route.query.category === 'string' ? route.query.category : '',
)
const selectedSort = ref<CatalogSortOption>(
  isValidSort(route.query.sort) ? route.query.sort : 'featured',
)

let searchDebounceTimer: number | undefined

function isValidSort(val: unknown): val is CatalogSortOption {
  return val === 'featured' || val === 'price_asc' || val === 'price_desc' || val === 'newest'
}

const hasActiveFilters = computed(
  () =>
    searchQuery.value.trim() !== '' ||
    selectedCategory.value !== '' ||
    selectedSort.value !== 'featured',
)

async function loadCategories(): Promise<void> {
  try {
    const res = await apiClient.get<ApiDataEnvelope<CategoryItem[]>>('/catalog/categories')
    categories.value = res.data
  } catch {
    // Categories failure is handled alongside products fetch
  }
}

async function loadProducts(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  isOffline.value = false

  try {
    const res = await apiClient.get<ApiDataEnvelope<ProductSummary[]>>('/catalog/products', {
      params: {
        q: searchQuery.value.trim() || undefined,
        category: selectedCategory.value || undefined,
        sort: selectedSort.value,
      },
    })
    products.value = res.data
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      isOffline.value = err.isNetworkError
    } else {
      errorMessage.value = 'Không thể tải danh sách sản phẩm. Vui lòng thử lại.'
    }
  } finally {
    loading.value = false
  }
}

function syncQueryToUrl(): void {
  const nextQuery: Record<string, string> = {}
  if (searchQuery.value.trim() !== '') nextQuery.q = searchQuery.value.trim()
  if (selectedCategory.value !== '') nextQuery.category = selectedCategory.value
  if (selectedSort.value !== 'featured') nextQuery.sort = selectedSort.value
  router.replace({ query: nextQuery }).catch(() => null)
}

function selectCategory(slug: string): void {
  selectedCategory.value = slug
  syncQueryToUrl()
  void loadProducts()
}

function handleSortChange(): void {
  syncQueryToUrl()
  void loadProducts()
}

function resetFilters(): void {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedSort.value = 'featured'
  syncQueryToUrl()
  void loadProducts()
}

function scrollToCatalog(): void {
  const el = document.getElementById('catalog-section')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

async function quickAddFirstAvailable(product: ProductSummary): Promise<void> {
  const candidate = product.variants.find((v) => v.available_stock > 0)
  if (!candidate) {
    await router.push(`/products/${product.slug}`)
    return
  }

  await cartStore.addItem(
    {
      variant_id: candidate.id,
      product_name: product.name,
      product_slug: product.slug,
      image_url: product.image_url,
      sku: candidate.sku,
      color: candidate.color,
      size: candidate.size,
      unit_price_minor: candidate.price_minor,
      available_stock: candidate.available_stock,
    },
    1,
  )

  toastStore.push({
    title: 'Đã thêm vào giỏ hàng',
    description: `${product.name} (${candidate.color} - ${candidate.size})`,
    variant: 'success',
  })
}

watch(searchQuery, () => {
  if (searchDebounceTimer !== undefined) {
    window.clearTimeout(searchDebounceTimer)
  }
  searchDebounceTimer = window.setTimeout(() => {
    syncQueryToUrl()
    void loadProducts()
  }, 280)
})

onMounted(() => {
  void loadCategories()
  void loadProducts()
})
</script>

<template>
  <div class="catalog-page container">
    <section class="editorial-banner surface-panel" aria-labelledby="hero-heading">
      <div class="banner-copy">
        <h1 id="hero-heading">Thiết kế tối giản cho nhịp sống hàng ngày</h1>
        <p class="banner-subtitle prose-measure">
          Tồn kho theo từng mã SKU được đối soát theo thời gian thực, hỗ trợ thanh toán VietQR xác nhận tự động trong vài giây.
        </p>
        <div class="banner-actions">
          <button type="button" class="btn btn-primary btn-lg" @click="scrollToCatalog">
            <span>Xem bộ sưu tập</span>
            <AppIcon name="arrow-right" />
          </button>
          <RouterLink to="/orders" class="btn btn-secondary btn-lg">
            <AppIcon name="package" />
            <span>Tra cứu đơn hàng</span>
          </RouterLink>
        </div>
      </div>

      <dl class="banner-highlights" data-ui="store-commitments">
        <div class="highlight-row">
          <dt>Đối soát tồn kho</dt>
          <dd class="tabular">Chuẩn xác theo từng biến thể màu sắc và kích cỡ</dd>
        </div>
        <div class="highlight-row">
          <dt>Thanh toán linh hoạt</dt>
          <dd>Hỗ trợ VietQR ngân hàng nội địa, thẻ Stripe và COD</dd>
        </div>
        <div class="highlight-row">
          <dt>Ưu đãi minh bạch</dt>
          <dd>Áp dụng mã WELCOME10, VIP200K hoặc FREESHIP trực tiếp ở bước thanh toán</dd>
        </div>
      </dl>
    </section>

    <section id="catalog-section" class="catalog-workspace" aria-label="Danh sách sản phẩm">
      <div class="filter-toolbar">
        <div class="search-box">
          <span class="search-icon">
            <AppIcon name="search" />
          </span>
          <input
            v-model="searchQuery"
            type="search"
            class="input search-input"
            placeholder="Tìm tên sản phẩm hoặc mã SKU"
            aria-label="Tìm tên sản phẩm hoặc mã SKU"
          />
        </div>

        <div class="sort-box">
          <label for="catalog-sort" class="sort-label">Sắp xếp</label>
          <select
            id="catalog-sort"
            v-model="selectedSort"
            class="select sort-select"
            @change="handleSortChange"
          >
            <option value="featured">Nổi bật</option>
            <option value="price_asc">Giá tăng dần</option>
            <option value="price_desc">Giá giảm dần</option>
            <option value="newest">Mới nhất</option>
          </select>
        </div>
      </div>

      <div class="category-pills" role="tablist" aria-label="Lọc theo danh mục">
        <button
          type="button"
          role="tab"
          class="pill-btn"
          :class="{ 'is-selected': selectedCategory === '' }"
          :aria-selected="selectedCategory === ''"
          @click="selectCategory('')"
        >
          <span>Tất cả</span>
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          type="button"
          role="tab"
          class="pill-btn"
          :class="{ 'is-selected': selectedCategory === cat.slug }"
          :aria-selected="selectedCategory === cat.slug"
          @click="selectCategory(cat.slug)"
        >
          <span>{{ cat.name }}</span>
          <span class="pill-count tabular">{{ cat.products_count }}</span>
        </button>
      </div>

      <div v-if="errorMessage" class="callout" :class="isOffline ? 'callout-warning' : 'callout-danger'">
        <AppIcon name="alert" />
        <div class="callout-content">
          <p class="callout-title">
            {{ isOffline ? 'Chưa kết nối được dịch vụ API Backend' : 'Tải danh mục chưa thành công' }}
          </p>
          <p class="callout-desc">{{ errorMessage }}</p>
          <p v-if="isOffline" class="callout-desc">
            Hãy khởi động máy chủ Laravel tại cổng <span class="mono-code">http://localhost:8080/api/v1</span> rồi bấm tải lại dữ liệu.
          </p>
          <div class="callout-actions">
            <button type="button" class="btn btn-secondary btn-sm" @click="loadProducts">
              <AppIcon name="refresh" />
              <span>Thử tải lại</span>
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="loading" class="product-grid" aria-busy="true">
        <div v-for="n in 6" :key="n" class="product-card skeleton-card">
          <div class="skeleton skeleton-media" />
          <div class="skeleton-body">
            <div class="skeleton skeleton-line-sm" />
            <div class="skeleton skeleton-line-lg" />
            <div class="skeleton skeleton-line-md" />
          </div>
        </div>
      </div>

      <div v-else-if="products.length === 0" class="empty-state surface-panel">
        <div class="empty-icon">
          <AppIcon name="search" />
        </div>
        <h2>Không tìm thấy sản phẩm phù hợp</h2>
        <p class="prose-measure">
          Chưa có mặt hàng nào khớp với từ khóa hoặc bộ lọc hiện tại. Bạn có thể xóa bộ lọc để xem toàn bộ danh mục.
        </p>
        <button
          v-if="hasActiveFilters"
          type="button"
          class="btn btn-primary"
          @click="resetFilters"
        >
          <span>Đặt lại bộ lọc</span>
        </button>
      </div>

      <div v-else class="product-grid">
        <article
          v-for="product in products"
          :key="product.id"
          class="product-card surface-panel"
        >
          <RouterLink :to="`/products/${product.slug}`" class="product-media-link">
            <img
              :src="product.image_url"
              :alt="product.name"
              class="product-image"
              loading="lazy"
            />
          </RouterLink>

          <div class="product-body">
            <h3 class="product-title">
              <RouterLink :to="`/products/${product.slug}`" class="title-link">
                {{ product.name }}
              </RouterLink>
            </h3>

            <div class="product-origin">
              <span v-if="product.category" class="origin-category">{{ product.category.name }}</span>
              <span class="origin-brand">{{ product.brand }}</span>
            </div>

            <p class="product-excerpt">{{ product.short_description }}</p>

            <div class="product-rating-stock">
              <div class="rating-group tabular">
                <span class="star-accent">
                  <AppIcon name="star" :filled="true" />
                </span>
                <span class="rating-score">{{ product.rating_avg.toFixed(1) }}</span>
                <span class="rating-count">({{ product.reviews_count }} đánh giá)</span>
              </div>

              <span
                class="badge tabular"
                :class="`badge-${getStockSummary(product.total_available_stock).tone}`"
              >
                {{ getStockSummary(product.total_available_stock).label }}
              </span>
            </div>

            <div class="product-footer">
              <div class="price-stack">
                <span class="price-caption">Giá từ</span>
                <span class="price-value tabular">{{ formatVnd(product.min_price_minor) }}</span>
              </div>

              <div class="card-actions">
                <button
                  v-if="product.total_available_stock > 0"
                  type="button"
                  class="btn btn-secondary btn-sm"
                  aria-label="Thêm nhanh phiên bản mặc định"
                  @click="quickAddFirstAvailable(product)"
                >
                  <AppIcon name="plus" />
                  <span>Thêm</span>
                </button>
                <RouterLink :to="`/products/${product.slug}`" class="btn btn-primary btn-sm">
                  <span>Chi tiết</span>
                </RouterLink>
              </div>
            </div>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>

<style scoped>
.catalog-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-10);
}

.editorial-banner {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  gap: var(--space-8);
  padding: var(--space-8);
  align-items: center;
}

.banner-copy {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.banner-subtitle {
  color: var(--color-fg-muted);
  font-size: var(--text-base);
}

.banner-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  padding-top: var(--space-2);
}

.banner-highlights {
  margin: 0;
  padding: var(--space-5);
  background-color: var(--color-surface-muted);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.highlight-row {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding-bottom: var(--space-3);
  border-bottom: 1px solid var(--color-border);
}

.highlight-row:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.highlight-row dt {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
}

.highlight-row dd {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.catalog-workspace {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.filter-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 260px;
  max-width: 480px;
}

.search-icon {
  position: absolute;
  left: var(--space-3);
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-fg-subtle);
  display: inline-flex;
  pointer-events: none;
}

.search-input {
  padding-left: var(--space-10);
}

.sort-box {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.sort-label {
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--color-fg-muted);
  white-space: nowrap;
}

.sort-select {
  min-width: 176px;
}

.category-pills {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.pill-btn {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 40px;
  padding: var(--space-2) var(--space-4);
  border-radius: var(--radius-pill);
  border: 1px solid var(--color-border);
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

.pill-btn:hover {
  background-color: var(--color-surface-muted);
  color: var(--color-fg);
}

.pill-btn.is-selected {
  background-color: var(--color-primary);
  border-color: var(--color-primary);
  color: var(--color-on-primary);
  font-weight: 600;
}

.pill-count {
  font-size: var(--text-caption);
  opacity: 0.85;
}

.callout-content {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.callout-title {
  font-weight: 600;
}

.callout-desc {
  color: var(--color-fg-muted);
}

.callout-actions {
  padding-top: var(--space-1);
}

.product-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-6);
}

.product-card {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transition:
    border-color var(--duration-normal) var(--ease-out),
    transform var(--duration-normal) var(--ease-out);
}

.product-card:hover {
  border-color: var(--color-border-strong);
  transform: translateY(-2px);
}

.product-media-link {
  display: block;
  aspect-ratio: 4 / 3;
  background-color: var(--color-surface-muted);
  overflow: hidden;
}

.product-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--duration-normal) var(--ease-out);
}

.product-card:hover .product-image {
  transform: scale(1.02);
}

.product-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-5);
}

.product-origin {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.origin-category {
  font-weight: 500;
  color: var(--color-primary);
}

.origin-brand {
  font-weight: 500;
}

.product-title {
  font-size: 1.0625rem;
  line-height: 1.35;
}

.title-link:hover {
  color: var(--color-primary);
}

.product-excerpt {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-rating-stock {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
  flex-wrap: wrap;
  margin-top: auto;
  padding-top: var(--space-2);
}

.rating-group {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-label);
}

.star-accent {
  color: var(--color-warning);
  display: inline-flex;
}

.rating-score {
  font-weight: 600;
  color: var(--color-fg);
}

.rating-count {
  color: var(--color-fg-subtle);
}

.product-footer {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-3);
  padding-top: var(--space-3);
  border-top: 1px solid var(--color-border);
}

.price-stack {
  display: flex;
  flex-direction: column;
}

.price-caption {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.price-value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-fg);
}

.card-actions {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.skeleton-card {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background-color: var(--color-surface);
  overflow: hidden;
}

.skeleton-media {
  aspect-ratio: 4 / 3;
  border-radius: 0;
}

.skeleton-body {
  padding: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
}

.skeleton-line-sm {
  height: 14px;
  width: 45%;
}

.skeleton-line-lg {
  height: 22px;
  width: 85%;
}

.skeleton-line-md {
  height: 18px;
  width: 60%;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
  padding: var(--space-8);
}

.empty-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
  border-radius: var(--radius-md);
  background-color: var(--color-surface-muted);
  color: var(--color-fg-muted);
}

@media (max-width: 1024px) {
  .editorial-banner {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-6);
  }

  .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .product-grid {
    grid-template-columns: minmax(0, 1fr);
  }

  .sort-box {
    width: 100%;
    justify-content: space-between;
  }

  .sort-select {
    flex: 1;
  }
}
</style>
