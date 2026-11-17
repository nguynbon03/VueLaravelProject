export function isValidEmail(email: string): boolean {
  const trimmed = email.trim()
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)
}

export function isValidVietnamPhone(phone: string): boolean {
  const digits = phone.replace(/[\s.-]/g, '')
  return /^(0|\+84)\d{8,10}$/.test(digits)
}

export interface CheckoutFormInput {
  customer_name: string
  customer_email: string
  customer_phone: string
  shipping_address: string
  shipping_province: string
}

export function validateCheckoutForm(input: CheckoutFormInput): Record<string, string> {
  const errors: Record<string, string> = {}

  if (input.customer_name.trim().length < 2) {
    errors.customer_name = 'Vui lòng nhập họ và tên người nhận (tối thiểu 2 ký tự).'
  }
  if (!isValidEmail(input.customer_email)) {
    errors.customer_email = 'Địa chỉ email chưa đúng định dạng.'
  }
  if (!isValidVietnamPhone(input.customer_phone)) {
    errors.customer_phone = 'Số điện thoại Việt Nam chưa hợp lệ (bắt đầu bằng 0 hoặc +84).'
  }
  if (input.shipping_province.trim().length === 0) {
    errors.shipping_province = 'Vui lòng chọn Tỉnh hoặc Thành phố nhận hàng.'
  }
  if (input.shipping_address.trim().length < 5) {
    errors.shipping_address = 'Vui lòng nhập địa chỉ số nhà, tên đường chi tiết.'
  }

  return errors
}
