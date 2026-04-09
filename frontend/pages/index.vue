<script setup>
const baseURL = useApiBase()
const { joinPelicula, onAforoActualitzat, ensureSocket } = useSocket()

const { data: moviesData, pending, error } = await useFetch('/peliculas', {
  baseURL,
  timeout: 5000,
  key: 'cartellera-peliculas'
})

const movies = shallowRef([])

watch(
  moviesData,
  (v) => {
    if (v && Array.isArray(v)) {
      movies.value = v.map((m) => ({ ...m }))
    } else {
      movies.value = []
    }
  },
  { immediate: true }
)

let offAforoActualitzat = () => {}
let offSocketConnect = () => {}

function joinAllPelicules() {
  for (const m of movies.value) {
    joinPelicula(m.id)
  }
}

onMounted(() => {
  offAforoActualitzat = onAforoActualitzat((data) => {
    if (data.pelicula_id === undefined || data.hi_ha_disponibilitat === undefined) {
      return
    }
    const pid = Number(data.pelicula_id)
    const idx = movies.value.findIndex((m) => Number(m.id) === pid)
    if (idx === -1) {
      return
    }
    movies.value = movies.value.map((m, i) =>
      i === idx ? { ...m, hi_ha_disponibilitat: data.hi_ha_disponibilitat } : m
    )
  })

  const socket = ensureSocket()
  if (socket) {
    socket.on('connect', joinAllPelicules)
    offSocketConnect = () => socket.off('connect', joinAllPelicules)
    joinAllPelicules()
  }
})

onUnmounted(() => {
  offAforoActualitzat()
  offSocketConnect()
})
</script>

<template>
  <div>
    <main>
      <h1>Cartelera</h1>
      <div v-if="pending">Carregant...</div>
      <div v-else-if="error">Error carregant dades</div>
      <div v-else class="movies-grid">
        <NuxtLink 
          v-for="movie in movies" 
          :key="movie.id"
          :to="`/sala?peli=${movie.id}`"
          class="movie-card"
        >
          <img :src="movie.imatge_url" :alt="movie.titol">
          <h2>{{ movie.titol }}</h2>
          <div class="status">
            <span :class="['dot', movie.hi_ha_disponibilitat ? 'available' : 'full']"></span>
            {{ movie.hi_ha_disponibilitat ? 'Disponible' : 'Sessió completa' }}
          </div>
        </NuxtLink>
      </div>
    </main>
  </div>
</template>

<style scoped>
.status {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.9em;
  margin-top: 8px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
}

.available { background-color: #28a745; }
.full { background-color: #dc3545; }

.movies-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
  padding: 20px;
}

.movie-card {
  border: 1px solid #ddd;
  padding: 10px;
  text-decoration: none;
  color: inherit;
  transition: transform 0.2s;
}

.movie-card:hover {
  transform: scale(1.05);
}

.movie-card img {
  width: 100%;
  height: 300px;
  object-fit: cover;
}
</style>
