<script setup lang="ts">
import { useRouter } from 'vue-router'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import { useCart } from '../../../composables/useCart'
import { useNotification } from '../../../composables/useNotification'
import type { ProductSummary } from '../../../types/api.types'
import { formatVnd } from '../../../utils/formatCurrency'
import { getStockSummary } from '../../../utils/formatDate'

const props = defineProps<{
  product: ProductSummary
}>()

const router = useRouter()
const { addItem } = useCart()
const { success } = useNotification()

async function handleQuickAdd(): Promise<void> {
  const candidate = props.product.variants.find((v) => v.available_stock > 0)
  if (!candidate) {
    await router.push(`/products/${props.product.slug}`)
    return
  }

  await addItem(
    {
      variant_id: candidate.id,
      product_name: props.product.name,
      product_slug: props.product.slug,
      image_url: props.product.image_url,
      sku: candidate.sku,
      color: candidate.color,
      size: candidate.size,
      unit_price_minor: candidate.price_minor,
      available_stock: candidate.available_stock,
    },
    1,
  )

  success(
    'Đã thêm vào giỏ hàng',
    `${props.product.name} (${candidate.color} - ${candidate.size})`,
  )
}
</script>

<template>
  <article class="product-card surface-panel">
    <RouterLink :to="`/products/${product.slug}`" class="product-media">
      <img
        :src="product.image_url"
        :alt="product.name"
        class="product-img"
      />
    </RouterLink>

    <div class="product-body">
      <h3 class="product-title">
        <RouterLink :to="`/products/${product.slug}`" class="title-link">
          {{ product.name }}
        </RouterLink>
      </h3>

      <div class="product-origin">
        <span v-if="product.category" class="origin-cat">{{ product.category.name }}</span>
        <span class="origin-brand">{{ product.brand }}</span>
      </div>

      <p class="product-excerpt">{{ product.short_description }}</p>

      <div class="product-stats">
        <div class="rating-inline tabular">
          <span class="star-tone">
            <BaseIcon name="star" :filled="true" />
          </span>
          <span class="rating-val">{{ product.rating_avg.toFixed(1) }}</span>
          <span class="rating-cnt">({{ product.reviews_count }})</span>
        </div>

        <span
          class="badge tabular"
          :class="`badge-${getStockSummary(product.total_available_stock).tone}`"
        >
          {{ getStockSummary(product.total_available_stock).label }}
        </span>
      </div>

      <div class="product-footer">
        <div class="price-col">
          <span class="price-note">Giá từ</span>
          <span class="price-figure tabular">{{ formatVnd(product.min_price_minor) }}</span>
        </div>

        <div class="card-cta">
          <button
            v-if="product.total_available_stock > 0"
            type="button"
            class="btn btn-secondary btn-sm"
            aria-label="Thêm nhanh vào giỏ"
            @click="handleQuickAdd"
          >
            <BaseIcon name="plus" />
            <span>Thêm</span>
          </button>
          <RouterLink :to="`/products/${product.slug}`" class="btn btn-primary btn-sm">
            <span>Chi tiết</span>
          </RouterLink>
        </div>
      </div>
    </div>
  </article>
</template>

<style scoped>
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

.product-media {
  display: block;
  aspect-ratio: 4 / 3;
  background-color: var(--color-surface-muted);
  overflow: hidden;
}

.product-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--duration-normal) var(--ease-out);
}

.product-card:hover .product-img {
  transform: scale(1.02);
}

.product-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  padding: var(--space-5);
}

.product-title {
  font-size: 1.0625rem;
  line-height: 1.35;
}

.title-link:hover {
  color: var(--color-primary);
}

.product-origin {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.origin-cat {
  font-weight: 500;
  color: var(--color-primary);
}

.origin-brand {
  font-weight: 500;
}

.product-excerpt {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.product-stats {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
  flex-wrap: wrap;
  margin-top: auto;
  padding-top: var(--space-2);
}

.rating-inline {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
  font-size: var(--text-label);
}

.star-tone {
  color: var(--color-warning);
  display: inline-flex;
}

.rating-val {
  font-weight: 600;
}

.rating-cnt {
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

.price-col {
  display: flex;
  flex-direction: column;
}

.price-note {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.price-figure {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--color-fg);
}

.card-cta {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}

.product-card :focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 2px;
}

.product-card button:active:not(:disabled) {
  transform: translateY(1px);
}

.product-card button:disabled {
  background-color: var(--color-surface-muted);
  color: var(--color-fg-subtle);
  cursor: not-allowed;
}

@media (prefers-reduced-motion: reduce) {
  .product-card,
  .product-img {
    transition-duration: 0.01ms !important;
  }
}
</style>
