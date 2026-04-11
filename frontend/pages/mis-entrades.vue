<script setup>
definePageMeta({
  middleware: 'auth',
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()
const authStore = useAuthStore()

const { data: entrades, pending, error } = await useFetch('/entrades', {
  baseURL,
  headers: authStore.capcalarsAutenticacio()
})

const primera = computed(() => (entrades.value?.length ? entrades.value[0] : null))
const segona = computed(() => (entrades.value?.length > 1 ? entrades.value[1] : null))
const resta = computed(() => (entrades.value?.length > 2 ? entrades.value.slice(2) : []))

const classePrimeraCol = computed(() =>
  segona.value ? 'md:col-span-3' : 'md:col-span-5'
)

function formatDataEtiqueta(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return d
    .toLocaleDateString('ca-ES', { day: '2-digit', month: 'short', year: 'numeric' })
    .replace(/\./g, '')
    .toUpperCase()
}

function formatHora(iso) {
  if (!iso) return '—'
  return new Date(iso).toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' })
}

function preuTxt(preu) {
  if (preu == null) return '—'
  return `${Number(preu).toFixed(2)} €`
}

const qrObert = ref(null)

function obrirQr(e) {
  qrObert.value = e
}

function tancarQr() {
  qrObert.value = null
}

const qrSrc = computed(() => {
  if (!qrObert.value) return ''
  const payload = `TICKET-FAST|${qrObert.value.id}|${qrObert.value.peli_titol}|${qrObert.value.seient}`
  return `https://api.qrserver.com/v1/create-qr-code/?size=220x220&margin=0&data=${encodeURIComponent(payload)}`
})
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-black font-body text-on-surface selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main class="mx-auto max-w-[1600px] px-4 pb-28 pt-28 md:px-8 md:pb-20 lg:px-12 lg:pt-32">
      <!-- Capçalera estil pòster -->
      <header class="mb-14 md:mb-20">
        <h1
          class="font-headline text-4xl font-black uppercase leading-[0.95] tracking-tight text-white drop-shadow-[2px_2px_0_#000] md:text-6xl lg:text-7xl"
          style="text-shadow: 3px 3px 0 rgba(0,0,0,0.9), -1px -1px 0 rgba(192,1,0,0.35)"
        >
          <span class="block">La teva col·lecció</span>
          <span class="block text-primary">digital</span>
        </h1>
        <p class="font-headline mt-4 text-xl font-bold uppercase tracking-[0.2em] text-stone-400 md:text-2xl">
          Mis entradas
        </p>
      </header>

      <div v-if="pending" class="py-24 text-center font-headline text-sm uppercase tracking-[0.3em] text-stone-500">
        Carregant…
      </div>
      <div v-else-if="error" class="rounded-sm border border-red-950/50 bg-stone-950/80 p-8 text-center text-red-300">
        No s’han pogut carregar les entrades.
      </div>
      <div
        v-else-if="!entrades || entrades.length === 0"
        class="border border-stone-800 bg-surface-container/50 px-8 py-16 text-center"
      >
        <p class="font-body text-stone-400">
          Encara no tens cap entrada comprada.
        </p>
        <NuxtLink
          to="/"
          class="font-headline mt-8 inline-block text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
        >
          Anar a la cartellera
        </NuxtLink>
      </div>

      <div v-else class="space-y-10">
        <!-- Primera fila: destacat + segona entrada -->
        <div class="grid gap-8 md:grid-cols-5 md:gap-10">
          <article
            v-if="primera"
            class="group relative flex flex-col border border-stone-800 bg-surface-container"
            :class="classePrimeraCol"
          >
            <div class="relative aspect-[2/3] max-h-[min(72vh,640px)] overflow-hidden bg-stone-900">
              <img
                v-if="primera.imatge_url"
                :src="primera.imatge_url"
                :alt="primera.peli_titol"
                class="h-full w-full object-cover grayscale transition duration-500 group-hover:grayscale-0"
              >
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-gradient-to-br from-stone-800 to-black font-headline text-2xl text-stone-600"
              >
                TICKET-FAST
              </div>
              <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/90 via-transparent to-transparent" />
            </div>
            <div class="flex flex-1 flex-col justify-between gap-4 p-6 md:p-8">
              <h2 class="font-headline text-2xl font-bold uppercase leading-tight text-white md:text-3xl">
                {{ primera.peli_titol }}
              </h2>
              <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  <p>{{ formatDataEtiqueta(primera.data_hora) }} · {{ formatHora(primera.data_hora) }}</p>
                  <p class="mt-1 text-stone-400">
                    {{ primera.sala_nom }} · Seient {{ primera.seient }} · {{ preuTxt(primera.preu_pagat) }}
                  </p>
                </div>
                <button
                  type="button"
                  class="shrink-0 bg-inverse-surface px-5 py-3 font-label text-[10px] font-bold uppercase tracking-[0.25em] text-surface transition hover:bg-primary hover:text-on-primary-fixed"
                  @click="obrirQr(primera)"
                >
                  Veure codi QR
                </button>
              </div>
            </div>
          </article>

          <article
            v-if="segona"
            class="flex flex-col border border-stone-800 bg-surface-container md:col-span-2"
          >
            <div class="relative aspect-[2/3] max-h-[420px] overflow-hidden bg-stone-900">
              <img
                v-if="segona.imatge_url"
                :src="segona.imatge_url"
                :alt="segona.peli_titol"
                class="h-full w-full object-cover grayscale"
              >
              <div
                v-else
                class="flex h-full w-full items-center justify-center bg-stone-900 font-headline text-stone-600"
              >
                —
              </div>
            </div>
            <div class="border-t border-stone-800 p-4">
              <div class="flex items-center justify-between gap-2">
                <h3 class="font-headline text-lg font-bold uppercase text-white">
                  {{ segona.peli_titol }}
                </h3>
                <span class="material-symbols-outlined text-primary" aria-hidden="true">confirmation_number</span>
              </div>
              <p class="mt-2 font-label text-[10px] uppercase tracking-wider text-stone-500">
                {{ segona.sala_nom }} · {{ segona.seient }}
              </p>
              <button
                type="button"
                class="mt-4 font-headline text-xs font-semibold uppercase tracking-wide text-primary transition hover:text-red-400"
                @click="obrirQr(segona)"
              >
                Veure codi QR →
              </button>
            </div>
          </article>
        </div>

        <!-- Entrades addicionals: targeta horitzontal -->
        <article
          v-for="e in resta"
          :key="e.id"
          class="grid gap-6 border border-stone-800 bg-surface-container md:grid-cols-[140px_1fr] md:items-stretch lg:grid-cols-[180px_1fr]"
        >
          <div class="relative aspect-[2/3] max-h-[220px] w-full overflow-hidden bg-stone-900 md:max-h-none md:min-h-[200px]">
            <img
              v-if="e.imatge_url"
              :src="e.imatge_url"
              :alt="e.peli_titol"
              class="h-full w-full object-cover grayscale md:aspect-auto"
            >
            <div
              v-else
              class="flex h-full min-h-[180px] items-center justify-center bg-gradient-to-br from-stone-800 to-black text-stone-600"
            >
              <span class="material-symbols-outlined text-4xl">movie</span>
            </div>
          </div>
          <div class="flex flex-col justify-center p-6 md:py-10 md:pr-10">
            <p class="font-label text-[10px] font-bold uppercase tracking-[0.35em] text-primary">
              Entrada
            </p>
            <h3 class="font-headline mt-2 text-2xl font-bold uppercase text-white md:text-3xl lg:text-4xl">
              {{ e.peli_titol }}
            </h3>
            <div
              class="mt-6 flex flex-wrap gap-x-10 gap-y-2 font-label text-[10px] uppercase tracking-[0.2em] text-white"
            >
              <span>Data: {{ formatDataEtiqueta(e.data_hora) }}</span>
              <span>Hora: {{ formatHora(e.data_hora) }}</span>
              <span>Sala: {{ e.sala_nom }}</span>
              <span>Seient: {{ e.seient }}</span>
            </div>
            <button
              type="button"
              class="mt-8 w-full max-w-md bg-inverse-surface py-4 font-label text-[10px] font-bold uppercase tracking-[0.3em] text-surface transition hover:bg-primary hover:text-on-primary-fixed md:w-auto md:px-12"
              @click="obrirQr(e)"
            >
              Veure codi QR
            </button>
          </div>
        </article>
      </div>
    </main>

    <!-- Modal QR -->
    <Teleport to="body">
      <div
        v-if="qrObert"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="qr-titol"
        @click.self="tancarQr"
      >
        <div
          class="max-w-sm border border-stone-700 bg-surface-container p-8 shadow-2xl"
        >
          <h2 id="qr-titol" class="font-headline text-center text-xl font-bold uppercase text-white">
            {{ qrObert.peli_titol }}
          </h2>
          <p class="mt-2 text-center font-label text-[10px] uppercase tracking-wider text-stone-500">
            Seient {{ qrObert.seient }} · {{ formatDataEtiqueta(qrObert.data_hora) }}
          </p>
          <div class="mt-6 flex justify-center bg-white p-4">
            <img :src="qrSrc" alt="" class="h-[220px] w-[220px]" width="220" height="220">
          </div>
          <p class="mt-4 text-center font-mono text-[10px] text-stone-500">
            ID: {{ qrObert.id }}
          </p>
          <button
            type="button"
            class="mt-6 w-full border border-stone-600 py-3 font-headline text-xs uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-white"
            @click="tancarQr"
          >
            Tancar
          </button>
        </div>
      </div>
    </Teleport>

    <footer
      class="flex w-full flex-col items-center justify-center gap-12 border-t-0 bg-stone-950 px-8 py-16 lg:py-20"
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
