import axios, { AxiosError } from 'axios'

export const AUTH_TOKEN_STORAGE_KEY = 'cleancommerce_auth_token'
export const AUTH_USER_STORAGE_KEY = 'cleancommerce_auth_user'

const rawBaseUrl = import.meta.env.VITE_API_BASE_URL as string | undefined
export const API_BASE_URL = (
  rawBaseUrl && rawBaseUrl.trim() !== '' ? rawBaseUrl : 'http://localhost:8080/api/v1'
).replace(/\/+$/, '')

export class ApiError extends Error {
  readonly status: number
  readonly fieldErrors: Record<string, string[]> | null
  readonly isNetworkError: boolean

  constructor(
    message: string,
    status: number,
    fieldErrors: Record<string, string[]> | null = null,
    isNetworkError = false,
  ) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.fieldErrors = fieldErrors
    this.isNetworkError = isNetworkError
  }
}

export function getStoredToken(): string | null {
  try {
    return localStorage.getItem(AUTH_TOKEN_STORAGE_KEY)
  } catch {
    return null
  }
}

export function setStoredToken(token: string | null): void {
  try {
    if (token) {
      localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, token)
    } else {
      localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY)
      localStorage.removeItem(AUTH_USER_STORAGE_KEY)
    }
  } catch {
    // Ignore storage quota or private browsing limitations
  }
}

export function generateIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  const randomHex = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1)
  return `${randomHex()}${randomHex()}-${randomHex()}-4${randomHex().slice(1)}-a${randomHex().slice(1)}-${randomHex()}${randomHex()}${randomHex()}`
}

interface ErrorResponseShape {
  message?: string
  error?: string
  errors?: Record<string, string[]>
}

export const axiosClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

axiosClient.interceptors.request.use((config) => {
  const token = getStoredToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

axiosClient.interceptors.response.use(
  (response) => response,
  (error: unknown) => {
    if (error instanceof AxiosError) {
      if (!error.response) {
        return Promise.reject(
          new ApiError(
            'Không thể kết nối tới máy chủ API (http://localhost:8080/api/v1). Vui lòng kiểm tra dịch vụ Backend đang chạy và thử tải lại.',
            0,
            null,
            true,
          ),
        )
      }

      const status = error.response.status
      if (status === 401) {
        setStoredToken(null)
      }

      const data = (
        error.response.data && typeof error.response.data === 'object'
          ? error.response.data
          : {}
      ) as ErrorResponseShape

      const fieldErrors = data.errors ?? null
      let message =
        data.message || data.error || `Yêu cầu không thành công (Mã trạng thái ${status}).`

      if (fieldErrors) {
        const firstKey = Object.keys(fieldErrors)[0]
        const firstList = firstKey ? fieldErrors[firstKey] : undefined
        if (firstList && firstList.length > 0 && firstList[0]) {
          message = firstList[0]
        }
      }

      return Promise.reject(new ApiError(message, status, fieldErrors, false))
    }

    return Promise.reject(
      new ApiError(
        error instanceof Error ? error.message : 'Đã xảy ra lỗi kết nối không xác định.',
        0,
        null,
        false,
      ),
    )
  },
)
