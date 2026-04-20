<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()

const peliId = route.query.peli

const { data: peli, pending, error } = await useFetch(
  peliId ? `/peliculas/${peliId}` : null,
  { baseURL, immediate: !!peliId }
)

function etiquetaEstat(estat) {
  if (estat === undefined || estat === null || estat === '') {
    return '—'
  }
  return String(estat)
}
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-surface-container-lowest selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main class="w-full pb-28 pt-28 md:pb-20">
      <div v-if="!peliId" class="px-4">
        <div
          class="mx-auto mt-8 max-w-lg border border-stone-800/80 bg-surface-container/80 p-10 text-center shadow-[0_20px_50px_rgba(65,0,0,0.2)] backdrop-blur-sm"
        >
          <p class="font-body text-stone-400">
            Falta el paràmetre <span class="text-stone-300">peli</span> a la URL.
          </p>
          <NuxtLink
            to="/"
            class="font-headline mt-6 inline-block text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
          >
            Tornar a la cartellera
          </NuxtLink>
        </div>
      </div>

      <div
        v-else-if="pending"
        class="flex min-h-[40vh] flex-col items-center justify-center gap-4 px-4"
      >
        <span class="font-headline text-lg uppercase tracking-[0.2em] text-stone-500">Carregant…</span>
      </div>

      <div v-else-if="error || !peli" class="px-4">
        <div class="mx-auto mt-8 max-w-lg border border-red-950/40 bg-stone-950/50 p-10 text-center">
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

      <div v-else class="mx-auto max-w-4xl px-4 md:px-8">
        <NuxtLink
          to="/"
          class="font-headline mb-8 inline-block text-[10px] uppercase tracking-[0.25em] text-stone-500 transition hover:text-primary"
        >
          ← Cartellera
        </NuxtLink>

        <div class="grid gap-10 md:grid-cols-[minmax(0,280px)_1fr] md:gap-14">
          <div class="overflow-hidden border border-stone-800/90 bg-stone-950/40">
            <img
              v-if="peli.imatge_url"
              :src="peli.imatge_url"
              :alt="peli.titol"
              class="aspect-[2/3] w-full object-cover"
            >
            <div v-else class="aspect-[2/3] w-full bg-stone-900" />
          </div>

          <div class="min-w-0">
            <h1
              class="font-headline text-3xl font-bold uppercase leading-tight tracking-tight text-white md:text-4xl lg:text-5xl"
            >
              {{ peli.titol }}
            </h1>

            <dl class="mt-8 space-y-4 border-t border-stone-800/80 pt-8 font-body text-sm text-stone-300">
              <div class="flex flex-wrap gap-x-3 gap-y-1">
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  id
                </dt>
                <dd class="text-white">
                  {{ peli.id }}
                </dd>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1">
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  titol
                </dt>
                <dd class="text-white">
                  {{ peli.titol }}
                </dd>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1">
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  imatge_url
                </dt>
                <dd class="break-all text-primary/90">
                  {{ peli.imatge_url || '—' }}
                </dd>
              </div>
              <div>
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  descripcio
                </dt>
                <dd class="mt-2 leading-relaxed text-stone-300">
                  {{ peli.descripcio || '—' }}
                </dd>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1">
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  durada_minuts
                </dt>
                <dd class="text-white">
                  {{ peli.durada_minuts != null ? peli.durada_minuts : '—' }}
                </dd>
              </div>
              <div class="flex flex-wrap gap-x-3 gap-y-1">
                <dt class="font-label text-[10px] uppercase tracking-widest text-stone-500">
                  estat
                </dt>
                <dd class="text-white">
                  {{ etiquetaEstat(peli.estat) }}
                </dd>
              </div>
            </dl>

            <div class="mt-10 flex flex-wrap gap-4">
              <NuxtLink
                :to="`/sala?peli=${peli.id}`"
                class="bg-white px-6 py-4 text-xs font-black uppercase tracking-widest text-black transition-colors hover:bg-primary"
              >
                Veure sessions
              </NuxtLink>
            </div>
          </div>
        </div>
      </div>
    </main>

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
