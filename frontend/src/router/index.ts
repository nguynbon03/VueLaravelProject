import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../modules/auth/authStore'
import CatalogView from '../views/CatalogView.vue'

export const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  scrollBehavior(_to, _from, savedPosition) {
    if (savedPosition) return savedPosition
    return { top: 0 }
  },
  routes: [
    {
      path: '/',
      name: 'catalog',
      component: CatalogView,
      meta: { title: 'Cửa hàng | CleanCommerce' },
    },
    {
      path: '/products/:slug',
      name: 'product-detail',
      component: () => import('../views/ProductDetailView.vue'),
      meta: { title: 'Chi tiết sản phẩm | CleanCommerce' },
    },
    {
      path: '/checkout',
      name: 'checkout',
      component: () => import('../views/CheckoutView.vue'),
      meta: { title: 'Giỏ hàng & Thanh toán | CleanCommerce' },
    },
    {
      path: '/orders',
      name: 'orders',
      component: () => import('../views/OrdersView.vue'),
      meta: { title: 'Theo dõi đơn hàng | CleanCommerce' },
    },
    {
      path: '/orders/:orderNumber',
      name: 'order-detail',
      component: () => import('../views/OrdersView.vue'),
      meta: { title: 'Chi tiết đơn hàng | CleanCommerce' },
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/AuthView.vue'),
      meta: { title: 'Đăng nhập & Đăng ký | CleanCommerce' },
    },
    {
      path: '/admin',
      name: 'admin',
      component: () => import('../views/AdminDashboardView.vue'),
      meta: { title: 'Quản trị vận hành | CleanCommerce', requiresAdmin: true },
    },
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

  if (to.meta.requiresAdmin && !authStore.isAdmin) {
    return {
      path: '/login',
      query: { redirect: to.fullPath },
    }
  }

  return true
})
