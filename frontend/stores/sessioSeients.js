import { defineStore } from 'pinia'

const MAX_PENDING_SOCKET_EVENTS = 64

/**
 * Estat global de la sessió de butaques: seients, selecció, expiracions, temporitzador i connexió Socket.
 * Centralitza el que el mòdul Client exigeix a Pinia (no només props/ref locals).
 */
export const useSessioSeientsStore = defineStore('sessioSeients', () => {
  const llistaSeients = ref([])
  const selectedSeients = ref([])
  const expiresBySeientId = ref({})
  const reservaEnCurs = ref(false)
  const segonsRestantsReserva = ref(0)
  /** Fins que no hi ha socket al client, es assumeix OK per no mostrar avís a la primera pintura. */
  const socketConnected = ref(true)
  const pendingSeatSocketEvents = ref([])

  const lastSessioKey = ref('')

  const millorExpiracioIso = computed(() => {
    const vals = Object.values(expiresBySeientId.value).filter(Boolean)
    if (!vals.length) {
      return null
    }
    return vals.reduce((a, b) => (new Date(a) < new Date(b) ? a : b))
  })

  const tempsReservaFormat = computed(() => {
    const t = segonsRestantsReserva.value
    const m = Math.floor(t / 60)
    const s = t % 60
    return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  })

  function setSocketConnected(val) {
    socketConnected.value = !!val
  }

  function initForSessio(peliId, sessioId) {
    const key = `${peliId ?? ''}_${sessioId ?? ''}`
    if (lastSessioKey.value !== key) {
      lastSessioKey.value = key
      selectedSeients.value = []
      pendingSeatSocketEvents.value = []
      expiresBySeientId.value = {}
      segonsRestantsReserva.value = 0
    }
  }

  function resetEnSortirDeSessio() {
    llistaSeients.value = []
    selectedSeients.value = []
    expiresBySeientId.value = {}
    reservaEnCurs.value = false
    segonsRestantsReserva.value = 0
    pendingSeatSocketEvents.value = []
    lastSessioKey.value = ''
  }

  function buidaSeleccio() {
    selectedSeients.value = []
  }

  function patchSeient(seientId, patch) {
    const list = llistaSeients.value
    if (!list || !list.length) {
      return
    }
    const id = Number(seientId)
    const i = list.findIndex((s) => Number(s.id) === id)
    if (i === -1) {
      return
    }
    llistaSeients.value = list.map((s, idx) => (idx === i ? { ...s, ...patch } : s))
  }

  function syncExpiresFromLlista(list) {
    if (!list || !Array.isArray(list)) {
      return
    }
    const m = {}
    for (const s of list) {
      if (s.la_meva_reserva && s.meva_expiracio_iso) {
        m[s.id] = s.meva_expiracio_iso
      }
    }
    expiresBySeientId.value = m
  }

  /** Sincronitza la llista sencera des del servidor (useFetch / refresh). */
  function aplicaLlistaDesDelServidor(list) {
    if (!list || !Array.isArray(list)) {
      llistaSeients.value = []
      return
    }
    llistaSeients.value = list.map((s) => ({ ...s }))
    syncExpiresFromLlista(llistaSeients.value)
  }

  function actualitzaSegonsReserva() {
    const iso = millorExpiracioIso.value
    if (!iso) {
      segonsRestantsReserva.value = 0
      return
    }
    const ms = new Date(iso).getTime() - Date.now()
    segonsRestantsReserva.value = Math.max(0, Math.floor(ms / 1000))
  }

  function enqueueSeatSocketEvent(kind, data) {
    if (pendingSeatSocketEvents.value.length < MAX_PENDING_SOCKET_EVENTS) {
      pendingSeatSocketEvents.value.push({ kind, data })
    }
  }

  function flushPendingSeatSocketEvents(seatsLoading, processSeleccionat, processAlliberat, processCompra) {
    if (seatsLoading || !llistaSeients.value.length || pendingSeatSocketEvents.value.length === 0) {
      return
    }
    const batch = pendingSeatSocketEvents.value.splice(0)
    for (const ev of batch) {
      if (ev.kind === 'seleccionat') {
        processSeleccionat(ev.data)
      } else if (ev.kind === 'alliberat') {
        processAlliberat(ev.data)
      } else if (ev.kind === 'compra') {
        processCompra(ev.data)
      }
    }
  }

  function processCompraCreadaLocal(data, mateixaSessioFn) {
    if (!mateixaSessioFn(data.sessio_id) || !data.seient_ids) {
      return
    }
    const ids = new Set(data.seient_ids.map((id) => Number(id)))
    for (const id of ids) {
      patchSeient(id, { reservat: true, seleccionat_per_altre: false })
    }
    selectedSeients.value = selectedSeients.value.filter((s) => !ids.has(s.id))
  }

  function afegirSeleccionat(seient) {
    if (!selectedSeients.value.some((s) => s.id === seient.id)) {
      selectedSeients.value.push(seient)
    }
  }

  function treureSeleccionatPerId(seientId) {
    const idx = selectedSeients.value.findIndex((s) => s.id === seientId)
    if (idx !== -1) {
      selectedSeients.value.splice(idx, 1)
    }
  }

  function setReservaEnCurs(val) {
    reservaEnCurs.value = val
  }

  function setSelectedFromHydration(list) {
    if (!list || !Array.isArray(list) || selectedSeients.value.length > 0) {
      return
    }
    const mine = list.filter((s) => s.la_meva_reserva)
    if (mine.length) {
      selectedSeients.value = mine.slice()
    }
  }

  return {
    llistaSeients,
    selectedSeients,
    expiresBySeientId,
    reservaEnCurs,
    segonsRestantsReserva,
    socketConnected,
    pendingSeatSocketEvents,
    millorExpiracioIso,
    tempsReservaFormat,
    lastSessioKey,
    setSocketConnected,
    initForSessio,
    resetEnSortirDeSessio,
    buidaSeleccio,
    patchSeient,
    syncExpiresFromLlista,
    aplicaLlistaDesDelServidor,
    actualitzaSegonsReserva,
    enqueueSeatSocketEvent,
    flushPendingSeatSocketEvents,
    processCompraCreadaLocal,
    afegirSeleccionat,
    treureSeleccionatPerId,
    setReservaEnCurs,
    setSelectedFromHydration
  }
})
