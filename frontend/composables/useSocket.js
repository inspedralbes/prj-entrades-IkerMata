import { io } from 'socket.io-client'

export function useSocket() {
    const config = useRuntimeConfig()
    const socket = io(config.public.gatewayUrl)

    function joinSessio(sessioId) {
        socket.emit('unirse-sessio', sessioId)
    }

    function joinPelicula(peliculaId) {
        socket.emit('unirse-pelicula', peliculaId)
    }

    function onAforoActualitzat(callback) {
        socket.on('aforo-actualitzat', callback)
    }

    function onCompraCreada(callback) {
        socket.on('compra-creada', callback)
    }

    return {
        socket,
        joinSessio,
        joinPelicula,
        onAforoActualitzat,
        onCompraCreada
    }
}
