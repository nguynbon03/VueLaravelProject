const vndFormatter = new Intl.NumberFormat('vi-VN')

export function formatVnd(amountMinor: number): string {
  const safeAmount = Number.isFinite(amountMinor) ? Math.round(amountMinor) : 0
  return `${vndFormatter.format(safeAmount)} ₫`
}

export function formatNumber(value: number): string {
  const safeValue = Number.isFinite(value) ? value : 0
  return vndFormatter.format(safeValue)
}
