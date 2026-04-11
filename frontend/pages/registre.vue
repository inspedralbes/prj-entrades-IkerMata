<script setup>
definePageMeta({
  middleware: 'guest',
  layout: 'blank'
})

const authStore = useAuthStore()
const nom = ref('')
const email = ref('')
const password = ref('')
const errorMsg = ref('')
const pending = ref(false)

async function enviar() {
  errorMsg.value = ''
  pending.value = true
  try {
    await authStore.registrar(nom.value, email.value, password.value)
    navigateTo('/')
  } catch (e) {
    if (e.data && e.data.errors) {
      errorMsg.value = 'Revisa les dades (email únic, contrasenya mínim 8 caràcters)'
    } else {
      errorMsg.value = 'No s’ha pogut completar el registre'
    }
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-surface-container-lowest selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main
      class="flex min-h-screen flex-col items-center justify-center px-4 pb-16 pt-28 md:px-8"
    >
      <div
        class="w-full max-w-md border border-stone-800/80 bg-surface-container/80 p-8 shadow-[0_20px_50px_rgba(65,0,0,0.25)] backdrop-blur-sm md:p-10"
      >
        <h1
          class="font-headline mb-2 text-center text-3xl font-bold uppercase tracking-tight text-white md:text-4xl"
        >
          Crea un compte
        </h1>
        <p class="mb-8 text-center font-body text-sm text-stone-500">
          Uneix-te a TICKET-FAST en un moment
        </p>

        <form class="space-y-6" @submit.prevent="enviar">
          <label class="block">
            <span
              class="mb-2 block font-headline text-xs font-semibold uppercase tracking-widest text-stone-400"
            >
              Nom
            </span>
            <input
              v-model="nom"
              type="text"
              required
              autocomplete="name"
              class="w-full border border-stone-700 bg-stone-950/80 px-4 py-3 font-body text-white placeholder-stone-600 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
              placeholder="El teu nom"
            >
          </label>
          <label class="block">
            <span
              class="mb-2 block font-headline text-xs font-semibold uppercase tracking-widest text-stone-400"
            >
              Correu
            </span>
            <input
              v-model="email"
              type="email"
              required
              autocomplete="email"
              class="w-full border border-stone-700 bg-stone-950/80 px-4 py-3 font-body text-white placeholder-stone-600 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
              placeholder="nom@exemple.com"
            >
          </label>
          <label class="block">
            <span
              class="mb-2 block font-headline text-xs font-semibold uppercase tracking-widest text-stone-400"
            >
              Contrasenya (mín. 8)
            </span>
            <input
              v-model="password"
              type="password"
              required
              minlength="8"
              autocomplete="new-password"
              class="w-full border border-stone-700 bg-stone-950/80 px-4 py-3 font-body text-white placeholder-stone-600 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
              placeholder="••••••••"
            >
          </label>

          <p v-if="errorMsg" class="font-body text-sm text-red-400">
            {{ errorMsg }}
          </p>

          <button
            type="submit"
            :disabled="pending"
            class="font-headline w-full border border-white/10 bg-white py-4 text-sm font-bold uppercase tracking-[0.2em] text-black transition hover:bg-primary hover:text-white disabled:cursor-not-allowed disabled:opacity-60"
          >
            {{ pending ? 'Registrant…' : 'Registra’t' }}
          </button>
        </form>

        <p class="mt-8 text-center font-body text-sm text-stone-500">
          Ja tens compte?
          <NuxtLink
            to="/login"
            class="font-semibold text-primary transition hover:text-red-400"
          >
            Inicia la sessió
          </NuxtLink>
        </p>
      </div>

      <NuxtLink
        to="/"
        class="font-headline mt-10 text-sm uppercase tracking-wider text-stone-500 transition hover:text-white"
      >
        ← Tornar a la cartellera
      </NuxtLink>
    </main>
  </div>
</template>
