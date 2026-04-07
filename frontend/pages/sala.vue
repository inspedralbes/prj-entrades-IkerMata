<script setup>
definePageMeta({
  layout: 'blank'
})

const route = useRoute()
const baseURL = useApiBase()
const { joinPelicula, onAforoActualitzat } = useSocket()

const peliId = route.query.peli

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: sessions } = await useFetch(peliId ? `/peliculas/${peliId}/sesiones` : null, {
  baseURL,
  immediate: !!peliId
})

onMounted(() => {
  if (peliId) {
    joinPelicula(peliId)
  }

  onAforoActualitzat((data) => {
    if (data.sessio_id && sessions.value) {
      const sessio = sessions.value.find(s => s.id === data.sessio_id)
      if (sessio) {
        sessio.aforo_disponible = data.aforo_disponible
      }
    }
  })
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
            :disabled="sessio.aforo_disponible <= 0"
            :class="{ 'full-btn': sessio.aforo_disponible <= 0 }"
            @click="anarAButaques(sessio.id)"
          >
            <div class="sessio-info">
              <span class="sala">{{ sessio.sala_nom }}</span>
              <span class="hora">{{ sessio.data_hora }}</span>
            </div>
            <div class="aforo-info">
              <span v-if="sessio.aforo_disponible > 0" class="lluny">
                {{ sessio.aforo_disponible }} butaques lliures
              </span>
              <span v-else class="full-text">Sessió completa</span>
            </div>
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
  padding: 12px 24px;
  border: 1px solid #ccc;
  background: #f8f9fa;
  cursor: pointer;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  min-width: 180px;
  transition: all 0.2s;
}

.sessions button:hover:not(:disabled) {
  background: #e9ecef;
  border-color: #007bff;
  transform: translateY(-2px);
}

.sessions button:disabled {
  cursor: not-allowed;
  opacity: 0.7;
}

.full-btn {
  background: #fff5f5 !important;
  border-color: #feb2b2 !important;
}

.sessio-info {
  font-weight: bold;
  margin-bottom: 4px;
}

.aforo-info {
  font-size: 0.85em;
  color: #666;
}

.full-text {
  color: #c53030;
  font-weight: bold;
}
</style>
