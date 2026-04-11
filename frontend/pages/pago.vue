<script setup>
definePageMeta({
  layout: 'blank',
  middleware: 'auth'
})

const route = useRoute()
const baseURL = useApiBase()
const gatewayURL = usePublicGatewayUrl()
const authStore = useAuthStore()

function primerQuery(val) {
  if (val == null) return undefined
  return Array.isArray(val) ? val[0] : val
}

const peliId = primerQuery(route.query.peli)
const sessioId = primerQuery(route.query.sessio)
const seientsParam = primerQuery(route.query.seients)

const seientIds = computed(() => {
  if (!seientsParam || typeof seientsParam !== 'string') return []
  return seientsParam.split(',').map((id) => parseInt(id, 10)).filter((n) => !Number.isNaN(n))
})

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: sessionsList } = await useFetch(peliId ? `/peliculas/${peliId}/sesiones` : null, {
  baseURL,
  immediate: !!peliId
})
const { data: vendaCfg } = await useFetch('/configuracio-venda', { baseURL })
const { data: totsSeients } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId
})

const sessioActual = computed(() => {
  const list = sessionsList.value
  if (!list || !Array.isArray(list) || !sessioId) {
    return null
  }
  return list.find((s) => Number(s.id) === Number(sessioId)) ?? null
})

const seleccionats = computed(() => {
  if (!totsSeients.value || !seientIds.value.length) return []
  const set = new Set(seientIds.value)
  return totsSeients.value.filter((s) => set.has(s.id))
})

function getPreu(categoria) {
  const preus = sessioActual.value?.preus
  if (preus?.length) {
    const row = preus.find((p) => p.categoria === categoria)
    if (row) {
      return parseFloat(row.preu, 10)
    }
  }
  if (categoria === 'VIP') return 9.7
  return 6.7
}

const totalPreu = computed(() => {
  return seleccionats.value.reduce((sum, s) => sum + getPreu(s.categoria), 0)
})

/** Sense càrrecs addicionals en aquesta demo; coincideix amb el que registra la compra al servidor. */
const cargosServei = computed(() => 0)
const totalAmbCargos = computed(() => totalPreu.value)

function formatEuro(n) {
  return (
    n.toLocaleString('ca-ES', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }) + ' €'
  )
}

function formatDataHoraCompleta(iso) {
  if (!iso) return '—'
  const d = new Date(iso)
  return (
    d.toLocaleDateString('ca-ES', { day: 'numeric', month: 'short', year: 'numeric' })
    + ' — '
    + d.toLocaleTimeString('ca-ES', { hour: '2-digit', minute: '2-digit' })
  )
}

const form = reactive({
  email: '',
  nom: '',
  numeroTargeta: '',
  caducitat: '',
  cvv: ''
})

watch(
  () => authStore.user,
  (u) => {
    if (u && !form.nom && u.nom) {
      form.nom = u.nom
    }
    if (u && !form.email && u.email) {
      form.email = u.email
    }
  },
  { immediate: true }
)

const enviant = ref(false)

const segonsRestants = ref(0)
let intervalId

function inicialitzaCompteEnrere() {
  const minuts = vendaCfg.value?.reserva_temporal_minuts ?? 5
  let fiMs = Date.now() + minuts * 60 * 1000
  if (sessioId && typeof sessionStorage !== 'undefined') {
    try {
      const raw = sessionStorage.getItem(`ticketfast_expira_${String(sessioId)}`)
      if (raw) {
        const t = new Date(raw).getTime()
        if (!Number.isNaN(t) && t > Date.now()) {
          fiMs = t
        }
      }
    } catch (_) {
      /* ignore */
    }
  }
  segonsRestants.value = Math.max(0, Math.floor((fiMs - Date.now()) / 1000))
}

onMounted(() => {
  inicialitzaCompteEnrere()
  intervalId = setInterval(() => {
    if (segonsRestants.value > 0) {
      segonsRestants.value -= 1
    }
  }, 1000)
})

onUnmounted(() => {
  if (intervalId) {
    clearInterval(intervalId)
  }
})

