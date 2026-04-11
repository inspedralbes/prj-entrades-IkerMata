import { defineStore } from 'pinia'
import { toValue } from 'vue'
import { resolvePublicGatewayUrl } from '~/composables/publicGatewayUrl'

export const useAuthStore = defineStore('auth', () => {
  const tokenCookie = useCookie('sanctum_token', {
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
    sameSite: 'lax'
  })
  const userCookie = useCookie('user_data', {
    maxAge: 60 * 60 * 24 * 7,
    path: '/',
    sameSite: 'lax'
  })

  const token = ref(tokenCookie.value || null)
  const user = ref(userCookie.value || null)

  watch(tokenCookie, (val) => { token.value = val })
  watch(userCookie, (val) => { user.value = val })

  function capcalarsAutenticacio() {
    var cap = {}
    if (token.value) {
      cap['Authorization'] = 'Bearer ' + token.value
    }
    return cap
  }

  async function login(email, password) {
    const config = useRuntimeConfig()
    const url = resolvePublicGatewayUrl(config.public.gatewayUrl) + '/api/login'

    const res = await $fetch(url, {
      method: 'POST',
      body: { email, password }
    })

    token.value = res.token
    user.value = res.usuari

    tokenCookie.value = res.token
    userCookie.value = res.usuari

    return res
  }

  async function registrar(nom, email, password) {
    const config = useRuntimeConfig()
    const url = config.public.gatewayUrl + '/api/register'

    const res = await $fetch(url, {
      method: 'POST',
      body: { nom, email, password }
    })

    token.value = res.token
    user.value = res.usuari

    tokenCookie.value = res.token
    userCookie.value = res.usuari

    return res
  }

  async function logout() {
    const config = useRuntimeConfig()
    const url = resolvePublicGatewayUrl(config.public.gatewayUrl) + '/api/logout'

    try {
      await $fetch(url, {
        method: 'POST',
        headers: capcalarsAutenticacio()
      })
    } catch (e) {
      var status = e.statusCode ?? e.response?.status
      if (status !== 401) {
        console.error('Error enviant petició de logout al servidor:', e)
      }
    } finally {
      token.value = null
      user.value = null
      tokenCookie.value = null
      userCookie.value = null
    }
  }

  const isAuthenticated = computed(() => !!token.value)
  const currentUserId = computed(() => (user.value ? user.value.id : null))

  /** Si hi ha token però falta l'usuari (cookie antiga), carrega GET /usuari. */
  async function syncUsuariSiCal(apiBase) {
    if (!token.value) return
    if (user.value && user.value.id) return
    const base = toValue(apiBase)
    if (!base || typeof base !== 'string') return
    try {
      const u = await $fetch(base + '/usuari', {
        headers: capcalarsAutenticacio()
      })
      if (u && u.id) {
        user.value = u
        userCookie.value = u
      }
    } catch (_) {
      /* ignore */
    }
  }

  return {
    token,
    user,
    capcalarsAutenticacio,
    login,
    registrar,
    logout,
    syncUsuariSiCal,
    isAuthenticated,
    currentUserId
  }
})