import { storeToRefs } from 'pinia'
import { useAuthStore } from '../stores/auth.store'

export function useAuth() {
  const authStore = useAuthStore()
  const { token, user, loading, initialized, isAuthenticated, isAdmin } = storeToRefs(authStore)

  return {
    token,
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdmin,
    login: authStore.login,
    register: authStore.register,
    fetchMe: authStore.fetchMe,
    logout: authStore.logout,
  }
}
