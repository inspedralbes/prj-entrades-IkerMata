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
      title: 'TICKET-FAST | Cartellera',
      meta: [{ name: 'viewport', content: 'width=device-width, initial-scale=1' }],
      link: [
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
    optimizeDeps: {
      include: ['socket.io-client']
    }
  }
})