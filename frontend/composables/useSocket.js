import { io } from 'socket.io-client'

/**
 * Una sola connexió Socket.io compartida (evita múltiples websockets per pàgina).
 */
export function useSocket() {
  const config = useRuntimeConfig()
  const socketRef = useState('socket-io-singleton', () => null)

  function ensureSocket() {
    if (import.meta.server) {
      return null
    }
    if (!socketRef.value) {
      socketRef.value = io(config.public.gatewayUrl)
    }
    return socketRef.value
  }

  const socket = computed(() => ensureSocket())

  function joinSessio(sessioId) {
    const s = ensureSocket()
    if (s) {
      s.emit('unirse-sessio', String(sessioId))
    }
  }

  function joinPelicula(peliculaId) {
    const s = ensureSocket()
    if (s) {
      s.emit('unirse-pelicula', String(peliculaId))
    }
  }

  /** Retorna una funció per fer `off` del mateix callback (evita fugues en canviar de pàgina). */
  function onAforoActualitzat(callback) {
    const s = ensureSocket()
    if (s) {
      s.on('aforo-actualitzat', callback)
      return () => s.off('aforo-actualitzat', callback)
    }
    return () => {}
  }

  function onCompraCreada(callback) {
    const s = ensureSocket()
    if (s) {
      s.on('compra-creada', callback)
      return () => s.off('compra-creada', callback)
    }
    return () => {}
  }

  /** Cartellera / admin: canvis a pel·lícules o sessions (Redis → gateway → tots els clients). */
  function onCatalogActualitzat(callback) {
    const s = ensureSocket()
    if (s) {
      s.on('catalog-actualitzat', callback)
      return () => s.off('catalog-actualitzat', callback)
    }
    return () => {}
  }

  return {
    socket,
    ensureSocket,
    joinSessio,
    joinPelicula,
    onAforoActualitzat,
    onCompraCreada,
    onCatalogActualitzat
  }
}
