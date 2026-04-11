<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()
const { joinPelicula, joinSessio, onAforoActualitzat, onCatalogActualitzat, ensureSocket } = useSocket()

const peliId = route.query.peli

const { data: peli, pending: pendingPeli, error: errorPeli, refresh: refreshPeli } = await useFetch(
  peliId ? `/peliculas/${peliId}` : null,
  { baseURL, immediate: !!peliId }
)
const { data: sessionsData, refresh: refreshSessions } = await useFetch(peliId ? `/peliculas/${peliId}/sesiones` : null, {
  baseURL,
  immediate: !!peliId
})

/** Còpia reactiva (shallowRef): mutar objectes dins useFetch sovint no redibuixa la vista. */
const sessions = shallowRef([])

watch(
  sessionsData,
  (v) => {
    if (v && Array.isArray(v)) {
      sessions.value = v.map((s) => ({ ...s }))
    } else {
      sessions.value = []
    }
  },
  { immediate: true }
)

let offAforoActualitzat = () => {}
let offCatalogActualitzat = () => {}
let offSocketConnect = () => {}

function joinAllSocketRooms() {
  if (!peliId) {
    return
  }
  joinPelicula(peliId)
  for (const s of sessions.value) {
    joinSessio(s.id)
  }
}

onMounted(() => {
  offAforoActualitzat = onAforoActualitzat((data) => {
    if (data.sessio_id === undefined || data.aforo_disponible === undefined) {
      return
    }
    const sid = Number(data.sessio_id)
    const idx = sessions.value.findIndex((s) => Number(s.id) === sid)
    if (idx === -1) {
      return
    }
    sessions.value = sessions.value.map((s, i) =>
      i === idx ? { ...s, aforo_disponible: data.aforo_disponible } : s
    )
  })

  offCatalogActualitzat = onCatalogActualitzat(async (data) => {
    if (!peliId) return
    if (data.scope === 'peliculas') {
      await refreshPeli()
      await refreshSessions()
      await nextTick()
      joinAllSocketRooms()
      return
    }
    if (data.scope === 'sesiones' && Number(data.pelicula_id) === Number(peliId)) {
      await refreshSessions()
      await nextTick()
      joinAllSocketRooms()
    }
  })

  const socket = ensureSocket()
  if (socket) {
    socket.on('connect', joinAllSocketRooms)
    offSocketConnect = () => socket.off('connect', joinAllSocketRooms)
    joinAllSocketRooms()
  }
})

onUnmounted(() => {
  offAforoActualitzat()
  offCatalogActualitzat()
  offSocketConnect()
})

function anarAButaques(sessioId) {
  navigateTo(`/butaques?peli=${peliId}&sessio=${sessioId}`)
}

function partsSessio(dataHora) {
  try {
    const d = new Date(dataHora)
    if (Number.isNaN(d.getTime())) {
      return { hora: String(dataHora), diaEtiqueta: '' }
    }
    return {
      hora: d.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' }),
      diaEtiqueta: d.toLocaleDateString('ca-ES', {
        weekday: 'short',
        day: 'numeric',
        month: 'short'
      })
    }
  } catch {
    return { hora: String(dataHora), diaEtiqueta: '' }
  }
}

const diesUnics = computed(() => {
  const claus = new Set()
  for (const s of sessions.value) {
    const d = new Date(s.data_hora)
    if (!Number.isNaN(d.getTime())) {
      claus.add(d.toDateString())
    }
  }
  return [...claus].sort((a, b) => new Date(a) - new Date(b))
})

const diaSeleccionat = ref(null)

watch(
  diesUnics,
  (dies) => {
    if (!dies.length) {
      diaSeleccionat.value = null
      return
    }
    if (!diaSeleccionat.value || !dies.includes(diaSeleccionat.value)) {
      diaSeleccionat.value = dies[0]
    }
  },
  { immediate: true }
)

const sessionsDelDia = computed(() => {
  if (!diaSeleccionat.value) {
    return sessions.value
  }
  return sessions.value.filter((s) => {
    const d = new Date(s.data_hora)
    return !Number.isNaN(d.getTime()) && d.toDateString() === diaSeleccionat.value
  })
})

