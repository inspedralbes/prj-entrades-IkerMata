<script setup>
definePageMeta({
  layout: 'default'
})

const baseURL = useApiBase()
const authStore = useAuthStore()
const router = useRouter()
const { onCatalogActualitzat } = useSocket()

const { data: pelis, refresh: refreshPelis } = await useFetch('/peliculas', { baseURL })

const sales = ref([])

let offCatalogActualitzat = () => {}

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
  })
})

onUnmounted(() => {
  offCatalogActualitzat()
})

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
</script>

<template>
  <div class="admin">
    <h1>Panell d'Administració</h1>
    
    <div class="actions">
      <button type="button" :disabled="guardantPeli || esborrant" @click="novaPeli">Nou Pel·lícula</button>
    </div>

    <div v-if="mostraForm" class="formulari">
      <h2>{{ editantId ? 'Editar' : 'Nova' }} Pel·lícula</h2>
      <label>
        Títol:
        <input v-model="formulari.titol" type="text" required>
      </label>
      <label>
        Descripció:
        <textarea v-model="formulari.descripcio" required></textarea>
      </label>
      <label>
        Imatge URL:
        <input v-model="formulari.imatge_url" type="url">
      </label>
      <label>
        Durada (minuts):
        <input v-model="formulari.durada_minuts" type="number" min="1">
      </label>
      <div class="btns">
        <button type="button" :disabled="guardantPeli" @click="desarPeli">
          {{ guardantPeli ? 'Desant…' : 'Desar' }}
        </button>
        <button type="button" :disabled="guardantPeli" @click="cancel" class="cancel">Cancel·lar</button>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Títol</th>
          <th>Descripció</th>
          <th>Durada</th>
          <th>Estat</th>
          <th>Accions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="p in pelis" :key="p.id">
          <td>{{ p.id }}</td>
          <td>{{ p.titol }}</td>
          <td>{{ p.descripcio?.substring(0, 50) }}...</td>
          <td>{{ p.durada_minuts }} min</td>
          <td>{{ p.estat }}</td>
          <td>
            <button type="button" :disabled="guardantPeli || esborrant" @click="editarPeli(p)">Editar</button>
            <button type="button" :disabled="guardantPeli || esborrant" @click="esborrarPeli(p.id)" class="del">
              {{ esborrant ? '…' : 'Esborrar' }}
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="!pelis?.length" class="buit">
      No hi ha pel·lícules.
    </div>

    <section class="bloc-sessions">
      <h2>Sessions (passis)</h2>
      <p class="hint">Selecciona una pel·lícula per veure i afegir passis (sala i data).</p>
      <label class="sel-peli">
        Pel·lícula
        <select v-model="peliSessioId" :disabled="guardantPeli || esborrant">
          <option value="">— Tria una pel·lícula —</option>
          <option v-for="p in pelis" :key="'sess-' + p.id" :value="String(p.id)">{{ p.titol }}</option>
        </select>
      </label>

      <template v-if="peliSessioId">
        <div class="actions sessio-actions">
          <button
            type="button"
            :disabled="guardantSessio || esborrantSessio || !sales?.length"
            @click="novaSessio"
          >
            Nova sessió
          </button>
        </div>

        <div v-if="mostraFormSessio" class="formulari">
          <h3>{{ editantSessioId ? 'Editar sessió' : 'Nova sessió' }}</h3>
          <label>
            Sala
            <select v-model="formSessio.sala_id" :disabled="guardantSessio">
              <option v-for="s in sales" :key="'sala-' + s.id" :value="s.id">{{ s.nom }} ({{ s.capacitat }} places)</option>
            </select>
          </label>
          <label>
            Data i hora
            <input v-model="formSessio.data_hora" type="datetime-local" :disabled="guardantSessio">
          </label>
          <div class="btns">
            <button type="button" :disabled="guardantSessio" @click="desarSessio">
              {{ guardantSessio ? 'Desant…' : 'Desar sessió' }}
            </button>
            <button type="button" :disabled="guardantSessio" class="cancel" @click="cancelSessio">Cancel·lar</button>
          </div>
        </div>

        <div v-if="carregantSessions" class="estat">Carregant sessions…</div>
        <table v-else class="taula-sessions">
          <thead>
            <tr>
              <th>ID</th>
              <th>Sala</th>
              <th>Data i hora</th>
              <th>Butaques lliures</th>
              <th>Accions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="s in sessioList" :key="'sessio-' + s.id">
              <td>{{ s.id }}</td>
              <td>{{ s.sala_nom }}</td>
              <td>{{ s.data_hora }}</td>
              <td>{{ s.aforo_disponible }}</td>
              <td>
                <button
                  type="button"
                  :disabled="guardantSessio || esborrantSessio"
                  @click="editarSessio(s)"
                >
                  Editar
                </button>
                <button
                  type="button"
                  class="del"
                  :disabled="guardantSessio || esborrantSessio"
                  @click="esborrarSessio(s.id)"
                >
                  {{ esborrantSessio ? '…' : 'Esborrar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-if="!carregantSessions && !sessioList?.length" class="buit">
          Cap sessió per aquesta pel·lícula. Crea’n una amb «Nova sessió».
        </div>
      </template>
    </section>
  </div>
</template>

<style scoped>
.admin {
  padding: 20px;
  max-width: 1000px;
  margin: 0 auto;
}

h1 { margin-bottom: 20px; }

.actions { margin-bottom: 20px; }

.actions button {
  background: #28a745;
  color: white;
  padding: 10px 20px;
  border: none;
  cursor: pointer;
  border-radius: 4px;
}

.formulari {
  background: #f5f5f5;
  padding: 20px;
  margin-bottom: 20px;
  border-radius: 8px;
}

.formulari label {
  display: block;
  margin-bottom: 12px;
}

.formulari input,
.formulari textarea {
  width: 100%;
  padding: 8px;
  box-sizing: border-box;
}

.formulari textarea {
  height: 80px;
}

.btns { margin-top: 16px; }

.btns button {
  margin-right: 10px;
  padding: 8px 16px;
  cursor: pointer;
}

.btns .cancel {
  background: #6c757d;
  color: white;
  border: none;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

th { background: #f8f9fa; }

td button {
  margin-right: 8px;
  padding: 6px 12px;
  cursor: pointer;
}

td button:disabled,
.btns button:disabled,
.actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

td .del {
  background: #dc3545;
  color: white;
  border: none;
}

.buit {
  text-align: center;
  padding: 40px;
  color: #666;
}

.bloc-sessions {
  margin-top: 48px;
  padding-top: 24px;
  border-top: 1px solid #ddd;
}

.bloc-sessions h2 {
  margin-bottom: 8px;
}

.hint {
  color: #666;
  font-size: 0.95rem;
  margin-bottom: 16px;
}

.sel-peli {
  display: block;
  margin-bottom: 16px;
}

.sel-peli select {
  margin-left: 8px;
  min-width: 220px;
  padding: 8px;
}

.sessio-actions {
  margin-bottom: 16px;
}

.taula-sessions {
  margin-top: 12px;
}

.estat {
  padding: 12px;
  color: #555;
}

.bloc-sessions h3 {
  margin-top: 0;
}
</style>