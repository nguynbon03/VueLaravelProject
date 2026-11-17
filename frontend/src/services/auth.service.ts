import type {
  AdminUserItem,
  ApiDataEnvelope,
  AuthSessionPayload,
  UserProfile,
  UserRole,
} from '../types/api.types'
import { axiosClient } from './axiosClient'

export const authService = {
  async login(payload: { email: string; password: string }): Promise<AuthSessionPayload> {
    const { data } = await axiosClient.post<ApiDataEnvelope<AuthSessionPayload>>(
      '/auth/login',
      payload,
    )
    return data.data
  },

  async register(payload: {
    name: string
    email: string
    password: string
    phone?: string
  }): Promise<AuthSessionPayload> {
    const { data } = await axiosClient.post<ApiDataEnvelope<AuthSessionPayload>>(
      '/auth/register',
      payload,
    )
    return data.data
  },

  async me(): Promise<UserProfile> {
    const { data } = await axiosClient.get<ApiDataEnvelope<UserProfile>>('/auth/me')
    return data.data
  },

  async logout(): Promise<void> {
    await axiosClient.post<{ message: string }>('/auth/logout')
  },

  async listUsers(): Promise<AdminUserItem[]> {
    const { data } = await axiosClient.get<ApiDataEnvelope<AdminUserItem[]>>('/admin/users')
    return data.data
  },

  async updateUserRole(userId: number, role: UserRole): Promise<AdminUserItem> {
    const { data } = await axiosClient.patch<ApiDataEnvelope<AdminUserItem>>(
      `/admin/users/${userId}/role`,
      { role },
    )
    return data.data
  },
}
