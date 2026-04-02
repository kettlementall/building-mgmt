import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/LoginView.vue'),
    meta: { guest: true },
  },
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'Dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { title: '總覽' },
      },
      {
        path: 'units',
        name: 'Units',
        component: () => import('@/views/units/UnitListView.vue'),
        meta: { title: '戶別管理' },
      },
      {
        path: 'residents',
        name: 'Residents',
        component: () => import('@/views/residents/ResidentListView.vue'),
        meta: { title: '住戶管理' },
      },
      {
        path: 'fee-rules',
        name: 'FeeRules',
        component: () => import('@/views/fee-rules/FeeRuleListView.vue'),
        meta: { title: '管理費規則' },
      },
      {
        path: 'bills',
        name: 'Bills',
        component: () => import('@/views/bills/BillListView.vue'),
        meta: { title: '帳單管理' },
      },
      {
        path: 'bills/:id',
        name: 'BillDetail',
        component: () => import('@/views/bills/BillDetailView.vue'),
        meta: { title: '帳單詳情' },
      },
      {
        path: 'expenses',
        name: 'Expenses',
        component: () => import('@/views/expenses/ExpenseListView.vue'),
        meta: { title: '支出管理' },
      },
      {
        path: 'reports',
        name: 'Reports',
        component: () => import('@/views/reports/ReportView.vue'),
        meta: { title: '財務報表' },
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const authStore = useAuthStore()

  if (to.meta.requiresAuth && !authStore.isLoggedIn()) {
    return { name: 'Login' }
  }

  if (to.meta.guest && authStore.isLoggedIn()) {
    return { name: 'Dashboard' }
  }

  // 若已登入但尚未取得使用者資訊
  if (authStore.isLoggedIn() && !authStore.user) {
    await authStore.fetchMe()
  }
})

export default router
