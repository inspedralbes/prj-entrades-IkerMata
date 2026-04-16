<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
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
/** Segona pel·lícula per a la recomanació; si només n’hi ha una, s’usa la mateixa. */
const recommendationMovie = computed(() => movies.value[1] ?? movies.value[0] ?? null)

function heroTitleLines(titol) {
  if (!titol) return ['Cartellera']
  const w = String(titol).trim().split(/\s+/)
  if (w.length <= 2) {
    return [w.join(' ')]
  }
  return [w.slice(0, 2).join(' '), w.slice(2).join(' ')]
}

function excerpt(text, max) {
  if (!text) return ''
  const t = String(text).replace(/\s+/g, ' ').trim()
  return t.length > max ? `${t.slice(0, max)}…` : t
}

const carouselScroll = ref(null)

function scrollCarousel(direction) {
  const el = carouselScroll.value
  if (!el) return
  const first = el.querySelector('a')
  const step = first ? first.getBoundingClientRect().width + 24 : 400
  el.scrollBy({ left: direction * step, behavior: 'smooth' })
}

let offAforoActualitzat = () => {}
let offCatalogActualitzat = () => {}
let offSocketConnect = () => {}

function joinAllPelicules() {
  for (const m of movies.value) {
    joinPelicula(m.id)
  }
}

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
    class="bg-surface-container-lowest min-h-screen overflow-x-hidden selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main class="min-h-screen">
      <!-- Hero -->
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
        <div class="relative z-10 mx-auto w-full max-w-7xl px-6 pb-24 md:px-12 md:pb-32">
          <p class="font-label mb-6 text-sm font-bold uppercase tracking-[0.4em] text-primary">
            Destacado
          </p>
          <h1
            class="font-headline -ml-2 mb-8 text-[clamp(2.5rem,10vw,8rem)] font-black uppercase leading-[0.9] tracking-tighter"
          >
            <template v-if="featured">
              <span v-for="(line, idx) in heroTitleLines(featured.titol)" :key="idx">
                <br v-if="idx > 0">
                <span :class="idx === 0 ? 'text-white' : 'text-primary-container'">{{ line }}</span>
              </span>
            </template>
            <template v-else>
              LA <br >
              <span class="text-primary-container">CARTELLERA</span>
            </template>
          </h1>
          <div class="flex flex-col items-start gap-12 md:flex-row md:items-end">
            <div class="max-w-md">
              <p class="font-body mb-8 text-lg italic leading-relaxed text-on-surface-variant">
                {{
                  featured
                    ? `"${excerpt(featured.descripcio, 180)}"`
                    : 'Selecciona una pel·lícula i gaudeix de la millor experiència.'
                }}
              </p>
              <div class="flex flex-wrap gap-4">
                <NuxtLink
                  v-if="featured"
                  :to="`/sala?peli=${featured.id}`"
                  class="bg-white px-8 py-5 text-sm font-black uppercase tracking-widest text-black transition-colors hover:bg-primary md:px-12"
                >
                  Veure sessions
                </NuxtLink>
                <span
                  v-else
                  class="cursor-not-allowed bg-stone-700 px-8 py-5 text-sm font-black uppercase tracking-widest text-stone-400 md:px-12"
                >
                  Properament
                </span>
              </div>
            </div>
            <div v-if="featured" class="flex items-center gap-4">
              <div class="h-12 w-[2px] bg-primary" />
              <div>
                <p class="mb-1 text-[10px] font-bold uppercase tracking-widest text-stone-500">
                  Estat
                </p>
                <p class="font-headline text-2xl uppercase text-white">
                  {{ featured.hi_ha_disponibilitat ? 'Entrades disponibles' : 'Esgotat' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Graella cartellera -->
      <section class="bg-surface-container-lowest px-6 py-24 md:px-12 md:py-40">
        <div class="mx-auto max-w-screen-2xl">
          <div class="mb-16 flex flex-col justify-between gap-8 md:mb-32 md:flex-row md:items-end">
            <h2
              class="font-headline text-5xl font-black uppercase leading-none tracking-tighter text-white md:text-8xl"
            >
              LA <br >
              CARTELLERA
            </h2>
            <div class="text-left md:text-right">
              <p class="font-label mb-2 text-xs uppercase tracking-widest text-stone-500">
                Sessions en temps real
              </p>
              <p class="text-lg font-bold tracking-widest text-primary">
                Disponibilitat actualitzada
              </p>
            </div>
          </div>

          <div v-if="pending" class="py-24 text-center font-label text-stone-400">
              S’està carregant la cartellera…
          </div>
          <div v-else-if="error" class="py-24 text-center text-error">
            Error en carregar les dades.
          </div>
          <div v-else-if="!movies.length" class="py-24 text-center text-on-surface-variant">
            No hi ha pel·lícules disponibles.
          </div>
          <div v-else class="relative">
            <button
              type="button"
              class="absolute left-1 top-1/2 z-10 flex -translate-y-1/2 items-center justify-center border border-stone-700 bg-stone-950/95 p-1.5 text-stone-300 shadow-lg transition hover:border-primary hover:text-primary md:left-0 md:p-2"
              aria-label="Anterior imatge"
              @click="scrollCarousel(-1)"
            >
              <span class="material-symbols-outlined text-2xl md:text-3xl">chevron_left</span>
            </button>
            <button
              type="button"
              class="absolute right-1 top-1/2 z-10 flex -translate-y-1/2 items-center justify-center border border-stone-700 bg-stone-950/95 p-1.5 text-stone-300 shadow-lg transition hover:border-primary hover:text-primary md:right-0 md:p-2"
              aria-label="Següent"
              @click="scrollCarousel(1)"
            >
              <span class="material-symbols-outlined text-2xl md:text-3xl">chevron_right</span>
            </button>

            <div
              ref="carouselScroll"
              class="flex snap-x snap-mandatory gap-6 overflow-x-auto scroll-smooth px-10 pb-2 [-ms-overflow-style:none] [scrollbar-width:none] md:px-14 [&::-webkit-scrollbar]:hidden"
            >
              <NuxtLink
                v-for="movie in movies"
                :key="movie.id"
                :to="`/sala?peli=${movie.id}`"
                class="group relative w-[min(82vw,300px)] shrink-0 snap-center overflow-hidden border border-stone-800/90 bg-surface-container transition hover:border-primary/60 md:w-[340px]"
              >
                <div class="relative aspect-[2/3] overflow-hidden">
                  <img
                    :src="movie.imatge_url"
                    :alt="movie.titol"
                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                  >
                  <div class="absolute inset-0 bg-gradient-to-t from-black via-black/30 to-transparent" />
                </div>
                <div class="border-t border-stone-800 p-5">
                  <h3 class="font-headline text-xl font-bold uppercase leading-tight tracking-tight text-white md:text-2xl">
                    {{ movie.titol }}
                  </h3>
                  <p class="mt-3 line-clamp-2 font-body text-sm leading-relaxed text-stone-400">
                    {{ excerpt(movie.descripcio, 130) }}
                  </p>
                  <div class="mt-4 flex items-center justify-between gap-2">
                    <span
                      class="text-xs font-bold uppercase tracking-wider"
                      :class="
                        movie.hi_ha_disponibilitat ? 'text-primary' : 'text-stone-500'
                      "
                    >
                      {{ movie.hi_ha_disponibilitat ? 'Disponible' : 'Esgotat' }}
                    </span>
                    <span class="font-label text-[10px] uppercase tracking-widest text-stone-600">
                      Veure sessions →
                    </span>
                  </div>
                </div>
              </NuxtLink>
            </div>
            <p class="mt-4 text-center font-label text-xs text-stone-500 md:hidden">
              Llisca horitzontalment per veure més
            </p>
          </div>
        </div>
      </section>

      <!-- Director's pick -->
      <section
        class="relative overflow-hidden bg-gradient-to-b from-surface-container-lowest to-surface-container-high py-24 md:py-40"
      >
        <div
          class="pointer-events-none absolute -right-20 top-0 select-none font-headline text-[12rem] font-black leading-none opacity-[0.02] md:text-[30rem]"
        >
          NOIR
        </div>
        <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center gap-12 px-6 md:flex-row md:gap-20 md:px-12">
          <div class="w-full md:w-1/3">
            <img
              v-if="recommendationMovie?.imatge_url"
              :src="recommendationMovie.imatge_url"
              :alt="recommendationMovie.titol"
              class="w-full border-r-8 border-red-600 grayscale"
            >
            <div
              v-else
              class="aspect-[3/4] w-full bg-surface-container-high"
            />
          </div>
          <div class="w-full md:w-2/3">
            <p
              class="font-label mb-6 flex items-center gap-4 text-sm font-bold uppercase tracking-[0.5em] text-primary"
            >
              <span class="h-px w-12 bg-primary" />
              Recomanació
            </p>
            <p
              v-if="recommendationMovie"
              class="font-headline mb-4 text-2xl font-bold uppercase tracking-tight text-white md:text-4xl"
            >
              {{ recommendationMovie.titol }}
            </p>
            <h2
              class="font-headline mb-8 text-4xl font-bold uppercase italic tracking-tighter text-white md:text-7xl"
            >
              «El cinema és la memòria del temps que no tornarà.»
            </h2>
            <p class="font-label text-xl uppercase tracking-widest text-stone-300">
              — TICKET-FAST
            </p>
          </div>
        </div>
      </section>
    </main>

    <footer
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
          Cartellera
        </NuxtLink>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">
          Sales
        </span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">
          Premium
        </span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">
          Suport
        </span>
      </div>
      <div class="h-px w-full max-w-4xl bg-gradient-to-r from-transparent via-stone-800 to-transparent" />
      <div class="text-center">
        <p class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">
          © {{ new Date().getFullYear() }} TICKET-FAST
        </p>
      </div>
    </footer>

    <!-- Mobile bottom nav -->
    <div class="crimson-glass fixed bottom-0 left-0 z-50 grid w-full grid-cols-2 items-center p-4 md:hidden">
      <NuxtLink
        to="/"
        class="flex flex-col items-center gap-1"
        :class="route.path === '/' ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">movie</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Cartellera</span>
      </NuxtLink>
      <NuxtLink
        to="/mis-entrades"
        class="flex flex-col items-center gap-1"
        :class="route.path.startsWith('/mis-entrades') ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">confirmation_number</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Les meves entrades</span>
      </NuxtLink>
    </div>
  </div>
</template>
