import { defineStore } from 'pinia'
import { ref } from 'vue'
import { auth as authApi } from '@/api'

export const useAuthStore = defineStore('auth', () => {
  const user  = ref(null)
  const token = ref(localStorage.getItem('token') || null)

  const isLoggedIn = () => !!token.value

  async function login(credentials) {
    const res = await authApi.login(credentials)
    token.value = res.token
    user.value  = res.user
    localStorage.setItem('token', res.token)
  }

  async function logout() {
    await authApi.logout()
    token.value = null
    user.value  = null
    localStorage.removeItem('token')
  }

  async function fetchMe() {
    if (!token.value) return
    user.value = await authApi.me()
  }

  return { user, token, isLoggedIn, login, logout, fetchMe }
})
