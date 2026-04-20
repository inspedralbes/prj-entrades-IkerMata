<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const authStore = useAuthStore()
const baseURL = useApiBase()
const { joinPelicula, onAforoActualitzat, onCatalogActualitzat, ensureSocket } = useSocket()

const { data: moviesData, pending, error, refresh: refreshPeliculas } = await useFetch('/peliculas', {
  baseURL,
  timeout: 5000,
  key: 'cartellera-peliculas'
})

const movies = shallowRef([])

watch(
  moviesData,
  (v) => {
    if (v && Array.isArray(v)) {
      movies.value = v.map((m) => ({ ...m }))
    } else {
      movies.value = []
    }
  },
  { immediate: true }
)

const featured = computed(() => movies.value[0] ?? null)
const recommendationMovie = computed(() => movies.value[1] ?? movies.value[0] ?? null)

const cartelleraTrackRef = ref(null)

function scrollCartellera(dir) {
  const el = cartelleraTrackRef.value
  if (!el) {
    return
  }
  const card = el.querySelector('[data-cartellera-card]')
  const gap = 12
  const step = card ? card.getBoundingClientRect().width + gap : 200
  el.scrollBy({ left: dir * step, behavior: 'smooth' })
}

useHead({
  title: 'TICKET-FAST | THE NOIR PREMIERE'
})

function heroTitleLines(titol) {
  if (!titol) {
    return ['LA', 'CARTELLERA']
  }
  const w = String(titol).trim().split(/\s+/)
  if (w.length <= 1) {
    return [w[0], '']
  }
  const mid = Math.ceil(w.length / 2)
  return [w.slice(0, mid).join(' '), w.slice(mid).join(' ')]
}

function excerpt(text, max) {
  if (!text) {
    return ''
  }
  const t = String(text).replace(/\s+/g, ' ').trim()
  return t.length > max ? `${t.slice(0, max)}…` : t
}

function joinAllPelicules() {
  for (const m of movies.value) {
    joinPelicula(m.id)
  }
}

let offAforoActualitzat = () => {}
let offCatalogActualitzat = () => {}
let offSocketConnect = () => {}

onMounted(() => {
  offAforoActualitzat = onAforoActualitzat((data) => {
    if (data.pelicula_id === undefined || data.hi_ha_disponibilitat === undefined) {
      return
    }
    const pid = Number(data.pelicula_id)
    const idx = movies.value.findIndex((m) => Number(m.id) === pid)
    if (idx === -1) {
      return
    }
    movies.value = movies.value.map((m, i) =>
      i === idx ? { ...m, hi_ha_disponibilitat: data.hi_ha_disponibilitat } : m
    )
  })

  offCatalogActualitzat = onCatalogActualitzat(async () => {
    await refreshPeliculas()
    await nextTick()
    joinAllPelicules()
  })

  const socket = ensureSocket()
  if (socket) {
    socket.on('connect', joinAllPelicules)
    offSocketConnect = () => socket.off('connect', joinAllPelicules)
    joinAllPelicules()
  }
})

onUnmounted(() => {
  offAforoActualitzat()
  offCatalogActualitzat()
  offSocketConnect()
})
</script>

