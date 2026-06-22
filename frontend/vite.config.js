import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

const proxyTarget = process.env.VITE_PROXY_TARGET || 'http://localhost:8080'

const proxyConfig = {
  target: proxyTarget,
  changeOrigin: true,
  secure: false,
  cookieDomainRewrite: 'localhost',
}

export default defineConfig({
  plugins: [vue()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      '/api': proxyConfig,
      '/sanctum': proxyConfig,
    },
  },
})
