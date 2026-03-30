<script setup>
const baseURL = process.client ? 'http://localhost:8001/api' : 'http://web/api'

const { data: movies, pending, error } = await useFetch('/peliculas', { 
  baseURL,
  timeout: 5000
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
          <p>Seients disponibles: {{ movie.seats_available }}</p>
        </NuxtLink>
      </div>
    </main>
  </div>
</template>

<style scoped>
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
