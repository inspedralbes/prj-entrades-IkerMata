// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  pinia: {
    autoImports: ['defineStore', 'storeToRefs'],
  },
  // Sobreescriptible amb NUXT_PUBLIC_API_BASE i NUXT_PUBLIC_GATEWAY_URL (veure docker-compose).
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
    '/registre': { ssr: false },
    '/admin': { ssr: false }
  },
  vite: {
    // Dev darrere de Nginx: el Host és el domini públic; si no, Vite respon 403.
    server: {
      allowedHosts: true,
    },
    optimizeDeps: {
      include: ['socket.io-client']
    }
  }
})
