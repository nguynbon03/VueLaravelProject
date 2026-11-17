import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { ApiError, apiClient, getStoredToken, setStoredToken } from '../../shared/api/client'
import type { ApiDataEnvelope, AuthSessionPayload, UserProfile } from '../../shared/api/types'

const USER_CACHE_KEY = 'cleancommerce_auth_user'

function readCachedUser(): UserProfile | null {
  try {
    const raw = localStorage.getItem(USER_CACHE_KEY)
    if (!raw) return null
    return JSON.parse(raw) as UserProfile
  } catch {
    return null
  }
}

function writeCachedUser(user: UserProfile | null): void {
  try {
    if (user) {
      localStorage.setItem(USER_CACHE_KEY, JSON.stringify(user))
    } else {
      localStorage.removeItem(USER_CACHE_KEY)
    }
  } catch {
    // Ignore storage errors
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(getStoredToken())
  const user = ref<UserProfile | null>(token.value ? readCachedUser() : null)
  const loading = ref(false)
  const initialized = ref(false)

  const isAuthenticated = computed(() => Boolean(token.value && user.value))
  const isAdmin = computed(() => user.value?.role === 'admin')

  function setSession(nextToken: string | null, nextUser: UserProfile | null): void {
    token.value = nextToken
    user.value = nextUser
    setStoredToken(nextToken)
    writeCachedUser(nextUser)
  }

  async function login(credentials: { email: string; password: string }): Promise<UserProfile> {
    loading.value = true
    try {
      const res = await apiClient.post<ApiDataEnvelope<AuthSessionPayload>>('/auth/login', credentials)
      setSession(res.data.token, res.data.user)
      return res.data.user
    } finally {
      loading.value = false
    }
  }

  async function register(payload: {
    name: string
    email: string
    password: string
    phone?: string
  }): Promise<UserProfile> {
    loading.value = true
    try {
      const res = await apiClient.post<ApiDataEnvelope<AuthSessionPayload>>('/auth/register', payload)
      setSession(res.data.token, res.data.user)
      return res.data.user
    } finally {
      loading.value = false
    }
  }

  async function fetchMe(): Promise<UserProfile | null> {
    if (!token.value) {
      initialized.value = true
      return null
    }
    loading.value = true
    try {
      const res = await apiClient.get<ApiDataEnvelope<UserProfile>>('/auth/me')
      user.value = res.data
      writeCachedUser(res.data)
      return res.data
    } catch (err) {
      if (err instanceof ApiError && err.status === 401) {
        setSession(null, null)
      }
      return user.value
    } finally {
      loading.value = false
      initialized.value = true
    }
  }

  async function logout(): Promise<void> {
    loading.value = true
    try {
      if (token.value) {
        await apiClient.post<{ message: string }>('/auth/logout').catch(() => null)
      }
    } finally {
      setSession(null, null)
      loading.value = false
    }
  }

  return {
    token,
    user,
    loading,
    initialized,
    isAuthenticated,
    isAdmin,
    login,
    register,
    fetchMe,
    logout,
  }
})
