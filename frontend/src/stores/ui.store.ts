import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export type ToastVariant = 'success' | 'warning' | 'danger' | 'info'

export interface ToastNotification {
  id: number
  title: string
  description?: string
  variant: ToastVariant
}

export const useUiStore = defineStore('ui', () => {
  const adminSidebarOpen = ref(false)
  const adminSidebarCollapsed = ref(false)
  const pendingRequests = ref(0)
  const toasts = ref<ToastNotification[]>([])
  let nextToastId = 1

  const isGlobalLoading = computed(() => pendingRequests.value > 0)

  function toggleAdminSidebar(): void {
    adminSidebarOpen.value = !adminSidebarOpen.value
  }

  function closeAdminSidebar(): void {
    adminSidebarOpen.value = false
  }

  function toggleAdminCollapse(): void {
    adminSidebarCollapsed.value = !adminSidebarCollapsed.value
  }

  function startLoading(): void {
    pendingRequests.value += 1
  }

  function finishLoading(): void {
    pendingRequests.value = Math.max(0, pendingRequests.value - 1)
  }

  function dismissToast(id: number): void {
    toasts.value = toasts.value.filter((item) => item.id !== id)
  }

  function pushToast(payload: {
    title: string
    description?: string
    variant?: ToastVariant
    durationMs?: number
  }): void {
    const id = nextToastId++
    toasts.value.push({
      id,
      title: payload.title,
      description: payload.description,
      variant: payload.variant ?? 'info',
    })

    const duration = payload.durationMs ?? 4200
    if (duration > 0) {
      window.setTimeout(() => {
        dismissToast(id)
      }, duration)
    }
  }

  return {
    adminSidebarOpen,
    adminSidebarCollapsed,
    pendingRequests,
    isGlobalLoading,
    toasts,
    toggleAdminSidebar,
    closeAdminSidebar,
    toggleAdminCollapse,
    startLoading,
    finishLoading,
    pushToast,
    dismissToast,
  }
})
