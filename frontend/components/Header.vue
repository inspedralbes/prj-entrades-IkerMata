<script setup>
const auth = useAuth()

async function tancarSessio() {
  await auth.logout()
  navigateTo('/')
}
</script>

<template>
  <header>
    <nav>
      <NuxtLink to="/">Cartelera</NuxtLink>
      <NuxtLink to="/mis-entrades">Mis entrades</NuxtLink>
      <span v-if="auth.token.value" class="user">
        <button type="button" class="link-btn" @click="tancarSessio">Tancar sessió</button>
      </span>
      <NuxtLink v-else to="/login" class="login-link">Iniciar sessió</NuxtLink>
    </nav>
  </header>
</template>

<style scoped>
nav {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
  padding: 12px 16px;
}

.login-link {
  margin-left: auto;
}

.user {
  margin-left: auto;
}

.link-btn {
  background: none;
  border: none;
  color: #007bff;
  cursor: pointer;
  text-decoration: underline;
  font-size: inherit;
  padding: 0;
}
</style>
