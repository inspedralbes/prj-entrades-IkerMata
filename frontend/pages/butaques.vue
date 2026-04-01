<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()

const peliId = route.query.peli
const sessioId = route.query.sessio

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: seients, pending: seatsLoading } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId
})

const selectedSeients = ref([])

function toggleSeient(seient) {
  const index = selectedSeients.value.findIndex(s => s.id === seient.id)
  if (index > -1) {
    selectedSeients.value.splice(index, 1)
  } else {
    selectedSeients.value.push(seient)
  }
}

function getPreu(categoria) {
  if (categoria === 'VIP') return 50
  return 20
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
                reservat: seient.reservat
              }"
              :style="{ backgroundColor: seient.color }"
              @click="!seient.reservat && toggleSeient(seient)"
            >
              {{ seient.fila }}{{ seient.numero }}
            </div>
          </div>

          <div class="legend">
            <div class="legend-item"><span class="dot" style="background:#FFD700"></span> VIP (50€)</div>
            <div class="legend-item"><span class="dot" style="background:#4169E1"></span> Normal (20€)</div>
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
