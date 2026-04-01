/**
 * Token Sanctum: cookie Nuxt (accessible al middleware) + capçaleres Bearer.
 */
export function useAuth() {
  const config = useRuntimeConfig()
  const token = useCookie('sanctum_token', {
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
    sameSite: 'lax'
  })

  function capcalarsAutenticacio() {
    var cap = {}
    if (token.value) {
      cap['Authorization'] = 'Bearer ' + token.value
    }
    return cap
  }

  async function login(email, password) {
    var url = config.public.apiBase + '/login'
    var res = await $fetch(url, {
      method: 'POST',
      body: { email: email, password: password }
    })
    token.value = res.token
    return res
  }

  async function registrar(nom, email, password) {
    var url = config.public.apiBase + '/register'
    var res = await $fetch(url, {
      method: 'POST',
      body: { nom: nom, email: email, password: password }
    })
    token.value = res.token
    return res
  }

  async function logout() {
    var url = config.public.apiBase + '/logout'
    await $fetch(url, {
      method: 'POST',
      headers: capcalarsAutenticacio()
    })
    token.value = null
  }

  return {
    token: token,
    capcalarsAutenticacio: capcalarsAutenticacio,
    login: login,
    registrar: registrar,
    logout: logout
  }
}
