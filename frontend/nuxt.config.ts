// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  pinia: {
    autoImports: ['defineStore', 'storeToRefs'],
  },
  runtimeConfig: {
    public: {
      apiBase: 'http://localhost:8001/api',
      gatewayUrl: 'http://localhost:3003'
    }
  },
  routeRules: {
    '/': { ssr: false },
    '/sala': { ssr: false },
    '/butaques': { ssr: false },
    '/mis-entrades': { ssr: false },
    '/pago': { ssr: false },
    '/login': { ssr: false },
    '/registre': { ssr: false }
  },
  vite: {
    optimizeDeps: {
      include: ['socket.io-client']
    }
  }
})
