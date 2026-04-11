<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const config = useRuntimeConfig()
const baseURL = useApiBase()
const gatewayURL = config.public.gatewayUrl
const authStore = useAuthStore()
const { ensureSocket, joinSessio } = useSocket()

const peliId = route.query.peli
const sessioId = route.query.sessio

function mateixaSessio(id) {
  return id != null && Number(id) === Number(sessioId)
}

const MAX_PENDING_SOCKET_EVENTS = 64
const pendingSeatSocketEvents = ref([])

function patchSeient(seientId, patch) {
  const list = seients.value
  if (!list || !list.length) return
  const id = Number(seientId)
  const i = list.findIndex((s) => Number(s.id) === id)
  if (i === -1) return
  seients.value = list.map((s, idx) => (idx === i ? { ...s, ...patch } : s))
}

function enqueueSeatSocketEvent(kind, data) {
  if (pendingSeatSocketEvents.value.length < MAX_PENDING_SOCKET_EVENTS) {
    pendingSeatSocketEvents.value.push({ kind, data })
  }
}

function flushPendingSeatSocketEvents() {
  if (!seients.value || seatsLoading.value || pendingSeatSocketEvents.value.length === 0) {
    return
  }
  const batch = pendingSeatSocketEvents.value.splice(0)
  for (const ev of batch) {
    if (ev.kind === 'seleccionat') {
      processSeientSeleccionat(ev.data)
    } else if (ev.kind === 'alliberat') {
      processSeientAlliberat(ev.data)
    } else if (ev.kind === 'compra') {
      processCompraCreada(ev.data)
    }
  }
}

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: sessionsList } = await useFetch(peliId ? `/peliculas/${peliId}/sesiones` : null, {
  baseURL,
  immediate: !!peliId
})
const { data: seients, pending: seatsLoading, refresh: refreshSeats } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId,
  headers: computed(() => authStore.capcalarsAutenticacio())
})

const sessioActual = computed(() => {
  const list = sessionsList.value
  if (!list || !Array.isArray(list) || !sessioId) {
    return null
  }
  return list.find((s) => Number(s.id) === Number(sessioId)) ?? null
})

function formatSessioEtiqueta(sessio) {
  if (!sessio?.data_hora) {
    return sessio?.sala_nom || ''
  }
  const d = new Date(sessio.data_hora)
  const t = d.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' })
  const sala = sessio.sala_nom || 'Sala'
  return `${sala} · ${t}`
}

const selectedSeients = ref([])
const reservaEnCurs = ref(false)

function processSeientSeleccionat(data) {
  if (!mateixaSessio(data.sessio_id)) return
  const jo = authStore.currentUserId ? String(authStore.currentUserId) : null
  const actor = data.usuari_id != null ? String(data.usuari_id) : ''
  if (jo && actor === jo) {
    return
  }
  if (!jo && selectedSeients.value.some((s) => Number(s.id) === Number(data.seient_id))) {
    return
  }
  patchSeient(data.seient_id, { seleccionat_per_altre: true })
}

function onSeientSeleccionat(data) {
  if (seatsLoading.value || !seients.value) {
    enqueueSeatSocketEvent('seleccionat', data)
    return
  }
  processSeientSeleccionat(data)
}

function processSeientAlliberat(data) {
  if (!mateixaSessio(data.sessio_id)) return
  patchSeient(data.seient_id, { seleccionat_per_altre: false })
}

function onSeientAlliberat(data) {
  if (seatsLoading.value || !seients.value) {
    enqueueSeatSocketEvent('alliberat', data)
    return
  }
  processSeientAlliberat(data)
}

function processCompraCreada(data) {
  if (!mateixaSessio(data.sessio_id) || !data.seient_ids) return
  const ids = new Set(data.seient_ids.map((id) => Number(id)))
  for (const id of ids) {
    patchSeient(id, { reservat: true, seleccionat_per_altre: false })
  }
  selectedSeients.value = selectedSeients.value.filter((s) => !ids.has(s.id))
}

