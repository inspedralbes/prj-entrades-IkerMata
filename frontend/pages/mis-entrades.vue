<script setup>
definePageMeta({
  middleware: 'auth',
  layout: 'default'
})

const baseURL = useApiBase()
const auth = useAuth()

const { data: entrades, pending, error } = await useFetch('/entrades', {
  baseURL,
  headers: auth.capcalarsAutenticacio()
})
</script>

<template>
  <div class="pagina">
    <h1>Mis entrades</h1>

    <div v-if="pending" class="estat">Carregant…</div>
    <div v-else-if="error" class="estat error">No s'han pogut carregar les entrades.</div>
    <div v-else-if="!entrades || entrades.length === 0" class="estat">
      Encara no tens cap entrada comprada.
    </div>
    <ul v-else class="llista">
      <li v-for="e in entrades" :key="e.id" class="targeta">
        <strong>{{ e.peli_titol }}</strong>
        <span class="meta">{{ e.sala_nom }} · {{ e.data_hora }}</span>
        <span class="meta">Seient {{ e.seient }} — {{ e.preu_pagat }}€</span>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.pagina {
  max-width: 560px;
  margin: 0 auto;
  padding: 24px 16px;
}

.estat {
  margin-top: 16px;
  color: #555;
}

.estat.error {
  color: #c00;
}

.llista {
  list-style: none;
  padding: 0;
  margin: 24px 0 0;
}

.targeta {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 12px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.meta {
  font-size: 0.9rem;
  color: #666;
}
</style>
