export default defineNuxtRouteMiddleware(function () {
  var authStore = useAuthStore()
  if (!authStore.token) {
    return navigateTo('/login')
  }
})