function onCompraCreada(data) {
  if (seatsLoading.value || !seients.value) {
    enqueueSeatSocketEvent('compra', data)
    return
  }
  processCompraCreada(data)
}

function rejoinSalaAlConnectar() {
  if (sessioId) {
    joinSessio(sessioId)
  }
}

async function alliberarReservesSeleccionades() {
  const seats = selectedSeients.value.slice()
  if (!sessioId || seats.length === 0) {
    return
  }
  await Promise.all(
    seats.map((seient) =>
      $fetch(`${gatewayURL}/api/reservar`, {
        method: 'POST',
        headers: authStore.capcalarsAutenticacio(),
        body: {
          sessioId,
          seientId: seient.id,
          estat: false
        }
      }).catch(() => {})
    )
  )
  selectedSeients.value = []
}

function vaAPagament(to) {
  const p = to.path || ''
  return p === '/pago' || p.startsWith('/pago/')
}

onBeforeRouteLeave(async (to) => {
  if (vaAPagament(to)) {
    return true
  }
  await alliberarReservesSeleccionades()
})

onMounted(async () => {
  await authStore.syncUsuariSiCal(baseURL)

  const socket = ensureSocket()
  if (!socket) return

  socket.on('connect', rejoinSalaAlConnectar)
  socket.on('seient-seleccionat', onSeientSeleccionat)
  socket.on('seient-alliberat', onSeientAlliberat)
  socket.on('compra-creada', onCompraCreada)

  if (sessioId) {
    joinSessio(sessioId)
  }
})

onUnmounted(() => {
  const socket = ensureSocket()
  if (!socket) return
  socket.off('connect', rejoinSalaAlConnectar)
  socket.off('seient-seleccionat', onSeientSeleccionat)
  socket.off('seient-alliberat', onSeientAlliberat)
  socket.off('compra-creada', onCompraCreada)
})

async function toggleSeient(seient) {
  if (reservaEnCurs.value) {
    return
  }
  const index = selectedSeients.value.findIndex((s) => s.id === seient.id)
  const nouEstat = index === -1

  reservaEnCurs.value = true
  try {
    await $fetch(`${gatewayURL}/api/reservar`, {
      method: 'POST',
      headers: authStore.capcalarsAutenticacio(),
      body: {
        sessioId: sessioId,
        seientId: seient.id,
        estat: nouEstat
      }
    })

    if (nouEstat) {
      if (!selectedSeients.value.some((s) => s.id === seient.id)) {
        selectedSeients.value.push(seient)
      }
    } else {
      selectedSeients.value.splice(index, 1)
    }
  } catch (e) {
    if (e.response && e.response.status === 401) {
      await authStore.logout()
      navigateTo('/login')
    } else {
      alert(e.data?.error || 'No s\'ha pogut reservar el seient')
      refreshSeats()
    }
  } finally {
    reservaEnCurs.value = false
  }
}

function getPreu(categoria) {
  if (categoria === 'VIP') return 9.7
  return 6.7
}

const totalPreu = computed(() => {
  return selectedSeients.value.reduce((sum, s) => sum + getPreu(s.categoria), 0)
})

const etiquetaTarifes = computed(() => {
  const sel = selectedSeients.value
  if (!sel.length) {
    return '—'
  }
  const vip = sel.filter((s) => s.categoria === 'VIP').length
  const norm = sel.filter((s) => s.categoria !== 'VIP').length
  const parts = []
  if (vip) {
    parts.push(`${vip} VIP`)
  }
  if (norm) {
    parts.push(`${norm} Normal`)
  }
  return parts.join(' · ')
})

const textButaquesSeleccionades = computed(() => {
  if (!selectedSeients.value.length) {
    return '—'
  }
  return selectedSeients.value.map((s) => `${s.fila}${s.numero}`).join(', ')
})

