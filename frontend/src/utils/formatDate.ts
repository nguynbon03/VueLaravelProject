import type { OrderStatus, PaymentMethod, PaymentStatus } from '../types/api.types'

export type BadgeTone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger'

export function formatDate(iso: string | null | undefined): string {
  if (!iso) return 'Chưa cập nhật'
  const date = new Date(iso)
  if (Number.isNaN(date.getTime())) return iso
  return new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  pending_payment: 'Chờ thanh toán',
  paid: 'Đã thanh toán',
  processing: 'Đang chuẩn bị hàng',
  shipped: 'Đang giao hàng',
  completed: 'Hoàn tất',
  cancelled: 'Đã hủy',
  refund_required: 'Cần hoàn tiền',
  paid_pending_refund: 'Chờ xử lý hoàn tiền',
}

export function getOrderStatusLabel(status: OrderStatus | string): string {
  if (status in ORDER_STATUS_LABELS) {
    return ORDER_STATUS_LABELS[status as OrderStatus]
  }
  return status
}

export function getOrderStatusTone(status: OrderStatus | string): BadgeTone {
  switch (status) {
    case 'paid':
    case 'completed':
      return 'success'
    case 'processing':
    case 'shipped':
      return 'primary'
    case 'pending_payment':
    case 'refund_required':
    case 'paid_pending_refund':
      return 'warning'
    case 'cancelled':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function getPaymentStatusLabel(status: PaymentStatus | string): string {
  switch (status) {
    case 'paid':
      return 'Đã thanh toán'
    case 'unpaid':
      return 'Chưa thanh toán'
    case 'failed':
      return 'Thanh toán thất bại'
    case 'refunded':
      return 'Đã hoàn tiền'
    case 'refund_required':
    case 'paid_pending_refund':
      return 'Cần hoàn tiền'
    default:
      return status
  }
}

export function getPaymentStatusTone(status: PaymentStatus | string): BadgeTone {
  switch (status) {
    case 'paid':
      return 'success'
    case 'unpaid':
    case 'refund_required':
    case 'paid_pending_refund':
      return 'warning'
    case 'failed':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function getPaymentMethodLabel(method: PaymentMethod | string): string {
  switch (method) {
    case 'vietqr':
      return 'VietQR Chuyển khoản 2 giây'
    case 'stripe':
      return 'Thẻ quốc tế Stripe'
    case 'cod':
      return 'Thanh toán khi nhận hàng COD'
    default:
      return method
  }
}

export function getStockSummary(stock: number): { label: string; tone: BadgeTone } {
  if (stock <= 0) {
    return { label: 'Hết hàng', tone: 'danger' }
  }
  if (stock <= 5) {
    return { label: `Sắp hết (còn ${stock})`, tone: 'warning' }
  }
  return { label: `Còn ${stock} sản phẩm`, tone: 'success' }
}

export const VIETNAM_PROVINCES: readonly string[] = [
  'TP. Hồ Chí Minh',
  'Hà Nội',
  'Đà Nẵng',
  'Hải Phòng',
  'Cần Thơ',
  'Bình Dương',
  'Đồng Nai',
  'Khánh Hòa',
  'Lâm Đồng',
  'Quảng Ninh',
  'Thừa Thiên Huế',
  'Nghệ An',
  'Thanh Hóa',
  'Bắc Ninh',
  'Bà Rịa - Vũng Tàu',
]