const sessionsPerSala = computed(() => {
  const map = new Map()
  for (const s of sessionsDelDia.value) {
    const nom = s.sala_nom || 'Sala'
    if (!map.has(nom)) {
      map.set(nom, [])
    }
    map.get(nom).push({ ...s, parts: partsSessio(s.data_hora) })
  }
  return [...map.entries()].map(([salaNom, list]) => ({
    salaNom,
    sessions: list.sort((a, b) => new Date(a.data_hora) - new Date(b.data_hora))
  }))
})

function etiquetaDia(clauData) {
  const avui = new Date()
  const dema = new Date(avui)
  dema.setDate(dema.getDate() + 1)
  const d = new Date(clauData)
  if (d.toDateString() === avui.toDateString()) {
    return 'Avui'
  }
  if (d.toDateString() === dema.toDateString()) {
    return 'Demà'
  }
  return d.toLocaleDateString('ca-ES', { weekday: 'short', day: 'numeric', month: 'short' })
}

function sessioSlotClass(disponible) {
  const base =
    'flex min-h-[5rem] flex-col items-center justify-center gap-1 rounded-sm border px-3 py-3 text-center transition md:min-h-[5.25rem]'
  if (disponible) {
    return `${base} cursor-pointer border-stone-600 bg-black/30 text-white hover:border-white hover:bg-white hover:text-black`
  }
  return `${base} cursor-not-allowed border-stone-800 bg-stone-950/60 text-stone-600`
}
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-surface-container-lowest selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main class="w-full pb-28 pt-28 md:pb-20">
      <!-- Sense pel·lícula a la query -->
      <div v-if="!peliId" class="px-4">
        <div
          class="mx-auto mt-8 max-w-lg border border-stone-800/80 bg-surface-container/80 p-10 text-center shadow-[0_20px_50px_rgba(65,0,0,0.2)] backdrop-blur-sm"
        >
        <p class="font-body text-stone-400">
          No s'ha triat cap pel·lícula.
        </p>
        <NuxtLink
          to="/"
          class="font-headline mt-6 inline-block text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
        >
          Tornar a la cartellera
        </NuxtLink>
        </div>
      </div>

      <!-- Carregant -->
      <div
        v-else-if="pendingPeli"
        class="flex min-h-[40vh] flex-col items-center justify-center gap-4 px-4"
      >
        <span class="font-headline text-lg uppercase tracking-[0.2em] text-stone-500">Carregant…</span>
      </div>

      <!-- Error -->
      <div v-else-if="errorPeli || !peli" class="px-4">
        <div
          class="mx-auto mt-8 max-w-lg border border-red-950/40 bg-stone-950/50 p-10 text-center"
        >
          <p class="font-body text-red-300/90">
            No s'ha pogut carregar la pel·lícula.
          </p>
          <NuxtLink
            to="/"
            class="font-headline mt-6 inline-block text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
          >
            Tornar a la cartellera
          </NuxtLink>
        </div>
      </div>

      <!-- Contingut: showcase (esquerra) + experiència (dreta), nav superior sense canvis -->
      <div v-else class="flex min-h-0 flex-col md:min-h-[calc(100svh-7rem)]">
        <div class="grid flex-1 md:grid-cols-2 md:items-stretch md:min-h-[calc(100svh-7rem)]">
          <!-- Columna pel·lícula: imatge full-bleed + tipografia sobreposada -->
          <section class="relative min-h-[72svh] overflow-hidden md:min-h-full">
            <img
              :src="peli.imatge_url"
              :alt="peli.titol"
              class="absolute inset-0 h-full w-full object-cover"
            >
            <div
              class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/55 to-black/30"
            />
            <div
              class="pointer-events-none absolute inset-0 bg-gradient-to-r from-black/70 via-transparent to-transparent md:w-[85%]"
            />

            <NuxtLink
              to="/"
              class="absolute left-4 top-4 z-20 font-headline text-[10px] uppercase tracking-[0.25em] text-white/70 transition hover:text-white md:left-8 md:top-8 md:text-xs"
            >
              ← Cartellera
            </NuxtLink>

            <div
              class="relative z-10 flex h-full min-h-[72svh] flex-col justify-end p-6 pb-10 md:min-h-full md:p-10 lg:p-14 lg:pb-16"
            >
              <p
                class="font-headline mb-3 text-[10px] font-semibold uppercase tracking-[0.35em] text-red-400/95 md:text-xs"
              >
                Passi exclusiu · TICKET-FAST
              </p>
              <h1
                class="font-headline max-w-[95%] text-4xl font-bold uppercase leading-[0.95] tracking-tight text-white md:text-5xl lg:text-6xl xl:text-7xl"
              >
                {{ peli.titol }}
              </h1>

              <div v-if="peli.descripcio" class="mt-8 max-w-xl border-t border-white/10 pt-8">
                <h3 class="font-headline text-xs font-bold uppercase tracking-[0.2em] text-stone-400">
                  La història
                </h3>
                <p
                  class="font-body mt-3 text-sm leading-relaxed text-stone-300 md:text-base md:leading-relaxed"
                >
                  {{ peli.descripcio }}
                </p>
              </div>
            </div>
          </section>

          <!-- Columna sessions: panell fosc, slots tipus rellotge -->
          <aside
            class="flex min-h-0 flex-col border-stone-800 bg-stone-950 md:min-h-full md:border-l md:px-8 md:py-12 lg:px-12 lg:py-14"
          >
            <div class="border-b border-stone-800/80 px-6 pb-8 pt-6 md:border-0 md:px-0 md:pb-0 md:pt-0">
              <h2
                class="font-headline text-3xl font-bold uppercase tracking-tight text-white md:text-4xl lg:text-5xl"
              >
                Experiència
              </h2>
              <p class="font-body mt-2 text-sm text-stone-500">
                Tria hora i sala; després seleccionaràs les butaques.
              </p>

              <div
                v-if="diesUnics.length > 1"
                class="mt-8 flex flex-wrap gap-2"
                role="tablist"
                aria-label="Dia de la sessió"
              >
                <button
                  v-for="dia in diesUnics"
                  :key="dia"
                  type="button"
                  role="tab"
                  :aria-selected="diaSeleccionat === dia"
                  class="font-headline rounded-full px-5 py-2 text-[10px] font-bold uppercase tracking-wider transition md:text-xs"
                  :class="
                    diaSeleccionat === dia
                      ? 'bg-white text-black'
                      : 'bg-transparent text-stone-400 hover:text-white'
                  "
                  @click="diaSeleccionat = dia"
                >
                  {{ etiquetaDia(dia) }}
                </button>
              </div>
            </div>

            <div class="flex flex-1 flex-col gap-10 overflow-y-auto px-6 py-8 md:px-0 md:py-10">
              <template v-if="sessionsPerSala.length">
                <div
                  v-for="grup in sessionsPerSala"
                  :key="grup.salaNom"
                  class="space-y-4"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="material-symbols-outlined text-lg text-stone-500"
                      aria-hidden="true"
                    >theaters</span>
                    <h3
                      class="font-headline text-xs font-bold uppercase tracking-[0.2em] text-stone-400"
                    >
                      {{ grup.salaNom }}
                    </h3>
                  </div>
                  <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3">
                    <button
                      v-for="sessio in grup.sessions"
                      :key="sessio.id"
                      type="button"
                      :disabled="sessio.aforo_disponible <= 0"
                      :class="sessioSlotClass(sessio.aforo_disponible > 0)"
                      @click="anarAButaques(sessio.id)"
                    >
                      <span class="font-headline text-xl font-bold tracking-tight md:text-2xl">
                        {{ sessio.parts.hora }}
                      </span>
                      <span
                        v-if="sessio.parts.diaEtiqueta"
                        class="font-label text-[9px] uppercase tracking-widest text-stone-500"
                      >
                        {{ sessio.parts.diaEtiqueta }}
                      </span>
                      <span
                        v-if="sessio.aforo_disponible > 0"
                        class="font-label mt-1 text-[9px] font-semibold uppercase tracking-wider text-primary"
                      >
                        {{ sessio.aforo_disponible }} lliures
                      </span>
                      <span
                        v-else
                        class="font-headline mt-1 text-[9px] font-bold uppercase tracking-wider text-stone-600"
                      >
                        Completa
                      </span>
                    </button>
                  </div>
                </div>
              </template>

              <p
                v-else
                class="font-body text-sm text-stone-500"
              >
                <template v-if="sessions.length === 0">
                  Encara no hi ha sessions programades per a aquesta pel·lícula.
                </template>
                <template v-else>
                  Cap sessió en aquest dia; tria un altre dia.
                </template>
              </p>
            </div>
          </aside>
        </div>
      </div>
    </main>

    <!-- Mobile bottom nav (mateix patró que index) -->
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
