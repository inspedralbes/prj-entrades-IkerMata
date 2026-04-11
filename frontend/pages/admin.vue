<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()
const authStore = useAuthStore()
const router = useRouter()
const { onCatalogActualitzat, ensureSocket, joinPanellAdmin, onAdminPanellRefresh } = useSocket()

const { data: pelis, refresh: refreshPelis } = await useFetch('/peliculas', { baseURL })

const sales = ref([])

let offCatalogActualitzat = () => {}
let offAdminPanell = () => {}
let debouncePanellTimer = null

const peliSessioId = ref('')
const sessioList = ref([])
const carregantSessions = ref(false)
const mostraFormSessio = ref(false)
const editantSessioId = ref(null)
const formSessio = ref({ sala_id: '', data_hora: '' })
const guardantSessio = ref(false)
const esborrantSessio = ref(false)

function defaultDataHoraLocal() {
  const d = new Date()
  d.setDate(d.getDate() + 1)
  d.setHours(18, 0, 0, 0)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function formatDataHoraPerApi(s) {
  if (!s) return ''
  let x = String(s).replace('T', ' ')
  if (x.length === 16) {
    x += ':00'
  }
  return x
}

function dataHoraPerInput(iso) {
  if (!iso) return defaultDataHoraLocal()
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return defaultDataHoraLocal()
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

async function carregarSessions() {
  sessioList.value = []
  const pid = peliSessioId.value
  if (pid === '' || pid == null) return
  carregantSessions.value = true
  try {
    const root = toValue(baseURL)
    sessioList.value = await $fetch(`${root}/peliculas/${pid}/sesiones`)
  } catch (_) {
    sessioList.value = []
  } finally {
    carregantSessions.value = false
  }
}

watch(peliSessioId, () => {
  mostraFormSessio.value = false
  editantSessioId.value = null
  formSessio.value = { sala_id: '', data_hora: defaultDataHoraLocal() }
  carregarSessions()
})

function novaSessio() {
  if (peliSessioId.value === '' || peliSessioId.value == null) return
  if (!sales.value?.length) {
    alert('No hi ha sales carregades.')
    return
  }
  editantSessioId.value = null
  formSessio.value = {
    sala_id: sales.value[0].id,
    data_hora: defaultDataHoraLocal()
  }
  mostraFormSessio.value = true
}

function editarSessio(s) {
  editantSessioId.value = s.id
  formSessio.value = {
    sala_id: s.sala_id,
    data_hora: dataHoraPerInput(s.data_hora)
  }
  mostraFormSessio.value = true
}

function cancelSessio() {
  mostraFormSessio.value = false
  editantSessioId.value = null
}

async function desarSessio() {
  if (guardantSessio.value) return
  const pid = peliSessioId.value
  if (pid === '' || pid == null) return
  if (!formSessio.value.sala_id || !formSessio.value.data_hora) return
  guardantSessio.value = true
  const root = toValue(baseURL)
  const body = {
    sala_id: Number(formSessio.value.sala_id),
    data_hora: formatDataHoraPerApi(formSessio.value.data_hora)
  }
  try {
    if (editantSessioId.value) {
      await $fetch(`${root}/sesiones/${editantSessioId.value}`, {
        method: 'PUT',
        headers: authStore.capcalarsAutenticacio(),
        body
      })
    } else {
      await $fetch(`${root}/peliculas/${pid}/sesiones`, {
        method: 'POST',
        headers: authStore.capcalarsAutenticacio(),
        body
      })
    }
    mostraFormSessio.value = false
    editantSessioId.value = null
    await carregarSessions()
    await refreshPelis()
  } finally {
    guardantSessio.value = false
  }
}

async function esborrarSessio(id) {
  if (esborrantSessio.value) return
  if (!confirm('Eliminar aquesta sessió?')) return
  esborrantSessio.value = true
  try {
    await $fetch(`${toValue(baseURL)}/sesiones/${id}`, {
      method: 'DELETE',
      headers: authStore.capcalarsAutenticacio()
    })
    await carregarSessions()
    await refreshPelis()
  } catch (e) {
    const msg = e?.data?.error ?? e?.message ?? 'No s\'ha pogut eliminar.'
    alert(msg)
  } finally {
    esborrantSessio.value = false
  }
}

const mostraForm = ref(false)
const editantId = ref(null)
const formulari = ref({ titol: '', descripcio: '', imatge_url: '', durada_minuts: 60 })
const guardantPeli = ref(false)
const esborrant = ref(false)

const panellTempsReal = ref(null)
const informesResum = ref(null)
const carregantPanell = ref(false)
const carregantInformes = ref(false)
let intervalPanell = null

async function carregarPanellTempsReal() {
  if (!authStore.user || authStore.user.rol !== 'admin') {
    return
  }
  carregantPanell.value = true
  try {
    const root = toValue(baseURL)
    panellTempsReal.value = await $fetch(`${root}/admin/panell-temps-real`, {
      headers: authStore.capcalarsAutenticacio()
    })
  } catch (_) {
    panellTempsReal.value = null
  } finally {
    carregantPanell.value = false
  }
}

async function carregarInformesResum() {
  if (!authStore.user || authStore.user.rol !== 'admin') {
    return
  }
  carregantInformes.value = true
  try {
    const root = toValue(baseURL)
    informesResum.value = await $fetch(`${root}/admin/informes-resum`, {
      headers: authStore.capcalarsAutenticacio()
    })
  } catch (_) {
    informesResum.value = null
  } finally {
    carregantInformes.value = false
  }
}

function programarActualitzacioPanell() {
  if (debouncePanellTimer) {
    clearTimeout(debouncePanellTimer)
  }
  debouncePanellTimer = setTimeout(async () => {
    debouncePanellTimer = null
    await carregarPanellTempsReal()
    await carregarInformesResum()
  }, 250)
}

function unirseSocketAdmin() {
  joinPanellAdmin()
}

onMounted(async () => {
  await authStore.syncUsuariSiCal(baseURL)
  if (!authStore.user || authStore.user.rol !== 'admin') {
    router.push('/')
    return
  }
  try {
    sales.value = await $fetch(`${toValue(baseURL)}/sales`, {
      headers: authStore.capcalarsAutenticacio()
    })
  } catch (_) {
    sales.value = []
  }

  offCatalogActualitzat = onCatalogActualitzat(async (data) => {
    await refreshPelis()
    if (data?.scope === 'peliculas' && peliSessioId.value) {
      await carregarSessions()
    }
    if (data?.scope === 'sesiones' && String(data.pelicula_id) === String(peliSessioId.value)) {
      await carregarSessions()
    }
    await carregarPanellTempsReal()
  })

  const socket = ensureSocket()
  if (socket) {
    socket.on('connect', unirseSocketAdmin)
    unirseSocketAdmin()
  }
  offAdminPanell = onAdminPanellRefresh(() => {
    programarActualitzacioPanell()
  })

  await carregarPanellTempsReal()
  await carregarInformesResum()
  intervalPanell = setInterval(carregarPanellTempsReal, 60000)
})

onUnmounted(() => {
  offCatalogActualitzat()
  offAdminPanell()
  const sock = ensureSocket()
  if (sock) {
    sock.off('connect', unirseSocketAdmin)
  }
  if (debouncePanellTimer) {
    clearTimeout(debouncePanellTimer)
    debouncePanellTimer = null
  }
  if (intervalPanell) {
    clearInterval(intervalPanell)
    intervalPanell = null
  }
})

function novaPeli() {
  editantId.value = null
  formulari.value = { titol: '', descripcio: '', imatge_url: '', durada_minuts: 60 }
  mostraForm.value = true
}

function editarPeli(p) {
  editantId.value = p.id
  formulari.value = { ...p }
  mostraForm.value = true
}

async function desarPeli() {
  if (guardantPeli.value) return
  guardantPeli.value = true
  try {
    const root = toValue(baseURL)
    const metode = editantId.value ? 'PUT' : 'POST'
    const url = editantId.value
      ? `${root}/peliculas/${editantId.value}`
      : `${root}/peliculas`

    await $fetch(url, {
      method: metode,
      headers: authStore.capcalarsAutenticacio(),
      body: formulari.value
    })

    mostraForm.value = false
    await refreshPelis()
  } finally {
    guardantPeli.value = false
  }
}

async function esborrarPeli(id) {
  if (esborrant.value) return
  if (!confirm('Segur que vols eliminar aquesta pel·lícula?')) return
  esborrant.value = true
  try {
    await $fetch(`${toValue(baseURL)}/peliculas/${id}`, {
      method: 'DELETE',
      headers: authStore.capcalarsAutenticacio()
    })
    await refreshPelis()
  } finally {
    esborrant.value = false
  }
}

function cancel() {
  mostraForm.value = false
}

function retallDescripcio(t) {
  if (!t) return '—'
  return t.length > 50 ? `${t.substring(0, 50)}…` : t
}
</script>

<template>
  <div
    class="min-h-screen overflow-x-hidden bg-surface-container-lowest font-body text-on-surface selection:bg-primary selection:text-on-primary-fixed"
  >
    <TicketFastNav variant="admin" />

    <main class="mx-auto max-w-6xl px-4 pb-28 pt-28 md:px-8 md:pb-20 lg:px-10">
      <div
        id="admin-panell"
        class="scroll-mt-28 mb-10 flex flex-col gap-4 border-b border-stone-800 pb-8 md:flex-row md:items-end md:justify-between"
      >
        <div>
          <p class="font-label mb-2 text-[10px] font-bold uppercase tracking-[0.35em] text-primary">
            Administració
          </p>
          <h1 class="font-headline text-3xl font-bold uppercase tracking-tight text-white md:text-4xl">
            Panell d’administració
          </h1>
        </div>
        <span
          class="inline-flex w-fit border border-primary/40 bg-stone-950 px-3 py-1 font-label text-[10px] font-bold uppercase tracking-widest text-primary"
        >
          Admin
        </span>
      </div>

      <div class="mb-8 flex flex-wrap gap-3">
        <button
          type="button"
          class="border border-white bg-white px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-black transition hover:bg-primary hover:text-on-primary-fixed disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="guardantPeli || esborrant"
          @click="novaPeli"
        >
          Nova pel·lícula
        </button>
      </div>

      <div
        v-if="mostraForm"
        class="mb-10 border border-stone-800 bg-surface-container/90 p-6 shadow-[0_20px_50px_rgba(0,0,0,0.35)] md:p-8"
      >
        <h2 class="font-headline mb-6 text-xl font-bold uppercase text-white">
          {{ editantId ? 'Editar' : 'Nova' }} pel·lícula
        </h2>
        <div class="grid gap-6 md:grid-cols-2">
          <label class="block md:col-span-2">
            <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Títol</span>
            <input
              v-model="formulari.titol"
              type="text"
              required
              class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-2 text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
            >
          </label>
          <label class="block md:col-span-2">
            <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Descripció</span>
            <textarea
              v-model="formulari.descripcio"
              required
              rows="4"
              class="mt-2 w-full border border-stone-700 bg-stone-950/50 px-3 py-2 text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            />
          </label>
          <label class="block">
            <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">URL imatge</span>
            <input
              v-model="formulari.imatge_url"
              type="url"
              class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-2 text-white placeholder:text-stone-600 focus:border-primary focus:outline-none focus:ring-0"
            >
          </label>
          <label class="block">
            <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Durada (min)</span>
            <input
              v-model.number="formulari.durada_minuts"
              type="number"
              min="1"
              class="mt-2 w-full border-0 border-b border-stone-600 bg-transparent py-2 text-white focus:border-primary focus:outline-none focus:ring-0"
            >
          </label>
        </div>
        <div class="mt-8 flex flex-wrap gap-3">
          <button
            type="button"
            class="border border-white bg-white px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-black transition hover:bg-primary hover:text-on-primary-fixed disabled:opacity-50"
            :disabled="guardantPeli"
            @click="desarPeli"
          >
            {{ guardantPeli ? 'Desant…' : 'Desar' }}
          </button>
          <button
            type="button"
            class="border border-stone-600 px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-stone-400 transition hover:border-stone-500 hover:text-white disabled:opacity-50"
            :disabled="guardantPeli"
            @click="cancel"
          >
            Cancel·lar
          </button>
        </div>
      </div>

      <div class="overflow-x-auto border border-stone-800 bg-black/20">
        <table class="w-full min-w-[640px] border-collapse text-left text-sm">
          <thead>
            <tr class="border-b border-stone-800 bg-stone-950/90">
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">ID</th>
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Títol</th>
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Descripció</th>
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Durada</th>
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Estat</th>
              <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="p in pelis"
              :key="p.id"
              class="border-b border-stone-800/80 transition hover:bg-stone-900/40"
            >
              <td class="px-4 py-3 font-mono text-xs text-stone-400">{{ p.id }}</td>
              <td class="px-4 py-3 font-medium text-white">{{ p.titol }}</td>
              <td class="max-w-[220px] px-4 py-3 text-stone-400">{{ retallDescripcio(p.descripcio) }}</td>
              <td class="px-4 py-3 text-stone-300">{{ p.durada_minuts }} min</td>
              <td class="px-4 py-3">
                <span class="font-label text-[10px] uppercase tracking-wider text-primary">{{ p.estat }}</span>
              </td>
              <td class="px-4 py-3">
                <button
                  type="button"
                  class="mr-2 border border-stone-600 px-3 py-1.5 font-label text-[10px] font-bold uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-primary disabled:opacity-40"
                  :disabled="guardantPeli || esborrant"
                  @click="editarPeli(p)"
                >
                  Editar
                </button>
                <button
                  type="button"
                  class="border border-red-900/60 bg-red-950/30 px-3 py-1.5 font-label text-[10px] font-bold uppercase tracking-wider text-red-300 transition hover:bg-red-950/60 disabled:opacity-40"
                  :disabled="guardantPeli || esborrant"
                  @click="esborrarPeli(p.id)"
                >
                  {{ esborrant ? '…' : 'Esborrar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="!pelis?.length" class="border border-dashed border-stone-700 py-12 text-center text-stone-500">
        No hi ha pel·lícules.
      </div>

      <section id="temps-real" class="scroll-mt-28 mt-16 border-t border-stone-800 pt-12">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <h2 class="font-headline text-2xl font-bold uppercase text-white md:text-3xl">
              Panell temps real
            </h2>
            <p class="mt-2 font-body text-sm text-stone-500">
              Reserves actives, ocupació per sessió i imports (dades del servidor).
            </p>
          </div>
          <button
            type="button"
            class="border border-stone-600 px-4 py-2 font-label text-[10px] font-bold uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-primary disabled:opacity-40"
            :disabled="carregantPanell"
            @click="carregarPanellTempsReal"
          >
            {{ carregantPanell ? 'Actualitzant…' : 'Actualitzar' }}
          </button>
        </div>

        <div v-if="panellTempsReal" class="mt-8 space-y-6">
          <div class="flex flex-wrap gap-8 border border-stone-800 bg-black/30 px-6 py-4">
            <div>
              <p class="font-label text-[9px] uppercase tracking-widest text-stone-500">
                Reserves temporals actives
              </p>
              <p class="font-headline text-2xl text-primary">
                {{ panellTempsReal.reserves_actives_total }}
              </p>
            </div>
            <div>
              <p class="font-label text-[9px] uppercase tracking-widest text-stone-500">
                Usuaris amb reserva activa
              </p>
              <p class="font-headline text-2xl text-white">
                {{ panellTempsReal.usuaris_amb_reserva_activa }}
              </p>
            </div>
          </div>

          <div class="overflow-x-auto border border-stone-800 bg-black/20">
            <table class="w-full min-w-[720px] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-stone-800 bg-stone-950/90">
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Sessió</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Pel·lícula</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Venuts</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Res. temp.</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">% ocup.</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Import sessió</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in panellTempsReal.per_sessio"
                  :key="'pt-' + row.sessio_id"
                  class="border-b border-stone-800/80"
                >
                  <td class="px-3 py-2 font-mono text-xs text-stone-400">{{ row.sessio_id }}</td>
                  <td class="max-w-[180px] px-3 py-2 text-stone-300">{{ row.peli_titol }}</td>
                  <td class="px-3 py-2 text-white">{{ row.places_venudes }} / {{ row.capacitat_sala }}</td>
                  <td class="px-3 py-2 text-amber-200/90">{{ row.seients_reservats_temporalment }}</td>
                  <td class="px-3 py-2 text-stone-400">{{ row.percentatge_ocupacio_vendes }}%</td>
                  <td class="px-3 py-2 text-primary">{{ row.import_total_sessio_eur }} €</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <p v-else-if="!carregantPanell" class="mt-6 text-sm text-stone-600">
          No s’han pogut carregar les mètriques.
        </p>
      </section>

      <section id="informes" class="scroll-mt-28 mt-16 border-t border-stone-800 pt-12">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
          <div>
            <h2 class="font-headline text-2xl font-bold uppercase text-white md:text-3xl">
              Informes
            </h2>
            <p class="mt-2 font-body text-sm text-stone-500">
              Recaptació total, per categoria i evolució diària (30 dies).
            </p>
          </div>
          <button
            type="button"
            class="border border-stone-600 px-4 py-2 font-label text-[10px] font-bold uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-primary disabled:opacity-40"
            :disabled="carregantInformes"
            @click="carregarInformesResum"
          >
            {{ carregantInformes ? 'Carregant…' : 'Actualitzar' }}
          </button>
        </div>

        <div v-if="informesResum" class="mt-8 space-y-8">
          <p class="font-body text-stone-300">
            Recaptació total:
            <span class="font-headline text-xl text-primary">{{ informesResum.recaptacio_total_eur }} €</span>
          </p>

          <div class="overflow-x-auto border border-stone-800 bg-black/20">
            <table class="w-full min-w-[400px] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-stone-800 bg-stone-950/90">
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Categoria</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Unitats</th>
                  <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Total</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="cat in informesResum.per_categoria"
                  :key="'cat-' + cat.categoria"
                  class="border-b border-stone-800/80"
                >
                  <td class="px-3 py-2 text-white">{{ cat.categoria }}</td>
                  <td class="px-3 py-2 text-stone-400">{{ cat.unitats }}</td>
                  <td class="px-3 py-2 text-primary">{{ cat.total_eur }} €</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div>
            <h3 class="font-headline mb-4 text-sm uppercase tracking-wider text-stone-400">
              Evolució diària (30 dies)
            </h3>
            <div class="overflow-x-auto border border-stone-800 bg-black/20">
              <table class="w-full min-w-[360px] border-collapse text-left text-sm">
                <thead>
                  <tr class="border-b border-stone-800 bg-stone-950/90">
                    <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Data</th>
                    <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Compres</th>
                    <th class="px-3 py-3 font-label text-[9px] uppercase tracking-widest text-stone-500">Import</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="ev in informesResum.evolucio_diaria_30_dies"
                    :key="'ev-' + ev.data"
                    class="border-b border-stone-800/80"
                  >
                    <td class="px-3 py-2 font-mono text-xs text-stone-400">{{ ev.data }}</td>
                    <td class="px-3 py-2 text-white">{{ ev.compres }}</td>
                    <td class="px-3 py-2 text-stone-300">{{ ev.import_eur }} €</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p
              v-if="!informesResum.evolucio_diaria_30_dies?.length"
              class="mt-4 text-sm text-stone-600"
            >
              Encara no hi ha compres en aquest període.
            </p>
          </div>
        </div>
        <p v-else-if="!carregantInformes" class="mt-6 text-sm text-stone-600">
          No s’han pogut carregar els informes.
        </p>
      </section>

      <section class="mt-16 border-t border-stone-800 pt-12">
        <h2 class="font-headline text-2xl font-bold uppercase text-white md:text-3xl">
          Sessions (passis)
        </h2>
        <p class="mt-2 font-body text-sm text-stone-500">
          Selecciona una pel·lícula per veure i afegir passis (sala i data).
        </p>

        <label class="mt-8 block max-w-xl">
          <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Pel·lícula</span>
          <select
            v-model="peliSessioId"
            :disabled="guardantPeli || esborrant"
            class="mt-2 w-full border border-stone-700 bg-stone-950 px-4 py-3 font-body text-white focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
          >
            <option value="">
              — Tria una pel·lícula —
            </option>
            <option v-for="p in pelis" :key="'sess-' + p.id" :value="String(p.id)">
              {{ p.titol }}
            </option>
          </select>
        </label>

        <template v-if="peliSessioId">
          <div class="mt-6">
            <button
              type="button"
              class="border border-white/80 bg-transparent px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-white transition hover:bg-white hover:text-black disabled:cursor-not-allowed disabled:opacity-40"
              :disabled="guardantSessio || esborrantSessio || !sales?.length"
              @click="novaSessio"
            >
              Nova sessió
            </button>
          </div>

          <div
            v-if="mostraFormSessio"
            class="mt-8 border border-stone-800 bg-surface-container/90 p-6 md:p-8"
          >
            <h3 class="font-headline mb-6 text-lg font-bold uppercase text-white">
              {{ editantSessioId ? 'Editar sessió' : 'Nova sessió' }}
            </h3>
            <div class="grid gap-6 md:max-w-xl">
              <label class="block">
                <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Sala</span>
                <select
                  v-model="formSessio.sala_id"
                  :disabled="guardantSessio"
                  class="mt-2 w-full border border-stone-700 bg-stone-950 px-4 py-3 text-white focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
                  <option v-for="s in sales" :key="'sala-' + s.id" :value="s.id">
                    {{ s.nom }} ({{ s.capacitat }} places)
                  </option>
                </select>
              </label>
              <label class="block">
                <span class="font-label text-[10px] uppercase tracking-widest text-stone-500">Data i hora</span>
                <input
                  v-model="formSessio.data_hora"
                  type="datetime-local"
                  :disabled="guardantSessio"
                  class="mt-2 w-full border border-stone-700 bg-stone-950 px-4 py-3 text-white focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                >
              </label>
            </div>
            <div class="mt-8 flex flex-wrap gap-3">
              <button
                type="button"
                class="border border-white bg-white px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-black transition hover:bg-primary hover:text-on-primary-fixed disabled:opacity-50"
                :disabled="guardantSessio"
                @click="desarSessio"
              >
                {{ guardantSessio ? 'Desant…' : 'Desar sessió' }}
              </button>
              <button
                type="button"
                class="border border-stone-600 px-6 py-3 font-headline text-xs font-bold uppercase tracking-wider text-stone-400 transition hover:text-white disabled:opacity-50"
                :disabled="guardantSessio"
                @click="cancelSessio"
              >
                Cancel·lar
              </button>
            </div>
          </div>

          <div v-if="carregantSessions" class="mt-8 text-sm text-stone-500">
            Carregant sessions…
          </div>
          <div v-else class="mt-8 overflow-x-auto border border-stone-800 bg-black/20">
            <table class="w-full min-w-[600px] border-collapse text-left text-sm">
              <thead>
                <tr class="border-b border-stone-800 bg-stone-950/90">
                  <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">ID</th>
                  <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Sala</th>
                  <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Data i hora</th>
                  <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Butaques lliures</th>
                  <th class="px-4 py-4 font-label text-[10px] uppercase tracking-widest text-stone-500">Accions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="s in sessioList"
                  :key="'sessio-' + s.id"
                  class="border-b border-stone-800/80 transition hover:bg-stone-900/40"
                >
                  <td class="px-4 py-3 font-mono text-xs text-stone-400">{{ s.id }}</td>
                  <td class="px-4 py-3 text-white">{{ s.sala_nom }}</td>
                  <td class="px-4 py-3 text-stone-300">{{ s.data_hora }}</td>
                  <td class="px-4 py-3 text-primary">{{ s.aforo_disponible }}</td>
                  <td class="px-4 py-3">
                    <button
                      type="button"
                      class="mr-2 border border-stone-600 px-3 py-1.5 font-label text-[10px] font-bold uppercase tracking-wider text-stone-300 transition hover:border-primary hover:text-primary disabled:opacity-40"
                      :disabled="guardantSessio || esborrantSessio"
                      @click="editarSessio(s)"
                    >
                      Editar
                    </button>
                    <button
                      type="button"
                      class="border border-red-900/60 bg-red-950/30 px-3 py-1.5 font-label text-[10px] font-bold uppercase tracking-wider text-red-300 transition hover:bg-red-950/60 disabled:opacity-40"
                      :disabled="guardantSessio || esborrantSessio"
                      @click="esborrarSessio(s.id)"
                    >
                      {{ esborrantSessio ? '…' : 'Esborrar' }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div
            v-if="!carregantSessions && !sessioList?.length"
            class="mt-6 border border-dashed border-stone-700 py-10 text-center text-sm text-stone-500"
          >
            Cap sessió per aquesta pel·lícula. Crea’n una amb «Nova sessió».
          </div>
        </template>
      </section>
    </main>

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
        to="/admin"
        class="flex flex-col items-center gap-1"
        :class="route.path === '/admin' && route.hash !== '#temps-real' ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">dashboard</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Panell</span>
      </NuxtLink>
      <NuxtLink
        to="/admin#temps-real"
        class="flex flex-col items-center gap-1"
        :class="route.hash === '#temps-real' ? 'text-primary' : 'text-stone-400'"
      >
        <span class="material-symbols-outlined">monitoring</span>
        <span class="text-[8px] font-bold uppercase tracking-widest">Temps real</span>
      </NuxtLink>
    </div>
  </div>
</template>
