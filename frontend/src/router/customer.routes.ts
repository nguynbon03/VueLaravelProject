import type { RouteRecordRaw } from 'vue-router'
import CustomerLayout from '../layouts/CustomerLayout.vue'
import HomeView from '../modules/customer/views/HomeView.vue'

export const customerRoutes: RouteRecordRaw[] = [
  {
    path: '/',
    component: CustomerLayout,
    children: [
      {
        path: '',
        name: 'home',
        component: HomeView,
        meta: { title: 'Trang chủ | CleanCommerce' },
      },
      {
        path: 'products',
        name: 'product-list',
        component: () => import('../modules/customer/views/ProductListView.vue'),
        meta: { title: 'Danh mục sản phẩm | CleanCommerce' },
      },
      {
        path: 'products/:slug',
        name: 'product-detail',
        component: () => import('../modules/customer/views/ProductDetailView.vue'),
        meta: { title: 'Chi tiết sản phẩm | CleanCommerce' },
      },
      {
        path: 'cart',
        name: 'cart',
        component: () => import('../modules/customer/views/CartView.vue'),
        meta: { title: 'Giỏ hàng | CleanCommerce' },
      },
      {
        path: 'checkout',
        name: 'checkout',
        component: () => import('../modules/customer/views/CheckoutView.vue'),
        meta: { title: 'Thanh toán đơn hàng | CleanCommerce' },
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('../modules/customer/views/ProfileView.vue'),
        meta: { title: 'Hồ sơ & Đơn hàng | CleanCommerce' },
      },
    ],
  },
  {
    path: '/login',
    redirect: (to) => ({ path: '/auth/login', query: to.query }),
  },
  {
    path: '/register',
    redirect: '/auth/register',
  },
  {
    path: '/orders',
    redirect: '/profile',
  },
  {
    path: '/orders/:orderNumber',
    redirect: (to) => ({
      path: '/profile',
      query: { order: String(to.params.orderNumber ?? '') },
    }),
  },
]
