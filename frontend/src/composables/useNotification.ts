import { storeToRefs } from 'pinia'
import { useUiStore, type ToastVariant } from '../stores/ui.store'

export function useNotification() {
  const uiStore = useUiStore()
  const { toasts } = storeToRefs(uiStore)

  function notify(payload: {
    title: string
    description?: string
    variant?: ToastVariant
    durationMs?: number
  }): void {
    uiStore.pushToast(payload)
  }

  function success(title: string, description?: string): void {
    uiStore.pushToast({ title, description, variant: 'success' })
  }

  function error(title: string, description?: string): void {
    uiStore.pushToast({ title, description, variant: 'danger' })
  }

  function warning(title: string, description?: string): void {
    uiStore.pushToast({ title, description, variant: 'warning' })
  }

  function info(title: string, description?: string): void {
    uiStore.pushToast({ title, description, variant: 'info' })
  }

  return {
    toasts,
    notify,
    success,
    error,
    warning,
    info,
    dismiss: uiStore.dismissToast,
  }
}
