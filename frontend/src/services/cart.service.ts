import type { ApiDataEnvelope, CartItemInput, CartQuote } from '../types/api.types'
import { axiosClient } from './axiosClient'

export const cartService = {
  async quoteCart(payload: {
    items: CartItemInput[]
    coupon_code?: string | null
    province?: string | null
  }): Promise<CartQuote> {
    const { data } = await axiosClient.post<ApiDataEnvelope<CartQuote>>('/cart/quote', payload)
    return data.data
  },
}
