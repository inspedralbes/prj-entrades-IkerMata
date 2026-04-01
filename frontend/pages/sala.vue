<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()

const peliId = route.query.peli

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: sessions } = await useFetch(peliId ? `/peliculas/${peliId}/sesiones` : null, {
  baseURL,
  immediate: !!peliId
})

function anarAButaques(sessioId) {
  navigateTo(`/butaques?peli=${peliId}&sessio=${sessioId}`)
}
</script>

<template>
  <div>
    <main>
      <div v-if="!peliId" class="missatge">
        <p>No s'ha triat cap pel·lícula.</p>
        <NuxtLink to="/">Tornar a la cartellera</NuxtLink>
      </div>

      <div v-else-if="peli">
        <p class="tornar">
          <NuxtLink to="/">← Cartellera</NuxtLink>
        </p>
        <h1>{{ peli.titol }}</h1>
        <img :src="peli.imatge_url" :alt="peli.titol">

        <h2>Sessions</h2>
        <p class="hint">Tria una sessió per seleccionar les butaques.</p>
        <div class="sessions">
          <button
            v-for="sessio in sessions"
            :key="sessio.id"
            type="button"
            @click="anarAButaques(sessio.id)"
          >
            {{ sessio.sala_nom }} - {{ sessio.data_hora }}
          </button>
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

.hint {
  color: #555;
  margin-bottom: 12px;
}

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
  border-radius: 4px;
}

.sessions button:hover {
  background: #e8e8e8;
  border-color: #007bff;
}
</style>
