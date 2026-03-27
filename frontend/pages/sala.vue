<script setup>
const route = useRoute()
const baseURL = process.client ? 'http://localhost:8001/api' : 'http://web/api'

const peliId = route.query.peli
const sessioId = route.query.sessio

const { data: peli } = await useFetch(`/peliculas/${peliId}`, { baseURL })
const { data: sessions } = await useFetch(`/peliculas/${peliId}/sesiones`, { baseURL })
const { data: seients, pending: seatsLoading } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, { 
  baseURL,
  immediate: !!sessioId
})

const selectedSeients = ref([])
const selectedSessio = ref(sessioId ? parseInt(sessioId) : null)

function toggleSeient(seient) {
  const index = selectedSeients.value.findIndex(s => s.id === seient.id)
  if (index > -1) {
    selectedSeients.value.splice(index, 1)
  } else {
    selectedSeients.value.push(seient)
  }
}

function selectSessio(id) {
  selectedSessio.value = id
  selectedSeients.value = []
  navigateTo(`/sala?peli=${peliId}&sessio=${id}`)
}

function getPreu(categoria) {
  const preus = { 'VIP': 50, 'Platea': 35, 'General': 20 }
  return preus[categoria] || 0
}

const totalPreu = computed(() => {
  return selectedSeients.value.reduce((sum, s) => sum + getPreu(s.categoria), 0)
})
</script>

<template>
  <div>
    <main>
      <div v-if="peli">
        <h1>{{ peli.titol }}</h1>
        <img :src="peli.imatge_url" :alt="peli.titol">
        
        <h2>Sessions</h2>
        <div class="sessions">
          <button 
            v-for="sessio in sessions" 
            :key="sessio.id"
            :class="{ active: selectedSessio === sessio.id }"
            @click="selectSessio(sessio.id)"
          >
            {{ sessio.sala_nom }} - {{ sessio.data_hora }}
          </button>
        </div>

        <div v-if="selectedSessio && seients" class="sala-container">
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
            <div class="legend-item"><span class="dot" style="background:#4169E1"></span> Platea (35€)</div>
            <div class="legend-item"><span class="dot" style="background:#228B22"></span> General (20€)</div>
          </div>

          <div v-if="selectedSeients.length > 0" class="resum">
            <h3>Seients seleccionats:</h3>
            <p>{{ selectedSeients.map(s => s.fila + s.numero).join(', ') }}</p>
            <p class="total">Total: {{ totalPreu }}€</p>
            <button class="comprar-btn">Comprar entrades</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.sessions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin: 20px 0;
}

.sessions button {
  padding: 10px 20px;
  border: 1px solid #ccc;
  background: #f5f5f5;
  cursor: pointer;
}

.sessions button.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
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
