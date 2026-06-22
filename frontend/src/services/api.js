import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  xsrfCookieName: 'XSRF-TOKEN',
  xsrfHeaderName: 'X-XSRF-TOKEN',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

let csrfInitialized = false

export async function ensureCsrfCookie() {
  await api.get('/sanctum/csrf-cookie')
  csrfInitialized = true
}

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const original = error.config

    if (error.response?.status === 419 && original && !original._retry) {
      original._retry = true
      csrfInitialized = false
      await ensureCsrfCookie()
      return api.request(original)
    }

    return Promise.reject(error)
  },
)

export default api
