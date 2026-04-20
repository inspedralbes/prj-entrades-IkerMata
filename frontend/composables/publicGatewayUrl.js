import { computed } from 'vue'
import {
  normalizePublicHttpUrl,
  upgradeHttpToHttpsIfPageHttps
} from '~/composables/useApiBase'

const DEFAULT_GATEWAY = 'http://localhost:3003'

/**
 * `auto` / `same` / `same-origin`: mateix origen que la pàgina (Nginx ha de fer proxy de /socket.io al gateway).
 * Buit en navegador no local: també es tracta com mateix origen.
 *
 * Si el build encara apunta a localhost però el navegador és en un altre host (producció),
 * substitueix el host per poder arribar al gateway al mateix servidor (p. ex. :3003).
 */
export function resolvePublicGatewayUrl(raw) {
  let u = typeof raw === 'string' ? raw.trim() : ''
  const sameOriginToken =
    u === '' || u === 'auto' || u === 'same' || u === 'same-origin'
  if (sameOriginToken) {
    if (import.meta.server || typeof window === 'undefined') {
      u = DEFAULT_GATEWAY
    } else {
      const h = window.location.hostname
      const local = h === 'localhost' || h === '127.0.0.1'
      u = local ? DEFAULT_GATEWAY : window.location.origin
    }
  } else if (!u.length) {
    u = DEFAULT_GATEWAY
  }
  u = normalizePublicHttpUrl(u)
  if (import.meta.server || typeof window === 'undefined') {
    return u
  }
  try {
    const parsed = new URL(u)
    const hostLocal =
      parsed.hostname === 'localhost' || parsed.hostname === '127.0.0.1'
    const browserHost = window.location.hostname
    const browserLocal =
      browserHost === 'localhost' || browserHost === '127.0.0.1'
    if (hostLocal && !browserLocal) {
      const port = parsed.port || '3003'
      u = `${window.location.protocol}//${browserHost}:${port}`
    }
  } catch (_) {
    /* ignore */
  }
  u = upgradeHttpToHttpsIfPageHttps(u)
  return u
}

export function usePublicGatewayUrl() {
  const config = useRuntimeConfig()
  return computed(() => resolvePublicGatewayUrl(config.public.gatewayUrl))
}
