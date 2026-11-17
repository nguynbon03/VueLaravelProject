const AUTH_TOKEN_STORAGE_KEY = 'cleancommerce_auth_token'

const rawBaseUrl = import.meta.env.VITE_API_BASE_URL as string | undefined
export const API_BASE_URL = (rawBaseUrl && rawBaseUrl.trim() !== ''
  ? rawBaseUrl
  : 'http://localhost:8080/api/v1'
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
    }
  } catch {
    // Ignore storage quota or private mode restrictions
  }
}

export function generateIdempotencyKey(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID()
  }
  const randomHex = () => Math.floor((1 + Math.random()) * 0x10000).toString(16).substring(1)
  return `${randomHex()}${randomHex()}-${randomHex()}-4${randomHex().slice(1)}-a${randomHex().slice(1)}-${randomHex()}${randomHex()}${randomHex()}`
}

interface RequestOptions {
  headers?: Record<string, string>
  params?: Record<string, string | number | undefined | null>
}

function buildUrl(path: string, params?: Record<string, string | number | undefined | null>): string {
  const normalizedPath = path.startsWith('/') ? path : `/${path}`
  const url = new URL(`${API_BASE_URL}${normalizedPath}`)
  if (params) {
    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && String(value).trim() !== '') {
        url.searchParams.set(key, String(value))
      }
    }
  }
  return url.toString()
}

interface ErrorResponseShape {
  message?: string
  error?: string
  errors?: Record<string, string[]>
}

async function request<T>(
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  path: string,
  body?: unknown,
  options?: RequestOptions,
): Promise<T> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    ...(options?.headers ?? {}),
  }

  const token = getStoredToken()
  if (token) {
    headers.Authorization = `Bearer ${token}`
  }

  if (body !== undefined) {
    headers['Content-Type'] = 'application/json'
  }

  let response: Response
  try {
    response = await fetch(buildUrl(path, options?.params), {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    })
  } catch {
    throw new ApiError(
      'Không thể kết nối tới máy chủ API (http://localhost:8080/api/v1). Vui lòng kiểm tra dịch vụ Backend đang chạy và thử tải lại.',
      0,
      null,
      true,
    )
  }

  const contentType = response.headers.get('content-type') ?? ''
  const isJson = contentType.includes('application/json')
  const parsed: unknown = isJson ? await response.json().catch(() => null) : null

  if (!response.ok) {
    if (response.status >= 500) {
      // Never surface raw server internals (SQL, hosts, stack traces) to end users.
      throw new ApiError('Hệ thống đang gặp sự cố, vui lòng thử lại sau.', response.status, null, false)
    }

    const errObj = (parsed && typeof parsed === 'object' ? parsed : {}) as ErrorResponseShape
    const fieldErrors = errObj.errors ?? null
    let message = errObj.message || errObj.error || `Yêu cầu không thành công (Mã ${response.status}).`

    if (fieldErrors) {
      const firstKey = Object.keys(fieldErrors)[0]
      const firstList = firstKey ? fieldErrors[firstKey] : undefined
      if (firstList && firstList.length > 0 && firstList[0]) {
        message = firstList[0]
      }
    }

    throw new ApiError(message, response.status, fieldErrors, false)
  }

  return parsed as T
}

export const apiClient = {
  get<T>(path: string, options?: RequestOptions): Promise<T> {
    return request<T>('GET', path, undefined, options)
  },
  post<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return request<T>('POST', path, body, options)
  },
  patch<T>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return request<T>('PATCH', path, body, options)
  },
}