<template>
  <div
    class="bg-surface-container-lowest min-h-screen overflow-x-hidden pb-24 selection:bg-primary selection:text-on-primary-fixed md:pb-0"
  >
    <TicketFastNav />

    <main class="min-h-screen">
      <!-- Hero: destacat -->
      <section class="relative flex h-screen w-full items-end overflow-hidden">
        <div class="absolute inset-0 z-0">
          <img
            v-if="featured?.imatge_url"
            :src="featured.imatge_url"
            :alt="featured.titol"
            class="h-full w-full object-cover brightness-50 contrast-125"
          >
          <div
            v-else
            class="h-full w-full bg-gradient-to-br from-stone-900 via-red-950/40 to-surface-container-lowest"
          />
          <div
            class="absolute inset-0 bg-gradient-to-t from-surface-container-lowest via-transparent to-transparent opacity-90"
          />
          <div
            class="absolute inset-0 bg-gradient-to-r from-surface-container-lowest via-transparent to-transparent opacity-40"
          />
        </div>
        <div class="relative z-10 mx-auto w-full max-w-7xl px-6 pb-28 pt-24 md:px-12 md:pb-32">
          <p class="font-label mb-6 text-sm font-bold uppercase tracking-[0.4em] text-primary">
            Destacat de la setmana
          </p>
          <h1
            class="font-headline -ml-2 mb-8 text-[clamp(2.5rem,10vw,8rem)] font-black uppercase leading-[0.85] tracking-tighter text-white"
          >
            <template v-if="featured">
              {{ heroTitleLines(featured.titol)[0] }}
              <br >
              <span class="text-primary-container">{{ heroTitleLines(featured.titol)[1] }}</span>
            </template>
            <template v-else>
              LA <br >
              <span class="text-primary-container">CARTELLERA</span>
            </template>
          </h1>
          <div class="flex flex-col items-start gap-12 md:flex-row md:items-end">
            <div class="max-w-md">
              <p
                v-if="featured?.descripcio"
                class="font-body mb-8 text-lg italic leading-relaxed text-on-surface-variant"
              >
                «{{ excerpt(featured.descripcio, 180) }}»
              </p>
              <p
                v-else
                class="font-body mb-8 text-lg italic leading-relaxed text-on-surface-variant"
              >
                Consulta la cartellera i reserva la teva sessió.
              </p>
              <div class="flex flex-wrap gap-4">
                <NuxtLink
                  v-if="featured"
                  :to="`/sala?peli=${featured.id}`"
                  class="bg-white px-12 py-5 text-sm font-black uppercase tracking-widest text-black transition-colors hover:bg-primary"
                >
                  Comprar ara
                </NuxtLink>
                <NuxtLink
                  v-if="featured"
                  :to="`/pelicula?peli=${featured.id}`"
                  class="border border-outline-variant px-8 py-5 text-sm font-bold uppercase tracking-widest text-white transition-all hover:bg-surface-container-highest"
                >
                  Fitxa
                </NuxtLink>
              </div>
            </div>
            <div class="flex items-center gap-4">
              <div class="h-12 w-[2px] bg-primary" />
              <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-stone-500">
                  Sessions
                </p>
                <p class="font-headline text-2xl uppercase text-white">
                  {{ featured?.hi_ha_disponibilitat !== false ? 'En directe' : 'Consulta’ns' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Cartellera: carrusel compacte (targetes en fila) -->
      <section id="cartellera" class="bg-surface-container-lowest py-10 md:py-16">
        <div class="mx-auto max-w-screen-2xl px-4 md:px-10">
          <div class="mb-6 flex flex-col gap-2 md:mb-8 md:flex-row md:items-end md:justify-between">
            <h2 class="font-headline text-4xl font-black uppercase leading-none tracking-tighter text-white md:text-6xl">
              LA <br >
              CARTELLERA
            </h2>
            <p class="font-label text-[10px] uppercase tracking-widest text-stone-500 md:text-xs">
              Llisca o fes servir les fletxes
            </p>
          </div>

          <div v-if="pending" class="py-16 text-center font-label text-sm text-stone-400">
            S’està carregant la cartellera…
          </div>
          <div v-else-if="error" class="py-16 text-center text-sm text-error">
            Error en carregar les dades.
          </div>
          <div v-else-if="!movies.length" class="py-16 text-center text-sm text-on-surface-variant">
            No hi ha pel·lícules disponibles.
          </div>

          <div
            v-else
            class="relative"
            role="region"
            aria-roledescription="carrusel"
            aria-label="Pel·lícules en cartellera"
          >
            <button
              type="button"
              class="absolute left-0 top-[42%] z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center border border-white/20 bg-black/80 text-white shadow-lg transition hover:border-white/40 hover:bg-black md:h-9 md:w-9"
              aria-label="Desplaçar cap a l’esquerra"
              @click="scrollCartellera(-1)"
            >
              <span class="material-symbols-outlined text-xl leading-none">chevron_left</span>
            </button>
            <button
              type="button"
              class="absolute right-0 top-[42%] z-10 flex h-8 w-8 -translate-y-1/2 items-center justify-center border border-white/20 bg-black/80 text-white shadow-lg transition hover:border-white/40 hover:bg-black md:h-9 md:w-9"
              aria-label="Desplaçar cap a la dreta"
              @click="scrollCartellera(1)"
            >
              <span class="material-symbols-outlined text-xl leading-none">chevron_right</span>
            </button>

            <div
              ref="cartelleraTrackRef"
              class="flex snap-x snap-mandatory gap-3 overflow-x-auto scroll-smooth px-10 py-1 [-ms-overflow-style:none] [scrollbar-width:none] md:gap-3 md:px-12 [&::-webkit-scrollbar]:hidden"
            >
              <NuxtLink
                v-for="m in movies"
                :key="m.id"
                data-cartellera-card
                :to="`/sala?peli=${m.id}`"
                class="group flex w-[176px] shrink-0 snap-start flex-col overflow-hidden border border-stone-800 bg-[#141414] transition hover:border-stone-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white/30 sm:w-[200px] md:w-[228px]"
              >
                <div class="relative aspect-[2/3] w-full overflow-hidden bg-stone-950">
                  <img
                    :src="m.imatge_url"
                    :alt="m.titol"
                    class="h-full w-full object-cover transition duration-300 group-hover:brightness-110"
                  >
                </div>
                <div class="flex min-h-[150px] flex-1 flex-col border-t border-stone-800/80 bg-[#1a1a1a] p-3 md:min-h-[162px] md:p-3.5">
                  <h3
                    class="font-headline line-clamp-2 text-xs font-bold uppercase leading-tight tracking-tight text-white md:text-sm"
                  >
                    {{ m.titol }}
                  </h3>
                  <p
                    class="font-body mt-2 line-clamp-3 flex-1 text-[11px] leading-snug text-stone-500 md:text-xs"
                  >
                    {{ m.descripcio ? excerpt(m.descripcio, 110) : 'Sessions i entrades en línia.' }}
                  </p>
                  <div
                    class="mt-2.5 flex items-end justify-between gap-1 border-t border-stone-800/60 pt-2.5"
                  >
                    <span
                      class="font-label text-[10px] font-bold uppercase tracking-wide"
                      :class="
                        m.hi_ha_disponibilitat !== false ? 'text-red-600' : 'text-stone-600'
                      "
                    >
                      {{ m.hi_ha_disponibilitat !== false ? 'Disponible' : 'No disponible' }}
                    </span>
                    <span
                      class="font-label text-[9px] uppercase tracking-wider text-stone-600 transition group-hover:text-stone-400 md:text-[10px]"
                    >
                      Veure sessions →
                    </span>
                  </div>
                </div>
              </NuxtLink>
            </div>
          </div>
        </div>
      </section>

      <!-- Recomanació -->
      <section
        id="recomenacio"
        class="relative overflow-hidden bg-gradient-to-b from-surface-container-lowest to-surface-container-high py-24 md:py-40"
      >
        <div class="pointer-events-none absolute -right-20 top-0 select-none font-headline text-[12rem] font-black leading-none opacity-[0.02] md:text-[30rem]">
          NOIR
        </div>
        <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center gap-12 px-6 md:flex-row md:gap-20 md:px-12">
          <div class="w-full md:w-1/3">
            <NuxtLink
              v-if="recommendationMovie?.imatge_url"
              :to="`/pelicula?peli=${recommendationMovie.id}`"
              class="block"
            >
              <img
                :src="recommendationMovie.imatge_url"
                :alt="recommendationMovie.titol"
                class="w-full border-r-8 border-red-600 grayscale"
              >
            </NuxtLink>
            <div v-else class="aspect-video w-full bg-surface-container-high" />
          </div>
          <div class="w-full md:w-2/3">
            <p
              class="mb-6 flex items-center gap-4 text-sm font-bold uppercase tracking-[0.5em] text-primary"
            >
              <span class="h-px w-12 bg-primary" />
              Recomanació
            </p>
            <h2 class="font-headline mb-8 text-4xl font-bold uppercase italic leading-tight tracking-tighter text-white md:text-7xl">
              «El cinema no és un reflex de la realitat, és el somni de la mateixa.»
            </h2>
            <p class="font-label text-xl uppercase tracking-widest text-stone-400">
              — Programació TICKET-FAST
            </p>
          </div>
        </div>
      </section>
    </main>

    <footer
      id="peu"
      class="flex w-full flex-col items-center justify-center gap-12 border-t-0 bg-stone-950 px-8 py-20"
    >
      <div class="font-headline text-3xl font-black tracking-[0.3em] text-red-600 md:text-5xl">
        TICKET-FAST
      </div>
      <div class="flex flex-wrap justify-center gap-8 md:gap-10">
        <NuxtLink
          to="/"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Programació
        </NuxtLink>
        <a
          href="#cartellera"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Sales
        </a>
        <NuxtLink
          to="/mis-entrades"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Premium
        </NuxtLink>
        <a
          href="#peu"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Suport
        </a>
      </div>
      <div class="h-px w-full max-w-4xl bg-gradient-to-r from-transparent via-stone-800 to-transparent" />
      <div class="text-center">
        <p class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">
          © {{ new Date().getFullYear() }} TICKET-FAST. THE NOIR PREMIERE.
        </p>
      </div>
    </footer>

    <!-- Bottom nav mòbil -->
    <div
      class="crimson-glass fixed bottom-0 left-0 z-50 grid w-full grid-cols-4 items-center p-4 md:hidden"
    >
      <NuxtLink
        to="/"
        class="flex flex-col items-center gap-1"
        :class="route.path === '/' ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">movie</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Cartellera</span>
      </NuxtLink>
      <a href="#cartellera" class="flex flex-col items-center gap-1 text-stone-400">
        <span class="material-symbols-outlined">theater_comedy</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Sales</span>
      </a>
      <NuxtLink
        to="/mis-entrades"
        class="flex flex-col items-center gap-1"
        :class="route.path.startsWith('/mis-entrades') ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">stars</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Entrades</span>
      </NuxtLink>
      <NuxtLink
        :to="authStore.isAuthenticated ? '/mis-entrades' : '/login'"
        class="flex flex-col items-center gap-1"
        :class="
          authStore.isAuthenticated
            ? route.path.startsWith('/mis-entrades')
              ? 'text-primary'
              : 'text-stone-400'
            : route.path.startsWith('/login')
              ? 'text-primary'
              : 'text-stone-400'
        "
      >
        <span class="material-symbols-outlined">person</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Perfil</span>
      </NuxtLink>
    </div>
  </div>
</template>
