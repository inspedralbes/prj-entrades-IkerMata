import { computed } from 'vue'

/**
 * Evita URLs tipus http://http://... si .env duplica el prefix (error comú en desplegament).
 */
export function normalizePublicHttpUrl(url) {
  if (!url || typeof url !== 'string') {
    return url
  }
  let u = url.trim()
  u = u.replace(/^http:\/\/http:\/\//i, 'http://')
  u = u.replace(/^http:\/\/http\/\//i, 'http://')
  u = u.replace(/^https:\/\/https:\/\//i, 'https://')
  return u
}

/**
 * Base URL de l'API Laravel (sempre la pública del navegador).
 * Retorna un Computed perquè `config.public.apiBase` (NUXT_PUBLIC_API_BASE) no quedi buit/undefined
 * en alguns entorns (Docker, HMR) i per poder passar-lo a useFetch com a opció reactiva.
 * Les pàgines que fan fetch han usar `routeRules` amb `ssr: false` si no hi ha proxy intern.
 */
const DEFAULT_API_BASE = 'http://localhost:8001/api'

export function useApiBase() {
  const config = useRuntimeConfig()
  return computed(() => {
    const u = config.public.apiBase
    const s = typeof u === 'string' ? u.trim() : ''
    const base = s.length > 0 ? s : DEFAULT_API_BASE
    return normalizePublicHttpUrl(base.replace(/\/$/, ''))
  })
}
