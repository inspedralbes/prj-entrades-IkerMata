<script setup>
definePageMeta({
  middleware: 'guest',
  layout: 'default'
})

const auth = useAuth()
const email = ref('')
const password = ref('')
const errorMsg = ref('')
const pending = ref(false)

async function enviar() {
  errorMsg.value = ''
  pending.value = true
  try {
    await auth.login(email.value, password.value)
    navigateTo('/')
  } catch (e) {
    if (e.data && e.data.missatge) {
      errorMsg.value = e.data.missatge
    } else if (e.data && e.data.errors) {
      errorMsg.value = 'Revisa el formulari'
    } else {
      errorMsg.value = 'Error d\'inici de sessió'
    }
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <div class="wrap">
    <h1>Iniciar sessió</h1>
    <form class="form" @submit.prevent="enviar">
      <label>
        <span>Correu</span>
        <input v-model="email" type="email" required autocomplete="email">
      </label>
      <label>
        <span>Contrasenya</span>
        <input v-model="password" type="password" required autocomplete="current-password">
      </label>
      <p v-if="errorMsg" class="err">{{ errorMsg }}</p>
      <button type="submit" :disabled="pending">{{ pending ? 'Entrant…' : 'Entrar' }}</button>
    </form>
    <p class="link">
      <NuxtLink to="/registre">Crear compte</NuxtLink>
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
