<script setup>
const { data: movies, pending, error } = await useFetch('http://localhost:8001/api/esdeveniments')
</script>

<template>
  <div style="background-color: white; margin: 0; font-family: sans-serif;">
    <div v-if="pending">Carregant...</div>
    <div v-else-if="error">Error carregant dades</div>
    <div v-else style="display: flex; gap: 40px; padding: 50px;">
      <div v-for="movie in movies" :key="movie.titol" style="width: 150px;">
        <img :src="movie.imatge_url" alt="Movie Poster" style="width: 150px; height: 225px; object-fit: cover; display: block;">
        <p style="margin: 10px 0 5px 0; font-size: 14px;">{{ movie.titol }}</p>
        <p style="margin: 0; font-size: 12px;">Seats available: {{ movie.seats_available }}</p>
      </div>
    </div>
  </div>
</template>

<style>
body {
  margin: 0;
  background-color: white;
}
</style>
