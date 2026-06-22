import { defineStore } from 'pinia'
import api, { ensureCsrfCookie } from '../services/api'
import { unwrapResource } from '../utils/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => Boolean(state.user),
    userName: (state) => state.user?.name ?? '',
  },

  actions: {
    async fetchUser() {
      try {
        const { data } = await api.get('/api/user')
        this.user = unwrapResource(data)
      } catch {
        this.user = null
      }
    },

    async register(payload) {
      this.loading = true
      this.error = null

      try {
        await ensureCsrfCookie()
        const { data } = await api.post('/api/register', payload)
        this.user = data.user
        return data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao cadastrar.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async login(payload) {
      this.loading = true
      this.error = null

      try {
        await ensureCsrfCookie()
        const { data } = await api.post('/api/login', payload)
        this.user = data.user
        return data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao entrar.'
        throw error
      } finally {
        this.loading = false
      }
    },

    async logout() {
      await ensureCsrfCookie()
      await api.post('/api/logout')
      this.user = null
    },
  },
})
