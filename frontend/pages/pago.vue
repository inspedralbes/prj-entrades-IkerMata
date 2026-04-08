<script setup>
definePageMeta({
  layout: 'blank',
  middleware: 'auth'
})

const route = useRoute()
const baseURL = useApiBase()
const authStore = useAuthStore()

function primerQuery(val) {
  if (val == null) return undefined
  return Array.isArray(val) ? val[0] : val
}

const peliId = primerQuery(route.query.peli)
const sessioId = primerQuery(route.query.sessio)
const seientsParam = primerQuery(route.query.seients)

const seientIds = computed(() => {
  if (!seientsParam || typeof seientsParam !== 'string') return []
  return seientsParam.split(',').map(id => parseInt(id, 10)).filter(n => !Number.isNaN(n))
})

const { data: peli } = await useFetch(peliId ? `/peliculas/${peliId}` : null, { baseURL, immediate: !!peliId })
const { data: totsSeients } = await useFetch(sessioId ? `/sesiones/${sessioId}/asientos` : null, {
  baseURL,
  immediate: !!sessioId
})

const seleccionats = computed(() => {
  if (!totsSeients.value || !seientIds.value.length) return []
  const set = new Set(seientIds.value)
  return totsSeients.value.filter(s => set.has(s.id))
})

function getPreu(categoria) {
  if (categoria === 'VIP') return 50
  return 20
}

const totalPreu = computed(() => {
  return seleccionats.value.reduce((sum, s) => sum + getPreu(s.categoria), 0)
})

const form = reactive({
  email: '',
  nom: '',
  numeroTargeta: '',
  caducitat: '',
  cvv: ''
})

const enviant = ref(false)

function formatTargeta(v) {
  const digits = v.replace(/\D/g, '').slice(0, 16)
  return digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim()
}

function formatCaducitat(v) {
  const d = v.replace(/\D/g, '').slice(0, 4)
  if (d.length <= 2) return d
  return `${d.slice(0, 2)}/${d.slice(2)}`
}

watch(
  () => form.numeroTargeta,
  (val) => {
    const f = formatTargeta(val)
    if (f !== val) form.numeroTargeta = f
  }
)

watch(
  () => form.caducitat,
  (val) => {
    const f = formatCaducitat(val)
    if (f !== val) form.caducitat = f
  }
)

async function enviarCompra() {
  enviant.value = true
  try {
    const sessioNum = parseInt(String(sessioId), 10)
    const cos = {
      sessioId: sessioNum,
      seientIds: seientIds.value
    }
    var urlComprar = baseURL + '/comprar'
    await $fetch(urlComprar, {
      method: 'POST',
      body: cos,
      headers: authStore.capcalarsAutenticacio()
    })
    navigateTo('/mis-entrades')
  } catch (e) {
    if (e.response && e.response.status === 401) {
      await authStore.logout()
      alert('La sessió ha caducat, cal tornar a entrar')
      navigateTo('/login')
    } else {
      var msg = 'Error en desar la compra'
      if (e && e.data) {
        if (e.data.missatge) {
          msg = e.data.missatge
        } else if (e.data.error) {
          msg = e.data.error
        }
      } else if (e && e.message) {
        msg = e.message
      }
      alert(msg)
    }
  } finally {
    enviant.value = false
  }
}

const butaquesUrl = computed(() => `/butaques?peli=${peliId}&sessio=${sessioId}`)
</script>

