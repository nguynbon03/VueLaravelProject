import type { RouteRecordRaw } from 'vue-router'

export const adminRoutes: RouteRecordRaw[] = [
  {
    path: '/admin',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAdmin: true },
    children: [
      {
        path: '',
        name: 'admin-dashboard',
        component: () => import('../modules/admin/views/DashboardView.vue'),
        meta: {
          title: 'Dashboard Quản trị | CleanCommerce',
          breadcrumb: 'Tổng quan vận hành',
          requiresAdmin: true,
        },
      },
      {
        path: 'products',
        name: 'admin-products',
        component: () => import('../modules/admin/views/products/ProductManageView.vue'),
        meta: {
          title: 'Quản lý sản phẩm & Kho | CleanCommerce',
          breadcrumb: 'Quản lý sản phẩm & Kho',
          requiresAdmin: true,
        },
      },
      {
        path: 'products/create',
        name: 'admin-product-create',
        component: () => import('../modules/admin/views/products/ProductFormView.vue'),
        meta: {
          title: 'Tạo sản phẩm mới | CleanCommerce',
          breadcrumb: 'Khởi tạo sản phẩm mới',
          requiresAdmin: true,
        },
      },
      {
        path: 'orders',
        name: 'admin-orders',
        component: () => import('../modules/admin/views/orders/OrderManageView.vue'),
        meta: {
          title: 'Quản lý đơn hàng | CleanCommerce',
          breadcrumb: 'Quản lý đơn hàng',
          requiresAdmin: true,
        },
      },
      {
        path: 'orders/:orderNumber',
        name: 'admin-order-detail',
        component: () => import('../modules/admin/views/orders/OrderDetailView.vue'),
        meta: {
          title: 'Xử lý đơn hàng | CleanCommerce',
          breadcrumb: 'Chi tiết đơn hàng',
          requiresAdmin: true,
        },
      },
      {
        path: 'users',
        name: 'admin-users',
        component: () => import('../modules/admin/views/users/UserManageView.vue'),
        meta: {
          title: 'Quản lý người dùng | CleanCommerce',
          breadcrumb: 'Quản lý người dùng',
          requiresAdmin: true,
        },
      },
    ],
  },
]
