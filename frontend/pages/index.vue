<script setup>
const baseURL = process.client ? 'http://localhost:8001/api' : 'http://web/api'

const { data: movies, pending, error } = await useFetch('/peliculas', { 
  baseURL,
  timeout: 5000
})
</script>

<template>
  <div>
    <h1>Cartelera</h1>
    <div v-if="pending">Carregant...</div>
    <div v-else-if="error">Error carregant dades</div>
    <div v-else>
      <div v-for="movie in movies" :key="movie.titol">
        <img :src="movie.imatge_url" :alt="movie.titol">
        <h2>{{ movie.titol }}</h2>
        <p>Seients disponibles: {{ movie.seats_available }}</p>
      </div>
    </div>
  </div>
</template>
