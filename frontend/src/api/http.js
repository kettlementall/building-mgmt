import axios from 'axios'
import { ElMessage } from 'element-plus'
import router from '@/router'

const http = axios.create({
  baseURL: '/api',
  timeout: 15000,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
})

// 請求攔截：自動帶上 token
http.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// 回應攔截：統一錯誤處理
http.interceptors.response.use(
  (res) => res.data,
  (err) => {
    const status  = err.response?.status
    const message = err.response?.data?.message

    if (status === 401) {
      localStorage.removeItem('token')
      router.push('/login')
      ElMessage.error('登入逾時，請重新登入')
    } else if (status === 422) {
      const errors = err.response?.data?.errors
      if (errors) {
        Object.values(errors).flat().forEach((msg) => ElMessage.error(msg))
      } else {
        ElMessage.error(message || '資料驗證失敗')
      }
    } else if (status === 403) {
      ElMessage.error('無權限執行此操作')
    } else {
      ElMessage.error(message || '系統發生錯誤，請稍後再試')
    }

    return Promise.reject(err)
  }
)

export default http
