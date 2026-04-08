# Guía de Integración de Pinia en Nuxt 3

Esta guía detalla los pasos para migrar el estado de la aplicación (como la autenticación y la selección de butacas) a **Pinia**, el sistema de gestión de estado recomendado para Vue y Nuxt.

## 1. Instalación

Primero, instala Pinia y el módulo oficial de Nuxt:

```bash
npm install pinia @pinia/nuxt
```

## 2. Configuración en Nuxt

Añade el módulo a tu archivo `nuxt.config.ts`:

```typescript
// nuxt.config.ts
export default defineNuxtConfig({
  modules: [
    '@pinia/nuxt',
  ],
  // Opcional: auto-imports para usar useStore sin importar
  pinia: {
    autoImports: ['defineStore', 'storeToRefs'],
  },
})
```

## 3. Creación de un Store (Autenticación)

Crea una carpeta `stores/` en la raíz del frontend y define tu primer store. Por ejemplo, `stores/auth.js`:

```javascript
// stores/auth.js
import { defineStore } from 'pinia'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: useCookie('sanctum_token').value || null,
    user: useCookie('user_data').value || null,
  }),
  
  actions: {
    async login(email, password) {
      const config = useRuntimeConfig()
      const url = config.public.gatewayUrl + '/api/login'
      
      try {
        const res = await $fetch(url, {
          method: 'POST',
          body: { email, password }
        })
        
        this.token = res.token
        this.user = res.usuari
        
        // Sincronizar con cookies para persistencia tras refrescar
        useCookie('sanctum_token').value = res.token
        useCookie('user_data').value = res.usuari
        
        return res
      } catch (error) {
        throw error
      }
    },
    
    logout() {
      this.token = null
      this.user = null
      useCookie('sanctum_token').value = null
      useCookie('user_data').value = null
    }
  },
  
  getters: {
    isAuthenticated: (state) => !!state.token,
    currentUserId: (state) => state.user ? state.user.id : null
  }
})
```

## 4. Uso en Componentes

Ahora puedes usar el store en cualquier componente (como `butaques.vue`) de forma reactiva:

```vue
<script setup>
const authStore = useAuthStore()
const { user, isAuthenticated } = storeToRefs(authStore)

// Ejemplo de uso en el evento de socket
socket.on('seient-seleccionat', (data) => {
  if (data.usuari_id !== authStore.currentUserId) {
    // ... marcar como seleccionado por otro
  }
})
</script>
```

## 5. Por qué usar Pinia aquí?

1.  **Reactividad Global**: Si cambias el usuario en el perfil, se actualiza automáticamente en la barra de navegación y en la página de butacas.
2.  **DevTools**: Podrás ver el estado de la aplicación en tiempo real con las extensiones de Chrome/Firefox.
3.  **Estructura**: Separa la lógica de API y estado de la lógica visual de los componentes.
4.  **Sustitución de Composables**: Reemplaza el uso de `useAuth.js` actual por un store centralizado que es más fácil de mantener conforme crezca la app.
