import type {
  AdminCreateProductPayload,
  AdminInventoryAdjustPayload,
  AdminInventoryAdjustResult,
  ApiDataEnvelope,
  CategoryItem,
  ProductDetail,
  ProductFilterParams,
  ProductReview,
  ProductSummary,
} from '../types/api.types'
import { axiosClient } from './axiosClient'

export const productService = {
  async getCategories(): Promise<CategoryItem[]> {
    const { data } = await axiosClient.get<ApiDataEnvelope<CategoryItem[]>>('/catalog/categories')
    return data.data
  },

  async getProducts(params?: ProductFilterParams): Promise<ProductSummary[]> {
    const cleanedParams: Record<string, string> = {}
    if (params?.q && params.q.trim() !== '') cleanedParams.q = params.q.trim()
    if (params?.category && params.category.trim() !== '') cleanedParams.category = params.category
    if (params?.sort) cleanedParams.sort = params.sort

    const { data } = await axiosClient.get<ApiDataEnvelope<ProductSummary[]>>('/catalog/products', {
      params: cleanedParams,
    })
    return data.data
  },

  async getProductBySlug(slug: string): Promise<ProductDetail> {
    const { data } = await axiosClient.get<ApiDataEnvelope<ProductDetail>>(
      `/catalog/products/${slug}`,
    )
    return data.data
  },

  async createReview(
    slug: string,
    payload: { rating: number; comment: string },
  ): Promise<ProductReview> {
    const { data } = await axiosClient.post<ApiDataEnvelope<ProductReview>>(
      `/catalog/products/${slug}/reviews`,
      payload,
    )
    return data.data
  },

  async createProduct(payload: AdminCreateProductPayload): Promise<ProductSummary> {
    const { data } = await axiosClient.post<ApiDataEnvelope<ProductSummary>>(
      '/admin/products',
      payload,
    )
    return data.data
  },

  async adjustInventory(
    payload: AdminInventoryAdjustPayload,
  ): Promise<AdminInventoryAdjustResult> {
    const { data } = await axiosClient.post<ApiDataEnvelope<AdminInventoryAdjustResult>>(
      '/admin/inventory/adjust',
      payload,
    )
    return data.data
  },
}
