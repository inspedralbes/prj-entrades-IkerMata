<script setup>
const config = useRuntimeConfig()
const baseURL = process.server ? 'http://web/api' : 'http://localhost:8001/api'

const { data: movies, pending, error } = await useFetch('/peliculas', { baseURL })
</script>

<template>
  <div class="app-container">
    <header class="header">
      <h1 class="title">Cinema<span>Elite</span></h1>
      <p class="subtitle">Descobreix les millors estrenes</p>
    </header>

    <main class="main-content">
      <div v-if="pending" class="loading-state">
        <div class="spinner"></div>
        <p>Carregant cartellera...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <div class="error-icon">⚠️</div>
        <p>No s'han pogut carregar les pel·lícules.</p>
        <button @click="refresh" class="retry-btn">Tornar a intentar</button>
      </div>

      <div v-else class="movie-grid">
        <div v-for="movie in movies" :key="movie.titol" class="movie-card">
          <div class="image-wrapper">
            <img :src="movie.imatge_url" :alt="movie.titol" class="movie-poster">
            <div class="card-overlay">
              <button class="buy-btn">Comprar Entrades</button>
            </div>
          </div>
          <div class="movie-info">
            <h2 class="movie-title">{{ movie.titol }}</h2>
            <div class="movie-meta">
              <span class="seats-badge">
                <span class="icon">🎟️</span>
                {{ movie.seats_available }} disponibles
              </span>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap');

:root {
  --primary: #6366f1;
  --primary-hover: #4f46e5;
  --bg-dark: #0f172a;
  --card-bg: #1e293b;
  --text-white: #f8fafc;
  --text-dim: #94a3b8;
  --accent: #f43f5e;
}

body {
  background-color: var(--bg-dark);
  color: var(--text-white);
  font-family: 'Outfit', sans-serif;
  margin: 0;
  -webkit-font-smoothing: antialiased;
}

.app-container {
  min-height: 100vh;
  padding: 40px 20px;
}

.header {
  text-align: center;
  margin-bottom: 60px;
}

.title {
  font-size: 3rem;
  font-weight: 700;
  margin: 0;
  letter-spacing: -0.02em;
}

.title span {
  color: var(--primary);
}

.subtitle {
  color: var(--text-dim);
  font-size: 1.1rem;
  margin-top: 10px;
}

.movie-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 32px;
  max-width: 1200px;
  margin: 0 auto;
}

.movie-card {
  background: var(--card-bg);
  border-radius: 20px;
  overflow: hidden;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid rgba(255, 255, 255, 0.05);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.movie-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
  border-color: rgba(99, 102, 241, 0.3);
}

.image-wrapper {
  position: relative;
  aspect-ratio: 2/3;
  overflow: hidden;
}

.movie-poster {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s ease;
}

.movie-card:hover .movie-poster {
  transform: scale(1.05);
}

.card-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(15, 23, 42, 0.9), transparent);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.movie-card:hover .card-overlay {
  opacity: 1;
}

.buy-btn {
  background: var(--primary);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, background 0.2s ease;
}

.buy-btn:hover {
  background: var(--primary-hover);
  transform: scale(1.05);
}

.movie-info {
  padding: 20px;
}

.movie-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 12px 0;
}

.seats-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(99, 102, 241, 0.1);
  color: var(--primary);
  padding: 6px 12px;
  border-radius: 99px;
  font-size: 0.875rem;
  font-weight: 500;
}

.loading-state, .error-state {
  text-align: center;
  padding: 100px 0;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(99, 102, 241, 0.1);
  border-top-color: var(--primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.retry-btn {
  background: transparent;
  border: 1px solid var(--text-dim);
  color: var(--text-dim);
  padding: 8px 16px;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 15px;
}

.retry-btn:hover {
  border-color: var(--text-white);
  color: var(--text-white);
}
</style>
