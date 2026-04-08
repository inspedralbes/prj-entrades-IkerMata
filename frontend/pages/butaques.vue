<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const config = useRuntimeConfig()
const baseURL = useApiBase()
const gatewayURL = config.public.gatewayUrl
const authStore = useAuthStore()
const { ensureSocket, joinSessio } = useSocket()

const peliId = route.query.peli
const sessioId = route.query.sessio

function mateixaSessio(id) {
  return id != null && Number(id) === Number(sessioId)
}

function patchSeient(seientId, patch) {
  const list = seients.value
  if (!list) return
  const i = list.findIndex((s) => s.id === seientId)
  if (i === -1) return
  list[i] = { ...list[i], ...patch }
}

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: seients, pending: seatsLoading, refresh: refreshSeats } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId
})

const selectedSeients = ref([])
const reservaEnCurs = ref(false)

function onSeientSeleccionat(data) {
  if (!mateixaSessio(data.sessio_id) || !seients.value) return
  const jo = authStore.currentUserId ? String(authStore.currentUserId) : null
  const actor = data.usuari_id != null ? String(data.usuari_id) : ''
  if (jo && actor === jo) {
    return
  }
  if (!jo && selectedSeients.value.some((s) => s.id === data.seient_id)) {
    return
  }
  patchSeient(data.seient_id, { seleccionat_per_altre: true })
}

function onSeientAlliberat(data) {
  if (!mateixaSessio(data.sessio_id) || !seients.value) return
  patchSeient(data.seient_id, { seleccionat_per_altre: false })
}

function onCompraCreada(data) {
  if (!mateixaSessio(data.sessio_id) || !data.seient_ids || !seients.value) return
  const ids = new Set(data.seient_ids.map((id) => Number(id)))
  for (const id of ids) {
    patchSeient(id, { reservat: true, seleccionat_per_altre: false })
  }
  selectedSeients.value = selectedSeients.value.filter((s) => !ids.has(s.id))
}

function rejoinSalaAlConnectar() {
  if (sessioId) {
    joinSessio(sessioId)
  }
}

/** Allibera totes les reserves temporals de l'usuari en aquesta sessió (POST estat: false). */
async function alliberarReservesSeleccionades() {
  const seats = selectedSeients.value.slice()
  if (!sessioId || seats.length === 0) {
    return
  }
  await Promise.all(
    seats.map((seient) =>
      $fetch(`${gatewayURL}/api/reservar`, {
        method: 'POST',
        headers: authStore.capcalarsAutenticacio(),
        body: {
          sessioId,
          seientId: seient.id,
          estat: false
        }
      }).catch(() => {})
    )
  )
  selectedSeients.value = []
}

function vaAPagament(to) {
  const p = to.path || ''
  return p === '/pago' || p.startsWith('/pago/')
}

onBeforeRouteLeave(async (to) => {
  if (vaAPagament(to)) {
    return true
  }
  await alliberarReservesSeleccionades()
})

onMounted(async () => {
  await authStore.syncUsuariSiCal(baseURL)

  if (sessioId) {
    joinSessio(sessioId)
  }

  const socket = ensureSocket()
  if (!socket) return

  socket.on('connect', rejoinSalaAlConnectar)

  socket.on('seient-seleccionat', onSeientSeleccionat)
  socket.on('seient-alliberat', onSeientAlliberat)
  socket.on('compra-creada', onCompraCreada)
})

onUnmounted(() => {
  const socket = ensureSocket()
  if (!socket) return
  socket.off('connect', rejoinSalaAlConnectar)
  socket.off('seient-seleccionat', onSeientSeleccionat)
  socket.off('seient-alliberat', onSeientAlliberat)
  socket.off('compra-creada', onCompraCreada)
})

async function toggleSeient(seient) {
  if (reservaEnCurs.value) {
    return
  }
  const index = selectedSeients.value.findIndex(s => s.id === seient.id)
  const nouEstat = index === -1

  reservaEnCurs.value = true
  try {
    await $fetch(`${gatewayURL}/api/reservar`, {
      method: 'POST',
      headers: authStore.capcalarsAutenticacio(),
      body: {
        sessioId: sessioId,
        seientId: seient.id,
        estat: nouEstat
      }
    })

    if (nouEstat) {
      if (!selectedSeients.value.some((s) => s.id === seient.id)) {
        selectedSeients.value.push(seient)
      }
    } else {
      selectedSeients.value.splice(index, 1)
    }
  } catch (e) {
    if (e.response && e.response.status === 401) {
      await authStore.logout()
      navigateTo('/login')
    } else {
      alert(e.data?.error || 'No s\'ha pogut reservar el seient')
      refreshSeats()
    }
  } finally {
    reservaEnCurs.value = false
  }
}

