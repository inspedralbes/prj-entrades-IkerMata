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
 * Les pàgines que fan fetch han usar `routeRules` amb `ssr: false` si no hi ha proxy intern,
 * per evitar desquadraments d'hidratació entre servidor i client.
 */
export function useApiBase() {
  const config = useRuntimeConfig()
  return normalizePublicHttpUrl(config.public.apiBase)
}
