import type { OrderStatus, PaymentMethod, PaymentStatus } from '../api/types'

const vndFormatter = new Intl.NumberFormat('vi-VN')

export function formatVnd(amountMinor: number): string {
  const safeAmount = Number.isFinite(amountMinor) ? Math.round(amountMinor) : 0
  return `${vndFormatter.format(safeAmount)} ₫`
}

export function formatNumber(value: number): string {
  const safeValue = Number.isFinite(value) ? value : 0
  return vndFormatter.format(safeValue)
}

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
  refund_required: 'Cần hoàn tiền (hết kho khi chuyển trễ)',
}

export function getOrderStatusLabel(status: OrderStatus | string): string {
  if (status in ORDER_STATUS_LABELS) {
    return ORDER_STATUS_LABELS[status as OrderStatus]
  }
  return status
}

export type BadgeTone = 'neutral' | 'primary' | 'success' | 'warning' | 'danger'

export function getOrderStatusTone(status: OrderStatus | string): BadgeTone {
  switch (status) {
    case 'paid':
    case 'completed':
      return 'success'
    case 'processing':
    case 'shipped':
      return 'primary'
    case 'pending_payment':
      return 'warning'
    case 'cancelled':
    case 'refund_required':
      return 'danger'
    default:
      return 'neutral'
  }
}

export function getPaymentStatusLabel(status: PaymentStatus | string): string {
  switch (status) {
    case 'paid':
      return 'Đã thanh toán'
    case 'paid_pending_refund':
      return 'Đã thu - Chờ hoàn tiền'
    case 'unpaid':
      return 'Chưa thanh toán'
    case 'failed':
      return 'Thanh toán thất bại'
    case 'refunded':
      return 'Đã hoàn tiền'
    default:
      return status
  }
}

export function getPaymentStatusTone(status: PaymentStatus | string): BadgeTone {
  switch (status) {
    case 'paid':
      return 'success'
    case 'paid_pending_refund':
    case 'unpaid':
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