<template>
  <div class="pago-page">
    <main class="pago-main">
      <p class="tornar">
        <NuxtLink :to="butaquesUrl">← Tornar a les butaques</NuxtLink>
      </p>

      <div v-if="!peliId || !sessioId || !seientIds.length" class="missatge">
        <p>No hi ha cap selecció de seients. Torna a triar butaques.</p>
        <NuxtLink v-if="peliId && sessioId" :to="butaquesUrl">Anar a butaques</NuxtLink>
        <NuxtLink v-else to="/">Cartellera</NuxtLink>
      </div>

      <template v-else-if="peli && seleccionats.length">
        <h1>Pagament</h1>
        <p class="subtitol">{{ peli.titol }}</p>

        <section class="resum">
          <h2>Resum de la comanda</h2>
          <ul>
            <li v-for="s in seleccionats" :key="s.id">
              Seient {{ s.fila }}{{ s.numero }} — {{ s.categoria }} ({{ getPreu(s.categoria) }}€)
            </li>
          </ul>
          <p class="total-line">Total: <strong>{{ totalPreu }}€</strong></p>
        </section>

        <form class="form-pago" novalidate @submit.prevent="enviarCompra">
          <h2>Dades de contacte</h2>
          <p class="hint">Tots els camps són opcionals per poder fer proves.</p>
          <label>
            <span>Correu electrònic</span>
            <input v-model="form.email" type="text" autocomplete="email" placeholder="nom@exemple.com">
          </label>
          <label>
            <span>Nom complet</span>
            <input v-model="form.nom" type="text" autocomplete="name" placeholder="Nom i cognoms">
          </label>

          <h2>Dades de la targeta</h2>
          <p class="hint">Demostració — no es guarda cap dada real.</p>
          <label>
            <span>Número de targeta</span>
            <input
              v-model="form.numeroTargeta"
              type="text"
              inputmode="numeric"
              autocomplete="cc-number"
              maxlength="19"
              placeholder="0000 0000 0000 0000"
            >
          </label>
          <div class="fila-doble">
            <label>
              <span>Caducitat</span>
              <input
                v-model="form.caducitat"
                type="text"
                inputmode="numeric"
                autocomplete="cc-exp"
                maxlength="5"
                placeholder="MM/AA"
              >
            </label>
            <label>
              <span>CVV</span>
              <input
                v-model="form.cvv"
                type="password"
                inputmode="numeric"
                autocomplete="cc-csc"
                maxlength="4"
                placeholder="123"
              >
            </label>
          </div>

          <button type="submit" class="btn-pagar" :disabled="enviant">
            {{ enviant ? 'Processant…' : `Pagar ${totalPreu}€` }}
          </button>
        </form>
      </template>

      <div v-else-if="peliId && sessioId" class="missatge">
        <p>Carregant o seients no vàlids…</p>
      </div>
    </main>
  </div>
</template>

<style scoped>
.pago-page {
  min-height: 100vh;
  padding: 24px 16px 48px;
  background: #f5f5f5;
}

.pago-main {
  max-width: 480px;
  margin: 0 auto;
}

.tornar {
  margin-bottom: 20px;
}

.tornar a {
  color: #007bff;
  text-decoration: none;
}

.tornar a:hover {
  text-decoration: underline;
}

h1 {
  font-size: 1.75rem;
  margin: 0 0 8px;
}

.subtitol {
  color: #555;
  margin: 0 0 24px;
}

.missatge {
  padding: 24px;
  text-align: center;
  background: white;
  border-radius: 8px;
}

.resum {
  background: white;
  padding: 20px;
  border-radius: 8px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.resum h2 {
  font-size: 1.1rem;
  margin: 0 0 12px;
}

.resum ul {
  margin: 0;
  padding-left: 20px;
}

.total-line {
  margin: 16px 0 0;
  font-size: 1.2rem;
}

.form-pago {
  background: white;
  padding: 24px;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.form-pago h2 {
  font-size: 1.1rem;
  margin: 24px 0 16px;
}

.form-pago h2:first-child {
  margin-top: 0;
}

.hint {
  font-size: 0.85rem;
  color: #666;
  margin: -8px 0 16px;
}

label {
  display: block;
  margin-bottom: 16px;
}

label span {
  display: block;
  font-size: 0.9rem;
  margin-bottom: 6px;
  color: #333;
}

input {
  width: 100%;
  padding: 12px 14px;
  border: 1px solid #ccc;
  border-radius: 6px;
  font-size: 1rem;
  box-sizing: border-box;
}

input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
}

.fila-doble {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.btn-pagar {
  width: 100%;
  margin-top: 24px;
  padding: 16px;
  font-size: 1.1rem;
  font-weight: 600;
  color: white;
  background: #28a745;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.btn-pagar:hover:not(:disabled) {
  background: #218838;
}

.btn-pagar:disabled {
  opacity: 0.7;
  cursor: wait;
}
</style>