const tempsRestantFormat = computed(() => {
  const t = segonsRestants.value
  const m = Math.floor(t / 60)
  const s = t % 60
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
})

function formatTargeta(v) {
  const digits = v.replace(/\D/g, '').slice(0, 16)
  return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim()
}

function formatCaducitat(v) {
  const d = v.replace(/\D/g, '').slice(0, 4)
  if (d.length <= 2) return d
  return `${d.slice(0, 2)}/${d.slice(2)}`
}

watch(
  () => form.numeroTargeta,
  (val) => {
    const f = formatTargeta(val)
    if (f !== val) form.numeroTargeta = f
  }
)

watch(
  () => form.caducitat,
  (val) => {
    const f = formatCaducitat(val)
    if (f !== val) form.caducitat = f
  }
)

async function enviarCompra() {
  const nom = String(form.nom || '').trim()
  const email = String(form.email || '').trim()
  if (nom.length < 2) {
    alert('Introdueix un nom vàlid.')
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    alert('Introdueix un correu electrònic vàlid.')
    return
  }
  const digits = form.numeroTargeta.replace(/\D/g, '')
  if (digits.length < 16) {
    alert('El número de targeta ha de tenir 16 dígits (demo).')
    return
  }
  if (!/^\d{2}\/\d{2}$/.test(form.caducitat || '')) {
    alert('La caducitat ha de ser MM/AA.')
    return
  }
  if (!/^\d{3,4}$/.test(form.cvv || '')) {
    alert('El CVC no és vàlid.')
    return
  }
  enviant.value = true
  try {
    const sessioNum = parseInt(String(sessioId), 10)
    const cos = {
      sessioId: sessioNum,
      seientIds: seientIds.value
    }
    const urlComprar = gatewayURL.value + '/api/comprar'
    await $fetch(urlComprar, {
      method: 'POST',
      body: cos,
      headers: authStore.capcalarsAutenticacio()
    })
    navigateTo('/mis-entrades')
  } catch (e) {
    if (e.response && e.response.status === 401) {
      await authStore.logout()
      alert('La sessió ha caducat, cal tornar a entrar')
      navigateTo('/login')
    } else {
      let msg = 'Error en processar la compra'
      if (e && e.data) {
        if (e.data.missatge) {
          msg = e.data.missatge
        } else if (e.data.error) {
          msg = e.data.error
        }
      } else if (e && e.message) {
        msg = e.message
      }
      alert(msg)
    }
  } finally {
    enviant.value = false
  }
}

