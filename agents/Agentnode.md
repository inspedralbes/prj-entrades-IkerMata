# Agent de desenvolupament backend (Node.js)

Normes per al **gateway** (`backend-node`): Express, **Socket.io** i pont cap a **Laravel**, en el context del projecte d’**entrades de cinema**.

## 1. Objectiu

- Servidor HTTP que fa de **pont** entre el frontend i Laravel (proxy / rutes pròpies).
- **Socket.io** per notificar esdeveniments al navegador (per exemple confirmació de compra), si s’utilitza.
- Connexió amb **Redis** només quan el projecte tingui el bridge asíncron activat (vegeu `Agentredis.md`).

## 2. Restriccions de codi (per codi nou en estil estricte)

Per al **codi nou** que s’afegeixi seguint l’agent, es recomana:

- **Entorn**: Node.js LTS amb Express (segons el `package.json`).
- **JavaScript ES5** per consistència amb els altres agents:
  - Variables amb **`var`** (evitar `const` i `let` en codi nou).
  - Funcions amb la paraula clau **`function`**, no arrow functions `=>`.
  - **Async**: es poden usar `async function` i `await`.
- **Evitar**: destructuring, `map` / `filter` / `reduce` quan es pugui fer amb bucles `for`, operadors ternaris.
- **Control**: `if`, `else`, `for`, `while`.

*Nota: el fitxer actual del gateway pot tenir sintaxi moderna; la norma aplica quan es reescrigui o s’ampliï de forma coherent amb aquest estil.*

## 3. Estructura de fitxer recomanada

```javascript
//==============================================================================
//================================ IMPORTS =====================================
//==============================================================================

//==============================================================================
//================================ VARIABLES ===================================
//==============================================================================

//==============================================================================
//================================ FUNCIONS ====================================
//==============================================================================
```

Cada funció: descripció general i passos **A, B, C** dins dels comentaris.

## 4. Idioma i nomenclatura

- Comentaris i noms propis del domini en **català**.
- **camelCase** per a variables i funcions.

## 5. Pont Redis (quan existeixi)

- **Sortida**: `LPUSH` cap a la **cua** acordada amb Laravel (no cal anomenar-la com en altres projectes).
- **Entrada**: subscripció al **canal de feedback** per reenviar missatges als clients via Socket.io, si escau.

## Regla GET / CUD

- **GET**: el frontend pot cridar Laravel directament o passar pel gateway; les rutes Laravel estan a `backend-laravel/routes/api.php`.
- **CUD**: preferentment **Node → (Redis opcional) → Laravel**; Socket.io només per **confirmacions / feedback**, no com a única font de veritat de dades.
