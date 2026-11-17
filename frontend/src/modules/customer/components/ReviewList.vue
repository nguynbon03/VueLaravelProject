<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import BaseButton from '../../../components/base/BaseButton.vue'
import BaseIcon from '../../../components/base/BaseIcon.vue'
import { useAuth } from '../../../composables/useAuth'
import { useNotification } from '../../../composables/useNotification'
import { ApiError } from '../../../services/axiosClient'
import { productService } from '../../../services/product.service'
import type { ProductReview } from '../../../types/api.types'
import { formatDate } from '../../../utils/formatDate'

const props = defineProps<{
  productSlug: string
  ratingAvg: number
  reviews: ProductReview[]
}>()

const emit = defineEmits<{
  reviewAdded: [review: ProductReview]
}>()

const route = useRoute()
const { isAuthenticated } = useAuth()
const { success } = useNotification()

const rating = ref<number>(5)
const comment = ref<string>('')
const submitting = ref(false)
const formError = ref<string | null>(null)

async function handleSubmit(): Promise<void> {
  formError.value = null
  if (comment.value.trim().length < 5) {
    formError.value = 'Vui lòng nhập nhận xét tối thiểu 5 ký tự.'
    return
  }

  submitting.value = true
  try {
    const created = await productService.createReview(props.productSlug, {
      rating: rating.value,
      comment: comment.value.trim(),
    })
    comment.value = ''
    rating.value = 5
    success('Đã gửi đánh giá sản phẩm')
    emit('reviewAdded', created)
  } catch (err) {
    formError.value =
      err instanceof ApiError
        ? err.message
        : 'Không thể gửi đánh giá lúc này. Vui lòng thử lại.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <section class="reviews-panel surface-panel" aria-labelledby="reviews-heading">
    <div class="reviews-head">
      <h2 id="reviews-heading">Đánh giá thực tế từ khách hàng</h2>
      <p class="reviews-sub tabular">
        Điểm trung bình {{ ratingAvg.toFixed(1) }} trên 5 ({{ reviews.length }} nhận xét)
      </p>
    </div>

    <div class="reviews-grid">
      <div class="reviews-items">
        <div v-if="reviews.length === 0" class="muted-block">
          <p>Chưa có đánh giá nào cho sản phẩm này. Hãy chia sẻ trải nghiệm đầu tiên của bạn.</p>
        </div>

        <article v-for="rev in reviews" :key="rev.id" class="review-row">
          <div class="review-top">
            <div class="author-line">
              <strong class="author-name">{{ rev.user_name }}</strong>
              <span v-if="rev.is_verified_purchase" class="badge badge-success">
                <BaseIcon name="check" />
                <span>Đã mua hàng</span>
              </span>
            </div>
            <time class="review-time tabular">{{ formatDate(rev.created_at) }}</time>
          </div>

          <div class="stars-row" :aria-label="`Đánh giá ${rev.rating} trên 5 sao`">
            <span
              v-for="s in 5"
              :key="s"
              class="star-glyph"
              :class="{ 'is-filled': s <= rev.rating }"
            >
              <BaseIcon name="star" :filled="s <= rev.rating" />
            </span>
          </div>

          <p class="review-body prose-measure">{{ rev.comment }}</p>
        </article>
      </div>

      <div class="review-composer muted-block">
        <h3>Gửi nhận xét của bạn</h3>

        <form v-if="isAuthenticated" class="composer-form" @submit.prevent="handleSubmit">
          <div class="field">
            <span class="field-label">Mức độ hài lòng</span>
            <div class="star-picker" role="radiogroup" aria-label="Chọn số sao">
              <button
                v-for="s in 5"
                :key="s"
                type="button"
                class="star-btn"
                :class="{ 'is-selected': s <= rating }"
                :aria-label="`${s} sao`"
                @click="rating = s"
              >
                <BaseIcon name="star" :filled="s <= rating" />
              </button>
              <span class="star-count tabular">{{ rating }} / 5</span>
            </div>
          </div>

          <div class="field">
            <label for="review-comment" class="field-label">Nội dung đánh giá</label>
            <textarea
              id="review-comment"
              v-model="comment"
              class="textarea"
              placeholder="Chia sẻ trải nghiệm về chất liệu, kích cỡ và độ hoàn thiện thực tế"
              required
            />
          </div>

          <p v-if="formError" class="field-error">{{ formError }}</p>

          <BaseButton type="submit" :loading="submitting">
            <span>Gửi đánh giá</span>
          </BaseButton>
        </form>

        <div v-else class="guest-prompt">
          <p>Đăng nhập tài khoản để gửi đánh giá và gắn nhãn xác thực đơn hàng đã mua.</p>
          <RouterLink
            :to="{ path: '/auth/login', query: { redirect: route.fullPath } }"
            class="btn btn-secondary"
          >
            <BaseIcon name="user" />
            <span>Đăng nhập ngay</span>
          </RouterLink>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.reviews-panel {
  padding: var(--space-8);
  display: flex;
  flex-direction: column;
  gap: var(--space-6);
}

.reviews-sub {
  margin-top: var(--space-1);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.reviews-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(0, 1fr);
  gap: var(--space-8);
  align-items: start;
}

.reviews-items {
  display: flex;
  flex-direction: column;
}

.review-row {
  padding-block: var(--space-4);
  border-bottom: 1px solid var(--color-border);
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}

.review-row:first-child {
  padding-top: 0;
}

.review-row:last-child {
  border-bottom: none;
}

.review-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  flex-wrap: wrap;
}

.author-line {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  flex-wrap: wrap;
}

.author-name {
  font-size: var(--text-sm);
}

.review-time {
  font-size: var(--text-caption);
  color: var(--color-fg-subtle);
}

.stars-row {
  display: inline-flex;
  gap: 2px;
}

.star-glyph {
  color: var(--color-border-strong);
  display: inline-flex;
}

.star-glyph.is-filled {
  color: var(--color-warning);
}

.review-body {
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

.review-composer {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.composer-form {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.star-picker {
  display: inline-flex;
  align-items: center;
  gap: var(--space-1);
}

.star-btn {
  width: 38px;
  height: 38px;
  border: 1px solid var(--color-border-strong);
  border-radius: var(--radius-sm);
  background-color: transparent;
  color: var(--color-border-strong);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.star-btn.is-selected {
  color: var(--color-warning);
  border-color: var(--color-warning);
}

.star-count {
  margin-left: var(--space-2);
  font-size: var(--text-sm);
  font-weight: 600;
}

.guest-prompt {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: var(--space-3);
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

@media (max-width: 960px) {
  .reviews-panel {
    padding: var(--space-5);
  }

  .reviews-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}
</style>