function getPreu(categoria) {
  if (categoria === 'VIP') return 9.7
  return 6.7
}

const totalPreu = computed(() => {
  return selectedSeients.value.reduce((sum, s) => sum + getPreu(s.categoria), 0)
})

function anarAPagament() {
  const ids = selectedSeients.value.map(s => s.id).join(',')
  navigateTo({
    path: '/pago',
    query: {
      peli: peliId,
      sessio: String(sessioId),
      seients: ids
    }
  })
}

watch(
  () => route.query.sessio,
  () => {
    selectedSeients.value = []
  }
)
</script>

<template>
  <div>
    <main>
      <div v-if="!peliId || !sessioId" class="missatge">
        <p>Falta seleccionar pel·lícula o sessió.</p>
        <NuxtLink to="/">Tornar a la cartellera</NuxtLink>
      </div>

      <div v-else-if="peli">
        <p class="tornar">
          <NuxtLink :to="`/sala?peli=${peliId}`">← Tornar a les sessions</NuxtLink>
        </p>
        <h1>{{ peli.titol }}</h1>

        <div v-if="seatsLoading" class="missatge">Carregant seients...</div>

        <div v-else-if="seients" class="sala-container">
          <h2>Selecciona els teus seients</h2>

          <div class="screen">PANTALLA</div>

          <div class="seients">
            <div
              v-for="seient in seients"
              :key="seient.id"
              class="seient"
              :class="{
                selected: selectedSeients.some(s => s.id === seient.id),
                reservat: seient.reservat,
                'other-selected': seient.seleccionat_per_altre
              }"
              :style="{ backgroundColor: seient.color }"
              @click="!seient.reservat && !seient.seleccionat_per_altre && toggleSeient(seient)"
            >
              {{ seient.fila }}{{ seient.numero }}
            </div>
          </div>

          <div class="legend">
            <div class="legend-item"><span class="dot" style="background:#FFD700"></span> VIP (9,70€)</div>
            <div class="legend-item"><span class="dot" style="background:#4169E1"></span> Normal (6,70€)</div>
            <div class="legend-item"><span class="dot other-selected-dot"></span> Seleccionat per un altre</div>
          </div>

          <div v-if="selectedSeients.length > 0" class="resum">
            <h3>Seients seleccionats:</h3>
            <p>{{ selectedSeients.map(s => s.fila + s.numero).join(', ') }}</p>
            <p class="total">Total: {{ totalPreu }}€</p>
            <button class="comprar-btn" type="button" @click="anarAPagament">Comprar entrades</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.tornar {
  margin-bottom: 16px;
}

.tornar a {
  color: #007bff;
  text-decoration: none;
}

.tornar a:hover {
  text-decoration: underline;
}

.missatge {
  padding: 24px;
  text-align: center;
}

.sala-container {
  margin-top: 30px;
}

.screen {
  background: #333;
  color: white;
  text-align: center;
  padding: 20px;
  margin: 20px 0;
  border-radius: 5px;
}

.seients {
  display: grid;
  grid-template-columns: repeat(10, 1fr);
  gap: 8px;
  max-width: 500px;
  margin: 0 auto;
}

.seient {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  color: white;
  cursor: pointer;
  border-radius: 4px;
}

.seient.reservat {
  opacity: 0.3;
  cursor: not-allowed;
}

.seient.selected {
  outline: 3px solid red;
}

.seient.other-selected {
  background-color: #ffa500 !important; /* Taronja */
  opacity: 0.8;
  cursor: not-allowed;
  border: 2px dashed #ff4500;
}

.other-selected-dot {
  background-color: #ffa500;
}

.legend {
  display: flex;
  gap: 20px;
  justify-content: center;
  margin-top: 20px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
}

.dot {
  width: 20px;
  height: 20px;
  border-radius: 3px;
}

.resum {
  text-align: center;
  margin-top: 30px;
  padding: 20px;
  background: #f9f9f9;
}

.total {
  font-size: 24px;
  font-weight: bold;
}

.comprar-btn {
  background: #28a745;
  color: white;
  padding: 15px 30px;
  border: none;
  font-size: 18px;
  cursor: pointer;
  border-radius: 5px;
}
</style>
