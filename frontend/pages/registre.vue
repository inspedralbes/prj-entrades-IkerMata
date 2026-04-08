<script setup>
definePageMeta({
  middleware: 'guest',
  layout: 'default'
})

const authStore = useAuthStore()
const nom = ref('')
const email = ref('')
const password = ref('')
const errorMsg = ref('')
const pending = ref(false)

async function enviar() {
  errorMsg.value = ''
  pending.value = true
  try {
    await authStore.registrar(nom.value, email.value, password.value)
    navigateTo('/')
  } catch (e) {
    if (e.data && e.data.errors) {
      errorMsg.value = 'Revisa les dades (email únic, contrasenya mínim 8 caràcters)'
    } else {
      errorMsg.value = 'Error en el registre'
    }
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <div class="wrap">
    <h1>Crear compte</h1>
    <form class="form" @submit.prevent="enviar">
      <label>
        <span>Nom</span>
        <input v-model="nom" type="text" required autocomplete="name">
      </label>
      <label>
        <span>Correu</span>
        <input v-model="email" type="email" required autocomplete="email">
      </label>
      <label>
        <span>Contrasenya (mín. 8)</span>
        <input v-model="password" type="password" required minlength="8" autocomplete="new-password">
      </label>
      <p v-if="errorMsg" class="err">{{ errorMsg }}</p>
      <button type="submit" :disabled="pending">{{ pending ? 'Registrant…' : 'Registrar-se' }}</button>
    </form>
    <p class="link">
      <NuxtLink to="/login">Ja tinc compte</NuxtLink>
    </p>
  </div>
</template>

<style scoped>
.wrap {
  max-width: 400px;
  margin: 40px auto;
  padding: 24px;
}

.form label {
  display: block;
  margin-bottom: 16px;
}

.form label span {
  display: block;
  margin-bottom: 6px;
}

.form input {
  width: 100%;
  padding: 10px;
  box-sizing: border-box;
}

.err {
  color: #c00;
}

button {
  padding: 12px 24px;
  cursor: pointer;
}

.link {
  margin-top: 20px;
}
</style>
