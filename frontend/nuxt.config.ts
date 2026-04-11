// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      htmlAttrs: {
        class: 'dark',
        lang: 'ca'
      },
      title: 'TICKET-FAST | Cartelera',
      meta: [{ name: 'viewport', content: 'width=device-width, initial-scale=1' }],
      link: [
        { rel: 'icon', type: 'image/png', href: '/favicon.png' },
        { rel: 'apple-touch-icon', href: '/favicon.png' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        {
          rel: 'preconnect',
          href: 'https://fonts.gstatic.com',
          crossorigin: ''
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Noto+Serif:wght@400;700;900&family=Inter:wght@300;400;500;700;900&display=swap'
        },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0'
        }
      ]
    }
  },
  modules: ['@pinia/nuxt', '@nuxtjs/tailwindcss'],
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