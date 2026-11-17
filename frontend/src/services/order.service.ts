import type {
  AdminOrderTransitionStatus,
  AdminOverviewAnalytics,
  ApiDataEnvelope,
  CheckoutPayload,
  DevWebhookSimulationResult,
  OrderDetail,
} from '../types/api.types'
import { axiosClient, generateIdempotencyKey } from './axiosClient'

export const orderService = {
  async checkout(payload: CheckoutPayload, idempotencyKey?: string): Promise<OrderDetail> {
    const key = idempotencyKey || generateIdempotencyKey()
    const { data } = await axiosClient.post<ApiDataEnvelope<OrderDetail>>('/checkout', payload, {
      headers: {
        'Idempotency-Key': key,
      },
    })
    return data.data
  },

  async getMyOrders(): Promise<OrderDetail[]> {
    const { data } = await axiosClient.get<ApiDataEnvelope<OrderDetail[]>>('/orders')
    return data.data
  },

  async getOrderByNumber(orderNumber: string): Promise<OrderDetail> {
    const { data } = await axiosClient.get<ApiDataEnvelope<OrderDetail>>(
      `/orders/${encodeURIComponent(orderNumber)}`,
    )
    return data.data
  },

  async simulateDevWebhook(orderNumber: string): Promise<DevWebhookSimulationResult> {
    const { data } = await axiosClient.post<ApiDataEnvelope<DevWebhookSimulationResult>>(
      '/payments/dev-simulate-webhook',
      { order_number: orderNumber },
    )
    return data.data
  },

  async getAdminOrders(): Promise<OrderDetail[]> {
    const { data } = await axiosClient.get<ApiDataEnvelope<OrderDetail[]>>('/admin/orders')
    return data.data
  },

  async updateAdminOrderStatus(
    orderNumber: string,
    status: AdminOrderTransitionStatus,
  ): Promise<OrderDetail> {
    const { data } = await axiosClient.patch<ApiDataEnvelope<OrderDetail>>(
      `/admin/orders/${encodeURIComponent(orderNumber)}/status`,
      { status },
    )
    return data.data
  },

  async getAdminAnalytics(): Promise<AdminOverviewAnalytics> {
    const { data } = await axiosClient.get<ApiDataEnvelope<AdminOverviewAnalytics>>(
      '/admin/analytics/overview',
    )
    return data.data
  },
}
