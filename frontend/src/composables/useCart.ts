import { storeToRefs } from 'pinia'
import { computed } from 'vue'
import { useCartStore } from '../stores/cart.store'

export function useCart() {
  const cartStore = useCartStore()
  const {
    items,
    couponCode,
    province,
    quote,
    quoteLoading,
    quoteError,
    recentOrderNumbers,
    totalQuantity,
    fallbackSubtotalMinor,
    displayLines,
  } = storeToRefs(cartStore)

  const subtotalMinor = computed(
    () => quote.value?.subtotal_minor ?? fallbackSubtotalMinor.value,
  )
  const discountMinor = computed(() => quote.value?.discount_minor ?? 0)
  const shippingFeeMinor = computed(() => quote.value?.shipping_fee_minor ?? 30000)
  const grandTotalMinor = computed(
    () =>
      quote.value?.grand_total_minor ??
      Math.max(0, subtotalMinor.value - discountMinor.value + shippingFeeMinor.value),
  )

  return {
    items,
    couponCode,
    province,
    quote,
    quoteLoading,
    quoteError,
    recentOrderNumbers,
    totalQuantity,
    fallbackSubtotalMinor,
    displayLines,
    subtotalMinor,
    discountMinor,
    shippingFeeMinor,
    grandTotalMinor,
    addItem: cartStore.addItem,
    updateQuantity: cartStore.updateQuantity,
    removeItem: cartStore.removeItem,
    applyCoupon: cartStore.applyCoupon,
    setProvince: cartStore.setProvince,
    refreshQuote: cartStore.refreshQuote,
    clearCart: cartStore.clearCart,
    recordPlacedOrder: cartStore.recordPlacedOrder,
  }
}
