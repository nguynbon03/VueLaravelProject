import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '../stores/auth.store'
import { adminRoutes } from './admin.routes'
import { customerRoutes } from './customer.routes'

const authRoutes: RouteRecordRaw[] = [
  {
    path: '/auth',
    component: () => import('../layouts/AuthLayout.vue'),
    redirect: '/auth/login',
    children: [
      {
        path: 'login',
        name: 'auth-login',
        component: () => import('../modules/auth/views/LoginView.vue'),
        meta: { title: 'Đăng nhập | CleanCommerce' },
      },
      {
        path: 'register',
        name: 'auth-register',
        component: () => import('../modules/auth/views/RegisterView.vue'),
        meta: { title: 'Đăng ký tài khoản | CleanCommerce' },
      },
    ],
  },
]

export const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
  routes: [
    ...customerRoutes,
    ...authRoutes,
    ...adminRoutes,
    {
      path: '/:pathMatch(.*)*',
      redirect: '/',
    },
  ],
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (!authStore.initialized && authStore.token) {
    await authStore.fetchMe()
  }

  if (typeof to.meta.title === 'string') {
    document.title = to.meta.title
  }

  const requiresAdmin = to.matched.some((record) => Boolean(record.meta.requiresAdmin))
  const requiresAuth = to.matched.some((record) => Boolean(record.meta.requiresAuth))

  if (requiresAdmin && !authStore.isAdmin) {
    return {
      path: '/auth/login',
      query: { redirect: to.fullPath },
    }
  }

  if (requiresAuth && !authStore.isAuthenticated) {
    return {
      path: '/auth/login',
      query: { redirect: to.fullPath },
    }
  }

  return true
})
