import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { ApiError, apiClient } from '../../shared/api/client'
import type { ApiDataEnvelope, CartItemSnapshot, CartQuote, CartQuoteLine } from '../../shared/api/types'

const CART_ITEMS_KEY = 'cleancommerce_cart_items'
const CART_COUPON_KEY = 'cleancommerce_cart_coupon'
const CART_PROVINCE_KEY = 'cleancommerce_cart_province'
const RECENT_ORDERS_KEY = 'cleancommerce_recent_orders'

function loadJson<T>(key: string, fallback: T): T {
  try {
    const raw = localStorage.getItem(key)
    if (!raw) return fallback
    return JSON.parse(raw) as T
  } catch {
    return fallback
  }
}

function saveJson(key: string, value: unknown): void {
  try {
    localStorage.setItem(key, JSON.stringify(value))
  } catch {
    // Ignore storage limitations
  }
}

export const useCartStore = defineStore('cart', () => {
  const items = ref<CartItemSnapshot[]>(loadJson<CartItemSnapshot[]>(CART_ITEMS_KEY, []))
  const couponCode = ref<string>(loadJson<string>(CART_COUPON_KEY, ''))
  const province = ref<string>(loadJson<string>(CART_PROVINCE_KEY, 'TP. Hồ Chí Minh'))
  const quote = ref<CartQuote | null>(null)
  const quoteLoading = ref(false)
  const quoteError = ref<string | null>(null)
  const recentOrderNumbers = ref<string[]>(loadJson<string[]>(RECENT_ORDERS_KEY, []))

  const totalQuantity = computed(() =>
    items.value.reduce((sum, item) => sum + item.quantity, 0),
  )

  const fallbackSubtotalMinor = computed(() =>
    items.value.reduce((sum, item) => sum + item.unit_price_minor * item.quantity, 0),
  )

  const displayLines = computed<CartQuoteLine[]>(() => {
    if (quote.value && quote.value.lines.length > 0) {
      return quote.value.lines
    }
    return items.value.map((item) => ({
      variant_id: item.variant_id,
      product_name: item.product_name,
      product_slug: item.product_slug,
      image_url: item.image_url,
      sku: item.sku,
      color: item.color,
      size: item.size,
      unit_price_minor: item.unit_price_minor,
      quantity: item.quantity,
      line_total_minor: item.unit_price_minor * item.quantity,
      available_stock: item.available_stock,
    }))
  })

  function persistState(): void {
    saveJson(CART_ITEMS_KEY, items.value)
    saveJson(CART_COUPON_KEY, couponCode.value)
    saveJson(CART_PROVINCE_KEY, province.value)
  }

  async function refreshQuote(): Promise<CartQuote | null> {
    if (items.value.length === 0) {
      quote.value = null
      quoteError.value = null
      return null
    }

    quoteLoading.value = true
    quoteError.value = null
    try {
      const res = await apiClient.post<ApiDataEnvelope<CartQuote>>('/cart/quote', {
        items: items.value.map((item) => ({
          variant_id: item.variant_id,
          quantity: item.quantity,
        })),
        coupon_code: couponCode.value.trim() !== '' ? couponCode.value.trim() : null,
        province: province.value.trim() !== '' ? province.value.trim() : null,
      })
      quote.value = res.data
      return res.data
    } catch (err) {
      quoteError.value =
        err instanceof ApiError
          ? err.message
          : 'Tạm thời chưa đồng bộ được báo giá từ máy chủ.'
      return null
    } finally {
      quoteLoading.value = false
    }
  }

  async function addItem(snapshot: Omit<CartItemSnapshot, 'quantity'>, quantity = 1): Promise<void> {
    const safeQty = Math.max(1, Math.round(quantity))
    const existing = items.value.find((item) => item.variant_id === snapshot.variant_id)
    if (existing) {
      const nextQty = Math.min(existing.quantity + safeQty, Math.max(1, snapshot.available_stock))
      existing.quantity = nextQty
      existing.unit_price_minor = snapshot.unit_price_minor
      existing.available_stock = snapshot.available_stock
    } else {
      items.value.push({
        ...snapshot,
        quantity: Math.min(safeQty, Math.max(1, snapshot.available_stock)),
      })
    }
    persistState()
    await refreshQuote()
  }

  async function updateQuantity(variantId: number, nextQuantity: number): Promise<void> {
    if (nextQuantity <= 0) {
      await removeItem(variantId)
      return
    }
    const target = items.value.find((item) => item.variant_id === variantId)
    if (!target) return
    const maxAllowed = target.available_stock > 0 ? target.available_stock : nextQuantity
    target.quantity = Math.min(Math.round(nextQuantity), maxAllowed)
    persistState()
    await refreshQuote()
  }

  async function removeItem(variantId: number): Promise<void> {
    items.value = items.value.filter((item) => item.variant_id !== variantId)
    persistState()
    await refreshQuote()
  }

  async function applyCoupon(code: string): Promise<CartQuote | null> {
    couponCode.value = code.trim()
    persistState()
    return refreshQuote()
  }

  async function setProvince(nextProvince: string): Promise<void> {
    province.value = nextProvince
    persistState()
    if (items.value.length > 0) {
      await refreshQuote()
    }
  }

  function clearCart(): void {
    items.value = []
    couponCode.value = ''
    quote.value = null
    quoteError.value = null
    persistState()
  }

  function recordPlacedOrder(orderNumber: string): void {
    if (!orderNumber) return
    const filtered = recentOrderNumbers.value.filter((num) => num !== orderNumber)
    recentOrderNumbers.value = [orderNumber, ...filtered].slice(0, 12)
    saveJson(RECENT_ORDERS_KEY, recentOrderNumbers.value)
  }

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
    addItem,
    updateQuantity,
    removeItem,
    applyCoupon,
    setProvince,
    refreshQuote,
    clearCart,
    recordPlacedOrder,
  }
})
