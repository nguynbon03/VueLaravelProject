<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../components/common/LoadingSpinner.vue'
import Pagination from '../../../components/common/Pagination.vue'
import { ApiError } from '../../../services/axiosClient'
import { productService } from '../../../services/product.service'
import type { CatalogSortOption, CategoryItem, ProductSummary } from '../../../types/api.types'
import ProductCard from '../components/ProductCard.vue'

const PAGE_SIZE = 9

const route = useRoute()
const router = useRouter()

const categories = ref<CategoryItem[]>([])
const allProducts = ref<ProductSummary[]>([])
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
const currentPage = ref<number>(1)

let debounceTimer: number | undefined

function isValidSort(val: unknown): val is CatalogSortOption {
  return val === 'featured' || val === 'price_asc' || val === 'price_desc' || val === 'newest'
}

const totalPages = computed(() => Math.max(1, Math.ceil(allProducts.value.length / PAGE_SIZE)))

const paginatedProducts = computed<ProductSummary[]>(() => {
  const start = (currentPage.value - 1) * PAGE_SIZE
  return allProducts.value.slice(start, start + PAGE_SIZE)
})

const hasActiveFilters = computed(
  () =>
    searchQuery.value.trim() !== '' ||
    selectedCategory.value !== '' ||
    selectedSort.value !== 'featured',
)

const resultCount = computed(() => allProducts.value.length)

function syncUrlQuery(): void {
  const nextQuery: Record<string, string> = {}
  if (searchQuery.value.trim() !== '') nextQuery.q = searchQuery.value.trim()
  if (selectedCategory.value !== '') nextQuery.category = selectedCategory.value
  if (selectedSort.value !== 'featured') nextQuery.sort = selectedSort.value
  router.replace({ query: nextQuery }).catch(() => null)
}

async function fetchCatalog(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  isOffline.value = false
  try {
    allProducts.value = await productService.getProducts({
      q: searchQuery.value,
      category: selectedCategory.value,
      sort: selectedSort.value,
    })
    if (currentPage.value > totalPages.value) {
      currentPage.value = 1
    }
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      isOffline.value = err.isNetworkError
    } else {
      errorMessage.value = 'Không thể tải danh sách sản phẩm.'
    }
  } finally {
    loading.value = false
  }
}

function selectCategory(slug: string): void {
  selectedCategory.value = slug
  currentPage.value = 1
  syncUrlQuery()
  void fetchCatalog()
}

function handleSortChange(): void {
  currentPage.value = 1
  syncUrlQuery()
  void fetchCatalog()
}

function resetFilters(): void {
  searchQuery.value = ''
  selectedCategory.value = ''
  selectedSort.value = 'featured'
  currentPage.value = 1
  syncUrlQuery()
  void fetchCatalog()
}

watch(searchQuery, () => {
  if (debounceTimer !== undefined) {
    window.clearTimeout(debounceTimer)
  }
  debounceTimer = window.setTimeout(() => {
    currentPage.value = 1
    syncUrlQuery()
    void fetchCatalog()
  }, 260)
})

watch(
  () => route.query.category,
  (catSlug) => {
    const normalized = typeof catSlug === 'string' ? catSlug : ''
    if (normalized !== selectedCategory.value) {
      selectedCategory.value = normalized
      currentPage.value = 1
      void fetchCatalog()
    }
  },
)

onMounted(async () => {
  void fetchCatalog()
  try {
    categories.value = await productService.getCategories()
  } catch {
    // Handled by primary error banner
  }
})
</script>

<template>
  <div class="catalog-list-page container">
    <div class="catalog-header">
      <div>
        <h1>Danh mục sản phẩm</h1>
        <p class="catalog-sub tabular">
          Hiển thị {{ resultCount }} sản phẩm sẵn sàng đặt mua trực tuyến.
        </p>
      </div>
    </div>

    <div class="filter-bar">
      <div class="search-wrap">
        <span class="search-icon">
          <BaseIcon name="search" />
        </span>
        <input
          v-model="searchQuery"
          type="search"
          class="input search-input"
          placeholder="Tìm tên sản phẩm hoặc mã SKU"
          aria-label="Tìm tên sản phẩm hoặc mã SKU"
        />
      </div>

      <div class="sort-wrap">
        <label for="sort-select" class="sort-label">Sắp xếp</label>
        <select
          id="sort-select"
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
      <BaseIcon name="alert" />
      <div class="callout-stack">
        <strong>
          {{ isOffline ? 'Chưa kết nối được máy chủ API Backend' : 'Tải danh mục chưa thành công' }}
        </strong>
        <p>{{ errorMessage }}</p>
        <div>
          <button type="button" class="btn btn-secondary btn-sm" @click="fetchCatalog">
            <BaseIcon name="refresh" />
            <span>Thử tải lại</span>
          </button>
        </div>
      </div>
    </div>

    <LoadingSpinner v-else-if="loading" label="Đang lọc danh sách sản phẩm…" :rows="4" />

    <div v-else-if="paginatedProducts.length === 0" class="empty-box surface-panel">
      <BaseIcon name="search" />
      <h2>Không tìm thấy sản phẩm phù hợp</h2>
      <p class="prose-measure">
        Không có mặt hàng nào khớp với điều kiện lọc hiện tại. Hãy đặt lại bộ lọc để xem toàn bộ sản phẩm.
      </p>
      <button v-if="hasActiveFilters" type="button" class="btn btn-primary" @click="resetFilters">
        <span>Đặt lại bộ lọc</span>
      </button>
    </div>

    <template v-else>
      <div class="products-grid">
        <ProductCard
          v-for="product in paginatedProducts"
          :key="product.id"
          :product="product"
        />
      </div>

      <Pagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="resultCount"
        @change="currentPage = $event"
      />
    </template>
  </div>
</template>

<style scoped>
.catalog-list-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.catalog-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.search-wrap {
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

.sort-wrap {
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
  border: 1px solid var(--color-border-strong);
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

.products-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-6);
}

.empty-box {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
}

.callout-stack {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

@media (max-width: 1024px) {
  .products-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .products-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
