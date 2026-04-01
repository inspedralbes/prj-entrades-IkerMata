export default defineNuxtRouteMiddleware(function () {
  var auth = useAuth()
  if (!auth.token.value) {
    return navigateTo('/login')
  }
})
