import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authService } from '../services/auth.service'
import {
  AUTH_USER_STORAGE_KEY,
  ApiError,
  getStoredToken,
  setStoredToken,
} from '../services/axiosClient'
import type { UserProfile } from '../types/api.types'

function readCachedUser(): UserProfile | null {
  try {
    const raw = localStorage.getItem(AUTH_USER_STORAGE_KEY)
    if (!raw) return null
    return JSON.parse(raw) as UserProfile
  } catch {
    return null
  }
}

function writeCachedUser(user: UserProfile | null): void {
  try {
    if (user) {
      localStorage.setItem(AUTH_USER_STORAGE_KEY, JSON.stringify(user))
    } else {
      localStorage.removeItem(AUTH_USER_STORAGE_KEY)
    }
  } catch {
    // Ignore storage quota errors
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

  if (typeof window !== 'undefined') {
    window.addEventListener('cleancommerce:unauthorized', () => {
      token.value = null
      user.value = null
    })
  }

  async function login(credentials: { email: string; password: string }): Promise<UserProfile> {
    loading.value = true
    try {
      const session = await authService.login(credentials)
      setSession(session.token, session.user)
      return session.user
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
      const session = await authService.register(payload)
      setSession(session.token, session.user)
      return session.user
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
      const profile = await authService.me()
      user.value = profile
      writeCachedUser(profile)
      return profile
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
        await authService.logout().catch(() => null)
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
