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
 * Si la pàgina és HTTPS i l'URL és HTTP amb el mateix hostname que el navegador,
 * promou a https (evita bloqueig de contingut mixt quan Nginx fa TLS i fa proxy a HTTP intern).
 */
export function upgradeHttpToHttpsIfPageHttps(url) {
  if (!url || typeof url !== 'string') {
    return url
  }
  if (import.meta.server || typeof window === 'undefined') {
    return url
  }
  if (window.location.protocol !== 'https:') {
    return url
  }
  try {
    const p = new URL(url)
    if (p.protocol !== 'http:') {
      return url
    }
    if (p.hostname !== window.location.hostname) {
      return url
    }
    const port = p.port ? `:${p.port}` : ''
    return `https://${p.hostname}${port}${p.pathname}${p.search}${p.hash}`
  } catch {
    return url
  }
}

/**
 * Base URL de l'API Laravel (sempre la pública del navegador).
 * Retorna un Computed perquè `config.public.apiBase` (NUXT_PUBLIC_API_BASE) no quedi buit/undefined
 * en alguns entorns (Docker, HMR) i per poder passar-lo a useFetch com a opció reactiva.
 *
 * - Ruta relativa `NUXT_PUBLIC_API_BASE=/api`: mateix origen que la pàgina (recomanat amb HTTPS + proxy Nginx).
 * - URL absoluta: si la pàgina és HTTPS i comparteix hostname amb l'API HTTP, s'intenta passar a HTTPS.
 */
const DEFAULT_API_BASE = 'http://localhost:8001/api'

export function useApiBase() {
  const config = useRuntimeConfig()
  const requestURL = useRequestURL()
  return computed(() => {
    const raw = config.public.apiBase
    const s = typeof raw === 'string' ? raw.trim() : ''
    if (s.startsWith('/')) {
      const origin =
        import.meta.client && typeof window !== 'undefined'
          ? window.location.origin
          : requestURL.origin
      return normalizePublicHttpUrl(`${origin}${s}`.replace(/\/$/, ''))
    }
    const base = s.length > 0 ? s : DEFAULT_API_BASE
    let out = normalizePublicHttpUrl(base.replace(/\/$/, ''))
    out = upgradeHttpToHttpsIfPageHttps(out)
    return out
  })
}
