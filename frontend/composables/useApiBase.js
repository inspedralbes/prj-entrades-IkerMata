/**
 * Base URL de l'API Laravel (sempre la pública del navegador).
 * Les pàgines que fan fetch han usar `routeRules` amb `ssr: false` si no hi ha proxy intern,
 * per evitar desquadraments d'hidratació entre servidor i client.
 */
export function useApiBase() {
  const config = useRuntimeConfig()
  return config.public.apiBase
}
