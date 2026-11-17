import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastVariant = 'success' | 'warning' | 'danger' | 'info'

export interface ToastItem {
  id: number
  title: string
  description?: string
  variant: ToastVariant
}

export const useToastStore = defineStore('toast', () => {
  const items = ref<ToastItem[]>([])
  let counter = 1

  function dismiss(id: number): void {
    items.value = items.value.filter((item) => item.id !== id)
  }

  function push(payload: { title: string; description?: string; variant?: ToastVariant; durationMs?: number }): void {
    const id = counter++
    const variant = payload.variant ?? 'info'
    items.value.push({
      id,
      title: payload.title,
      description: payload.description,
      variant,
    })

    const duration = payload.durationMs ?? 4200
    if (duration > 0) {
      window.setTimeout(() => {
        dismiss(id)
      }, duration)
    }
  }

  return {
    items,
    push,
    dismiss,
  }
})
