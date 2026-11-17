<script setup lang="ts">
import { onMounted, ref } from 'vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import LoadingSpinner from '../../../components/common/LoadingSpinner.vue'
import { ApiError } from '../../../services/axiosClient'
import { productService } from '../../../services/product.service'
import type { CategoryItem, ProductSummary } from '../../../types/api.types'
import HeroBanner from '../components/HeroBanner.vue'
import ProductCard from '../components/ProductCard.vue'

const categories = ref<CategoryItem[]>([])
const featuredProducts = ref<ProductSummary[]>([])
const loading = ref(true)
const errorMessage = ref<string | null>(null)
const isOffline = ref(false)

async function loadHomeData(): Promise<void> {
  loading.value = true
  errorMessage.value = null
  isOffline.value = false
  try {
    const [cats, prods] = await Promise.all([
      productService.getCategories(),
      productService.getProducts({ sort: 'featured' }),
    ])
    categories.value = cats
    featuredProducts.value = prods.slice(0, 6)
  } catch (err) {
    if (err instanceof ApiError) {
      errorMessage.value = err.message
      isOffline.value = err.isNetworkError
    } else {
      errorMessage.value = 'Không thể tải dữ liệu trang chủ. Vui lòng thử lại.'
    }
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadHomeData()
})
</script>

<template>
  <div class="home-page container">
    <HeroBanner />

    <section v-if="categories.length > 0" class="home-section" aria-labelledby="cat-heading">
      <div class="section-head">
        <div>
          <h2 id="cat-heading">Danh mục tuyển chọn</h2>
          <p class="section-sub">Phân nhóm rõ ràng theo nhu cầu sử dụng hàng ngày.</p>
        </div>
        <RouterLink to="/products" class="btn btn-ghost btn-sm">
          <span>Xem tất cả</span>
          <BaseIcon name="arrow-right" />
        </RouterLink>
      </div>

      <div class="category-cards">
        <RouterLink
          v-for="cat in categories"
          :key="cat.id"
          :to="{ path: '/products', query: { category: cat.slug } }"
          class="category-tile surface-panel"
        >
          <div class="tile-top">
            <h3>{{ cat.name }}</h3>
            <span class="badge badge-primary tabular">{{ cat.products_count }} SP</span>
          </div>
          <p v-if="cat.description" class="tile-desc">{{ cat.description }}</p>
        </RouterLink>
      </div>
    </section>

    <section class="home-section" aria-labelledby="featured-heading">
      <div class="section-head">
        <div>
          <h2 id="featured-heading">Sản phẩm nổi bật</h2>
          <p class="section-sub">Các thiết kế có điểm đánh giá cao và sẵn kho giao ngay.</p>
        </div>
        <RouterLink to="/products" class="btn btn-secondary btn-sm">
          <span>Mở bộ lọc đầy đủ</span>
          <BaseIcon name="arrow-right" />
        </RouterLink>
      </div>

      <div v-if="errorMessage" class="callout" :class="isOffline ? 'callout-warning' : 'callout-danger'">
        <BaseIcon name="alert" />
        <div class="callout-stack">
          <strong>
            {{ isOffline ? 'Chưa kết nối được máy chủ API Backend' : 'Tải dữ liệu chưa thành công' }}
          </strong>
          <p>{{ errorMessage }}</p>
          <div>
            <button type="button" class="btn btn-secondary btn-sm" @click="loadHomeData">
              <BaseIcon name="refresh" />
              <span>Tải lại</span>
            </button>
          </div>
        </div>
      </div>

      <LoadingSpinner v-else-if="loading" label="Đang tải danh sách sản phẩm nổi bật…" :rows="4" />

      <div v-else class="featured-grid">
        <ProductCard
          v-for="product in featuredProducts"
          :key="product.id"
          :product="product"
        />
      </div>
    </section>
  </div>
</template>

<style scoped>
.home-page {
  display: flex;
  flex-direction: column;
  gap: var(--space-12);
}

.home-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-5);
}

.section-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: var(--space-4);
  flex-wrap: wrap;
}

.section-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.category-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: var(--space-4);
}

.category-tile {
  padding: var(--space-5);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
  transition:
    border-color var(--duration-fast) var(--ease-out),
    transform var(--duration-fast) var(--ease-out);
}

.category-tile:hover {
  border-color: var(--color-primary);
  transform: translateY(-2px);
}

.tile-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
}

.tile-desc {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.featured-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-6);
}

.callout-stack {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

@media (max-width: 1024px) {
  .featured-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .featured-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