const butaquesUrl = computed(() => `/butaques?peli=${peliId}&sessio=${sessioId}`)
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-black font-body text-on-surface selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav />

    <main
      class="mx-auto max-w-[1920px] pb-28 pt-28 lg:grid lg:min-h-[calc(100svh-7rem)] lg:grid-cols-12 lg:gap-0 lg:pb-0 lg:pt-32"
    >
      <template v-if="!peliId || !sessioId || !seientIds.length">
        <div
          class="flex min-h-[50vh] flex-col items-center justify-center px-6 text-center lg:col-span-12"
        >
          <p class="text-stone-400">
            No hi ha cap selecció de seients. Torna a triar butaques.
          </p>
          <NuxtLink
            v-if="peliId && sessioId"
            :to="butaquesUrl"
            class="font-headline mt-6 text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
          >
            Anar a butaques
          </NuxtLink>
          <NuxtLink
            v-else
            to="/"
            class="font-headline mt-6 text-sm uppercase tracking-wider text-primary transition hover:text-red-400"
          >
            Cartellera
          </NuxtLink>
        </div>
      </template>

      <template v-else-if="peli && seleccionats.length">
        <!-- Columna formulari -->
        <section
          class="flex flex-col px-6 pb-16 pt-8 lg:col-span-7 lg:px-12 lg:pb-24 lg:pt-4 xl:px-20"
        >
          <NuxtLink
            :to="butaquesUrl"
            class="font-headline mb-10 inline-flex text-xs uppercase tracking-[0.2em] text-stone-500 transition hover:text-white"
          >
            ← Tornar a les butaques
          </NuxtLink>

          <div class="mb-4 flex items-center gap-3">
            <span class="h-px w-8 bg-primary" aria-hidden="true" />
            <span class="font-label text-[10px] font-bold uppercase tracking-[0.35em] text-primary">
              Finalitzar compra
            </span>
          </div>

          <h1 class="font-headline mb-10 text-4xl font-bold tracking-tight text-white md:text-5xl">
            Detalls del pagament
          </h1>

          <!-- Compte enrere -->
          <div
            class="mb-12 flex items-center gap-4 border-l-4 border-primary bg-stone-900/60 px-5 py-4"
            role="status"
            aria-live="polite"
          >
            <span class="material-symbols-outlined shrink-0 text-3xl text-primary" aria-hidden="true">timer</span>
            <div>
              <p class="font-label text-[10px] uppercase tracking-[0.25em] text-stone-500">
                Temps restant
              </p>
              <p class="font-mono text-3xl font-black text-primary md:text-4xl">
                {{ tempsRestantFormat }}
              </p>
            </div>
          </div>

          <form class="max-w-xl space-y-10" novalidate @submit.prevent="enviarCompra">
            <div>
              <label class="block">
                <span class="font-label text-[10px] uppercase tracking-[0.2em] text-stone-500">Nom complet</span>
                <input
                  v-model="form.nom"
                  type="text"
                  autocomplete="name"
                  placeholder="Nom i cognoms"
                  class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-3 text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
                >
              </label>
            </div>

            <div>
              <label class="block">
                <span class="font-label text-[10px] uppercase tracking-[0.2em] text-stone-500">Correu electrònic</span>
                <input
                  v-model="form.email"
                  type="email"
                  autocomplete="email"
                  placeholder="nom@exemple.com"
                  class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-3 text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
                >
              </label>
            </div>

            <div>
              <p class="font-label mb-4 text-[10px] uppercase tracking-[0.2em] text-stone-500">
                Targeta de crèdit
              </p>
              <p class="mb-6 font-body text-xs text-stone-600">
                Demostració — no es desa cap dada real.
              </p>
              <div class="grid grid-cols-1 gap-8 md:grid-cols-12 md:gap-6">
                <label class="md:col-span-7">
                  <span class="sr-only">Número de targeta</span>
                  <input
                    v-model="form.numeroTargeta"
                    type="text"
                    inputmode="numeric"
                    autocomplete="cc-number"
                    maxlength="19"
                    placeholder="0000 0000 0000 0000"
                    class="w-full border-0 border-b border-stone-600 bg-transparent py-3 font-mono text-sm text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
                  >
                </label>
                <label class="md:col-span-3">
                  <span class="font-label text-[10px] uppercase tracking-[0.2em] text-stone-500">Caduca</span>
                  <input
                    v-model="form.caducitat"
                    type="text"
                    inputmode="numeric"
                    autocomplete="cc-exp"
                    maxlength="5"
                    placeholder="MM/AA"
                    class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-3 font-mono text-sm text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
                  >
                </label>
                <label class="md:col-span-2">
                  <span class="font-label text-[10px] uppercase tracking-[0.2em] text-stone-500">CVC</span>
                  <input
                    v-model="form.cvv"
                    type="password"
                    inputmode="numeric"
                    autocomplete="cc-csc"
                    maxlength="4"
                    placeholder="000"
                    class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-3 font-mono text-sm text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
                  >
                </label>
              </div>
            </div>

            <button
              type="submit"
              class="w-full bg-white py-5 font-black text-xs uppercase tracking-[0.35em] text-black transition hover:bg-primary hover:text-on-primary-fixed active:scale-[0.99] disabled:cursor-wait disabled:opacity-60"
              :disabled="enviant"
            >
              {{ enviant ? 'Processant…' : 'Pagar ara' }}
            </button>
          </form>
        </section>

        <!-- Columna resum -->
        <aside
          class="relative flex min-h-[min(100%,560px)] flex-col justify-between overflow-hidden lg:col-span-5 lg:min-h-[calc(100svh-8rem)]"
        >
          <img
            :src="peli.imatge_url"
            :alt="peli.titol"
            class="absolute inset-0 h-full w-full object-cover"
          >
          <div class="absolute inset-0 bg-gradient-to-t from-black via-black/75 to-black/35" />
          <div class="absolute inset-0 bg-gradient-to-r from-black/40 to-transparent" />

          <div class="relative z-10 flex flex-1 flex-col justify-between p-8 lg:p-12">
            <div>
              <h2
                class="font-headline text-3xl font-bold uppercase leading-tight tracking-tight text-white drop-shadow-lg md:text-4xl lg:text-5xl"
              >
                {{ peli.titol }}
              </h2>
              <p
                v-if="sessioActual?.sala_nom"
                class="mt-3 font-label text-xs font-bold uppercase tracking-[0.2em] text-primary"
              >
                {{ sessioActual.sala_nom }}
              </p>

              <div
                class="mt-10 flex items-start justify-between gap-4 border-b border-white/10 pb-6"
              >
                <div>
                  <p class="font-label text-[9px] uppercase tracking-[0.25em] text-stone-500">
                    Data i hora
                  </p>
                  <p class="mt-2 font-body text-sm font-medium text-white">
                    {{ formatDataHoraCompleta(sessioActual?.data_hora) }}
                  </p>
                </div>
                <span class="material-symbols-outlined text-2xl text-primary/90" aria-hidden="true">calendar_month</span>
              </div>

              <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="font-label text-[9px] uppercase tracking-[0.25em] text-stone-500">
                    Seients seleccionats
                  </p>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <span
                      v-for="s in seleccionats"
                      :key="s.id"
                      class="inline-flex border border-primary bg-secondary-container px-3 py-1.5 font-headline text-sm font-bold uppercase tracking-wide text-primary-fixed shadow-[0_0_12px_rgba(255,180,168,0.25)]"
                    >
                      {{ s.fila }}{{ s.numero }}
                    </span>
                  </div>
                </div>
                <span class="material-symbols-outlined text-2xl text-primary/90" aria-hidden="true">event_seat</span>
              </div>
            </div>

            <div
              class="mt-12 border border-white/10 bg-black/55 p-6 backdrop-blur-md lg:mt-auto"
            >
              <div class="flex justify-between border-b border-white/10 py-3 font-label text-xs uppercase tracking-widest text-stone-400">
                <span>Subtotal ({{ seleccionats.length }} entrades)</span>
                <span class="font-body text-white">{{ formatEuro(totalPreu) }}</span>
              </div>
              <div class="flex justify-between py-3 font-label text-xs uppercase tracking-widest text-stone-400">
                <span>Càrrecs de servei</span>
                <span class="font-body text-white">{{ formatEuro(cargosServei) }}</span>
              </div>
              <div class="mt-4 flex items-end justify-between pt-2">
                <span class="font-headline text-lg text-white">Total</span>
                <span class="font-headline text-3xl font-bold text-primary md:text-4xl">{{ formatEuro(totalAmbCargos) }}</span>
              </div>
            </div>
          </div>
        </aside>
      </template>

      <div
        v-else-if="peliId && sessioId"
        class="flex min-h-[40vh] items-center justify-center px-6 text-stone-500 lg:col-span-12"
      >
        Carregant o seients no vàlids…
      </div>
    </main>

    <footer
      class="mt-auto flex w-full flex-col items-center justify-center gap-12 border-t-0 bg-stone-950 px-8 py-16 lg:py-20"
    >
      <div class="font-headline text-2xl font-black tracking-[0.3em] text-red-600 md:text-3xl">
        TICKET-FAST
      </div>
      <div class="flex flex-wrap justify-center gap-8 md:gap-10">
        <NuxtLink
          to="/"
          class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-500 transition-all duration-300 hover:text-white"
        >
          Cartellera
        </NuxtLink>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Sales</span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Premium</span>
        <span class="font-sans text-[10px] font-medium uppercase tracking-[0.2em] text-stone-600">Suport</span>
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
