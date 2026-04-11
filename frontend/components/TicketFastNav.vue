<script setup>
const props = defineProps({
  /** `default`: Cartellera + entrades. `admin`: Dashboard + temps real (mateix estil noir). */
  variant: {
    type: String,
    default: 'default',
    validator: (v) => v === 'default' || v === 'admin'
  }
})

const route = useRoute()
const authStore = useAuthStore()

const esAdmin = computed(() => props.variant === 'admin')

/** Actiu per a tot l’admin excepte l’ancora «temps real». */
const actiuPanellAdmin = computed(
  () => route.path === '/admin' && route.hash !== '#temps-real'
)

const actiuTempsRealAdmin = computed(() => route.path === '/admin' && route.hash === '#temps-real')

async function sortir() {
  await authStore.logout()
  await navigateTo('/')
}
</script>

<template>
  <nav
    class="fixed top-0 z-50 grid w-full grid-cols-3 items-center bg-gradient-to-r from-stone-950 via-red-950/20 to-stone-950 px-4 py-6 shadow-[0_20px_50px_rgba(65,0,0,0.3)] backdrop-blur-xl md:px-12"
  >
    <NuxtLink
      to="/"
      class="font-headline justify-self-start text-xl font-bold uppercase tracking-widest text-red-500 md:text-2xl"
    >
      TICKET-FAST
    </NuxtLink>
    <div class="flex items-center justify-center gap-6 md:gap-10">
      <template v-if="!esAdmin">
        <NuxtLink
          to="/"
          class="font-headline text-xl uppercase tracking-tighter transition-colors duration-500 md:text-3xl"
          :class="
            route.path === '/'
              ? 'font-bold text-white'
              : 'font-light text-stone-400 hover:text-red-400'
          "
        >
          Cartelera
        </NuxtLink>
        <NuxtLink
          to="/mis-entrades"
          class="font-headline text-xl uppercase tracking-tighter transition-colors duration-500 md:text-3xl"
          :class="
            route.path.startsWith('/mis-entrades')
              ? 'font-bold text-white'
              : 'font-light text-stone-400 hover:text-red-400'
          "
        >
          Mis entradas
        </NuxtLink>
      </template>
      <template v-else>
        <NuxtLink
          to="/admin"
          class="font-headline text-xl uppercase tracking-tighter transition-colors duration-500 md:text-3xl"
          :class="
            actiuPanellAdmin
              ? 'font-bold text-white'
              : 'font-light text-stone-400 hover:text-red-400'
          "
        >
          Panell
        </NuxtLink>
        <NuxtLink
          to="/admin#temps-real"
          class="font-headline text-xl uppercase tracking-tighter transition-colors duration-500 md:text-3xl"
          :class="
            actiuTempsRealAdmin
              ? 'font-bold text-white'
              : 'font-light text-stone-400 hover:text-red-400'
          "
        >
          Temps real
        </NuxtLink>
      </template>
    </div>
    <div class="justify-self-end">
      <NuxtLink
        v-if="!authStore.isAuthenticated"
        to="/login"
        class="font-headline text-sm uppercase tracking-tighter transition-colors duration-500 md:text-lg"
        :class="
          route.path.startsWith('/login')
            ? 'font-bold text-white'
            : 'font-light text-stone-400 hover:text-red-400'
        "
      >
        Login
      </NuxtLink>
      <div
        v-else
        class="flex max-w-[min(100%,14rem)] items-center justify-end gap-2 md:max-w-none md:gap-3"
      >
        <span
          class="font-headline min-w-0 truncate text-right text-xs font-light text-stone-300 md:text-sm"
          :title="authStore.user?.nom"
        >
          {{ authStore.user?.nom }}
        </span>
        <button
          type="button"
          class="font-headline shrink-0 whitespace-nowrap border border-stone-600 px-2 py-1 text-[10px] font-semibold uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-primary md:px-3 md:text-xs"
          @click="sortir"
        >
          Sortir
        </button>
      </div>
    </div>
  </nav>
</template>
