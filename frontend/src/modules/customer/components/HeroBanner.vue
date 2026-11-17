<script setup lang="ts">
import BaseIcon from '../../../components/base/BaseIcon.vue'
</script>

<template>
  <section class="hero-banner surface-panel" aria-labelledby="hero-heading">
    <div class="hero-copy">
      <h1 id="hero-heading">Thiết kế tối giản cho nhịp sống hàng ngày</h1>
      <p class="hero-subtitle prose-measure">
        Tồn kho từng mã SKU được đối soát trực tiếp trên sổ cái, kết hợp thanh toán VietQR xác nhận tự động qua Webhook chữ ký số.
      </p>
      <div class="hero-actions">
        <RouterLink to="/products" class="btn btn-primary btn-lg">
          <span>Khám phá sản phẩm</span>
          <BaseIcon name="arrow-right" />
        </RouterLink>
        <RouterLink to="/profile" class="btn btn-secondary btn-lg">
          <BaseIcon name="package" />
          <span>Tra cứu đơn hàng</span>
        </RouterLink>
      </div>
    </div>

    <dl class="hero-commitments" data-ui="hero-commitments">
      <div class="commitment-item">
        <dt>Tồn kho chính xác theo SKU</dt>
        <dd>Cập nhật tức thì theo màu sắc, kích cỡ và khóa hàng an toàn khi đặt đơn</dd>
      </div>
      <div class="commitment-item">
        <dt>Thanh toán đa phương thức</dt>
        <dd>Quét mã VietQR ngân hàng nội địa, thẻ quốc tế Stripe hoặc nhận hàng COD</dd>
      </div>
      <div class="commitment-item">
        <dt>Ưu đãi minh bạch tại giỏ hàng</dt>
        <dd>Kiểm tra trực tiếp mã WELCOME10, VIP200K và FREESHIP trước khi xác nhận</dd>
      </div>
    </dl>
  </section>
</template>

<style scoped>
.hero-banner {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
  gap: var(--space-8);
  padding: var(--space-8);
  align-items: center;
}

.hero-copy {
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.hero-subtitle {
  color: var(--color-fg-muted);
  font-size: var(--text-base);
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  padding-top: var(--space-2);
}

.hero-commitments {
  margin: 0;
  padding: var(--space-5);
  background-color: var(--color-surface-muted);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--space-4);
}

.commitment-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
  padding-bottom: var(--space-3);
  border-bottom: 1px solid var(--color-border);
}

.commitment-item:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.commitment-item dt {
  font-size: var(--text-sm);
  font-weight: 600;
  color: var(--color-fg);
}

.commitment-item dd {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-fg-muted);
}

@media (max-width: 960px) {
  .hero-banner {
    grid-template-columns: minmax(0, 1fr);
    padding: var(--space-6);
  }
}
</style>
