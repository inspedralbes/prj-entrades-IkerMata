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

  /**
   * Reserva temporal via Socket.IO amb ack (Laravel com a autoritat al gateway).
   * Rebutja amb `cause` 'no_socket' | 'timeout_reserva' | 'empty_ack' per permetre fallback HTTP.
   */
  function reservarTemporal({ sessioId, seientId, estat, token }) {
    const s = ensureSocket()
    if (!s?.connected) {
      return Promise.reject(Object.assign(new Error('Socket no connectat'), { cause: 'no_socket' }))
    }
    return new Promise((resolve, reject) => {
      const t = setTimeout(() => {
        reject(Object.assign(new Error('Timeout reserva'), { cause: 'timeout_reserva' }))
      }, 20000)
      s.emit(
        'reserva-temporal',
        {
          sessioId,
          seientId,
          estat,
          token: token ?? null
        },
        (res) => {
          clearTimeout(t)
          if (res == null) {
            reject(Object.assign(new Error('Resposta buida'), { cause: 'empty_ack' }))
            return
          }
          if (res.ok) {
            resolve(res.data)
            return
          }
          const err = new Error('Reserva rebutjada')
          err.statusCode = res.status
          err.data = res.data
          reject(err)
        }
      )
    })
  }

  return {
    socket,
    ensureSocket,
    joinSessio,
    joinPelicula,
    onAforoActualitzat,
    onCompraCreada,
    onCatalogActualitzat,
    reservarTemporal
  }
}