function anarAPagament() {
  const ids = selectedSeients.value.map((s) => s.id).join(',')
  navigateTo({
    path: '/pago',
    query: {
      peli: peliId,
      sessio: String(sessioId),
      seients: ids
    }
  })
}

watch(
  () => route.query.sessio,
  () => {
    selectedSeients.value = []
  }
)

watch(
  () => seients.value,
  (list) => {
    if (!list || !Array.isArray(list) || selectedSeients.value.length > 0) {
      return
    }
    const mine = list.filter((s) => s.la_meva_reserva)
    if (mine.length) {
      selectedSeients.value = mine.slice()
    }
  },
  { immediate: true }
)

watch([seients, seatsLoading], () => {
  flushPendingSeatSocketEvents()
})

/** Files A…E: la fila A (davant) queda just sota la pantalla. */
const filesPerMostrar = computed(() => {
  if (!seients.value?.length) {
    return []
  }
  const map = new Map()
  for (const s of seients.value) {
    const f = String(s.fila)
    if (!map.has(f)) {
      map.set(f, [])
    }
    map.get(f).push(s)
  }
  for (const arr of map.values()) {
    arr.sort((a, b) => a.numero - b.numero)
  }
  const filas = [...map.keys()].sort((a, b) => a.localeCompare(b, 'en', { sensitivity: 'base' }))
  return filas.map((fila) => ({ fila, seats: map.get(fila) }))
})

function meitatsFila(seats) {
  if (!seats?.length) {
    return []
  }
  const mid = Math.ceil(seats.length / 2)
  return [seats.slice(0, mid), seats.slice(mid)]
}

function esSeientSeleccionat(seient) {
  return selectedSeients.value.some((s) => s.id === seient.id)
}

function classeSeient(seient) {
  const base =
    'relative flex h-8 w-8 shrink-0 items-center justify-center border text-[8px] font-medium transition-all focus:outline-none focus-visible:ring-2 focus-visible:ring-primary'
  if (seient.reservat) {
    return `${base} cursor-not-allowed border-transparent bg-surface-container-low opacity-40`
  }
  if (seient.seleccionat_per_altre) {
    return `${base} cursor-not-allowed border-amber-700/50 bg-amber-950/70 text-amber-200/80`
  }
  if (esSeientSeleccionat(seient)) {
    return `${base} cursor-pointer border-primary bg-secondary-container text-white shadow-[0_0_15px_rgba(255,180,168,0.4)]`
  }
  const vip = seient.categoria === 'VIP'
  return `${base} cursor-pointer border-outline-variant/20 bg-surface-container-highest text-white/90 hover:border-primary ${
    vip ? 'ring-1 ring-inset ring-amber-500/25' : ''
  }`
}

