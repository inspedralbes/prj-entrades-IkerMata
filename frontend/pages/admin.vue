<script setup>
definePageMeta({
  layout: 'default'
})

const config = useRuntimeConfig()
const baseURL = useApiBase()
const authStore = useAuthStore()
const router = useRouter()

onMounted(async () => {
  await authStore.syncUsuariSiCal(baseURL)
  if (!authStore.user || authStore.user.rol !== 'admin') {
    router.push('/')
  }
})

const { data: pelis, refresh: refreshPelis } = await useFetch('/peliculas', { baseURL })

const mostraForm = ref(false)
const editantId = ref(null)
const formulari = ref({ titol: '', descripcio: '', imatge_url: '', durada_minuts: 60 })

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
  const metode = editantId.value ? 'PUT' : 'POST'
  const url = editantId.value 
    ? `${baseURL.value}/peliculas/${editantId.value}`
    : `${baseURL.value}/peliculas`
  
  await $fetch(url, {
    method: metode,
    headers: authStore.capcalarsAutenticacio(),
    body: formulari.value
  })
  
  mostraForm.value = false
  refreshPelis()
}

async function esborrarPeli(id) {
  if (!confirm('Segur que vols eliminar aquesta pel·lícula?')) return
  await $fetch(`${baseURL.value}/peliculas/${id}`, {
    method: 'DELETE',
    headers: authStore.capcalarsAutenticacio()
  })
  refreshPelis()
}

function cancel() {
  mostraForm.value = false
}
</script>

<template>
  <div class="admin">
    <h1>Panell d'Administració</h1>
    
    <div class="actions">
      <button @click="novaPeli">Nou Pel·lícula</button>
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
        <button @click="desarPeli">Desar</button>
        <button @click="cancel" class="cancel">Cancel·lar</button>
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
            <button @click="editarPeli(p)">Editar</button>
            <button @click="esborrarPeli(p.id)" class="del">Esborrar</button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="!pelis?.length" class="buit">
      No hi ha pel·lícules.
    </div>
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
</style>