function onClickSeient(seient) {
  if (seient.reservat || seient.seleccionat_per_altre) {
    return
  }
  toggleSeient(seient)
}
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-surface-container-lowest font-body text-on-surface selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main
      class="mx-auto grid max-w-[1920px] min-h-screen grid-cols-1 gap-12 px-4 pb-28 pt-32 lg:grid-cols-12 lg:gap-12 lg:px-8 lg:pb-20 xl:px-16"
    >
      <template v-if="!peliId || !sessioId">
        <div class="col-span-full flex min-h-[50vh] flex-col items-center justify-center px-4 text-center">
          <p class="font-body text-stone-400">
            Falta seleccionar pel·lícula o sessió.
          </p>
          <NuxtLink
            to="/"
            class="font-headline mt-6 text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
          >
            Tornar a la cartellera
          </NuxtLink>
        </div>
      </template>

      <template v-else-if="peli">
        <!-- Mapa de seients -->
        <section class="flex flex-col items-center lg:col-span-8">
          <div class="relative mb-16 w-full max-w-4xl">
            <div class="h-1 w-full overflow-hidden rounded-full bg-primary/20">
              <div class="h-full w-full bg-gradient-to-r from-transparent via-primary to-transparent opacity-60" />
            </div>
            <div
              class="screen-curve mt-4 flex h-32 w-full items-center justify-center bg-gradient-to-b from-primary/10 to-transparent"
            >
              <h2
                class="font-headline text-3xl uppercase tracking-[0.5em] text-primary-fixed opacity-80 md:text-5xl"
              >
                PANTALLA
              </h2>
            </div>
            <div
              class="pointer-events-none absolute left-1/2 top-0 h-64 w-[120%] -translate-x-1/2 bg-primary/5 blur-[100px]"
            />
          </div>

          <div v-if="seatsLoading" class="py-16 font-headline text-sm uppercase tracking-[0.2em] text-stone-500">
            Carregant seients…
          </div>

          <div v-else-if="seients" class="seat-grid flex w-full justify-center overflow-x-auto pb-12">
            <div class="flex flex-col gap-6">
              <div
                v-for="bloc in filesPerMostrar"
                :key="bloc.fila"
                class="flex items-center gap-4"
              >
                <span
                  class="w-8 shrink-0 font-label text-[10px] tracking-widest text-on-surface-variant"
                >{{ bloc.fila }}</span>
                <div class="flex flex-wrap items-center gap-3">
                  <template v-for="(meitat, mi) in meitatsFila(bloc.seats)" :key="`${bloc.fila}-${mi}`">
                    <div v-if="mi > 0" class="w-12 shrink-0" aria-hidden="true" />
                    <button
                      v-for="seient in meitat"
                      :key="seient.id"
                      type="button"
                      :class="classeSeient(seient)"
                      :disabled="seient.reservat || seient.seleccionat_per_altre || reservaEnCurs"
                      :aria-label="`Fila ${seient.fila}, seient ${seient.numero}`"
                      :aria-pressed="esSeientSeleccionat(seient)"
                      @click="onClickSeient(seient)"
                    >
                      <span class="sr-only">{{ seient.fila }}{{ seient.numero }}</span>
                      <span aria-hidden="true">{{ seient.numero }}</span>
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </div>

          <div v-if="seients && !seatsLoading" class="mt-8 flex flex-wrap justify-center gap-8 md:gap-12">
            <div class="flex items-center gap-3">
              <div class="h-5 w-5 border border-outline-variant/20 bg-surface-container-highest" />
              <span class="font-medium text-[10px] uppercase tracking-widest text-on-surface-variant">Disponible</span>
            </div>
            <div class="flex items-center gap-3">
              <div
                class="h-5 w-5 border border-primary bg-secondary-container shadow-[0_0_10px_rgba(255,180,168,0.3)]"
              />
              <span class="font-medium text-[10px] uppercase tracking-widest text-on-surface-variant">Seleccionat</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-5 w-5 bg-surface-container-low opacity-40" />
              <span class="font-medium text-[10px] uppercase tracking-widest text-on-surface-variant">Ocupat</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="h-5 w-5 border border-amber-700/50 bg-amber-950/70" />
              <span class="font-medium text-[10px] uppercase tracking-widest text-on-surface-variant">Altre usuari</span>
            </div>
          </div>
        </section>

        <!-- Panell reserva -->
        <aside class="h-fit lg:col-span-4 lg:sticky lg:top-32">
          <div class="crimson-glass border-l border-primary/20 p-6 md:p-8">
            <div class="mb-10 flex items-start justify-between gap-4">
              <div class="min-w-0">
                <h1 class="font-headline mb-2 text-3xl tracking-tight text-white md:text-4xl">
                  {{ peli.titol }}
                </h1>
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-primary">
                  {{ formatSessioEtiqueta(sessioActual) || 'Sessió' }}
                </p>
              </div>
              <div class="flex shrink-0 flex-col items-end">
                <span
                  class="material-symbols-outlined mb-1 text-primary"
                  style="font-variation-settings: 'FILL' 1"
                  aria-hidden="true"
                >schedule</span>
                <span class="font-mono text-lg font-black text-primary md:text-xl">
                  {{ sessioActual?.data_hora ? new Date(sessioActual.data_hora).toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' }) : '—' }}
                </span>
              </div>
            </div>

            <div class="mb-10 space-y-0">
              <div class="flex items-center justify-between border-b border-white/10 py-4">
                <span class="font-label text-xs uppercase tracking-widest text-stone-400">Butaques</span>
                <span class="font-headline text-right text-lg text-white">{{ textButaquesSeleccionades }}</span>
              </div>
              <div class="flex items-center justify-between border-b border-white/10 py-4">
                <span class="font-label text-xs uppercase tracking-widest text-stone-400">Tarifa</span>
                <span class="font-headline text-right text-lg text-white">{{ etiquetaTarifes }}</span>
              </div>
              <div class="flex items-center justify-between py-4">
                <span class="font-label text-xs uppercase tracking-widest text-stone-400">Subtotal</span>
                <span class="font-headline text-3xl text-primary">{{ totalPreu.toFixed(2) }}€</span>
              </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
              <button
                type="button"
                class="w-full bg-on-surface py-5 font-black text-xs uppercase tracking-[0.3em] text-surface transition hover:bg-primary hover:text-on-primary-fixed active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
                :disabled="selectedSeients.length === 0 || reservaEnCurs"
                @click="anarAPagament"
              >
                Confirmar reserva
              </button>
              <NuxtLink
                :to="`/sala?peli=${peliId}`"
                class="block w-full border border-white/20 py-5 text-center font-medium text-xs uppercase tracking-[0.3em] text-white transition hover:bg-white/5"
              >
                Canviar sessió
              </NuxtLink>
            </div>

            <div class="mt-10 flex items-center gap-4 border border-white/5 bg-black/40 p-4">
              <img
                :src="peli.imatge_url"
                :alt="peli.titol"
                class="h-24 w-16 object-cover"
              >
              <div class="min-w-0">
                <p class="mb-1 text-[9px] uppercase tracking-widest text-stone-500">
                  Tornar enrere
                </p>
                <NuxtLink
                  :to="`/sala?peli=${peliId}`"
                  class="text-xs font-bold uppercase tracking-wider text-white transition hover:text-primary"
                >
                  Sessions d’aquesta pel·lícula
                </NuxtLink>
              </div>
            </div>
          </div>
        </aside>
      </template>
    </main>

    <footer
      class="flex w-full flex-col items-center justify-center gap-12 border-t-0 bg-stone-950 px-8 py-20"
    >
      <div class="font-headline text-2xl font-black tracking-[0.3em] text-red-600 md:text-3xl">
        TICKET-FAST
      </div>
      <div class="flex flex-wrap justify-center gap-8 md:gap-10">
        <NuxtLink
          to="/"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Cartelera
        </NuxtLink>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Cines</span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Premium</span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Soporte</span>
      </div>
      <div class="h-px w-full max-w-4xl bg-gradient-to-r from-transparent via-stone-800 to-transparent" />
      <p class="text-center font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-700">
        © {{ new Date().getFullYear() }} TICKET-FAST. THE NOIR PREMIERE.
      </p>
    </footer>

    <div class="crimson-glass fixed bottom-0 left-0 z-50 grid w-full grid-cols-2 items-center p-4 md:hidden">
      <NuxtLink
        to="/"
        class="flex flex-col items-center gap-1"
        :class="route.path === '/' ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">movie</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Cartelera</span>
      </NuxtLink>
      <NuxtLink
        to="/mis-entrades"
        class="flex flex-col items-center gap-1"
        :class="route.path.startsWith('/mis-entrades') ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">confirmation_number</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Mis entradas</span>
      </NuxtLink>
    </div>
  </div>
</template>
