# Agent de Flux Global

Aquest agent supervisa que tot el projecte segueixi el **flux arquitectònic** i les **normes de codificació** definides per a la plataforma de venda d'entrades.

## 1. Flux Arquitectònic (The Correct Flow)

El projecte es divideix en tres grans capes que es relacionen de forma específica:

1.  **Frontend (Vue/Nuxt)**:
    *   **GET**: Crida directament a l'API de Laravel (`backend-laravel`).
    *   **CUD** (Create/Update/Delete): Crida al Gateway Node (`backend-node`) per iniciar operacions que canvasin l'estat.
    *   **Real-time**: Escolta esdeveniments via Socket.io des del Gateway Node.

2.  **Gateway Node (`backend-node`)**:
    *   Actua com a proxy i gestor de comunicació en temps real.
    *   **CUD**: Rep peticions del frontend i les envia a Redis (cua `LPUSH`) o directament a Laravel segons configuració.
    *   **Feedback**: Escolta canals de Redis Pub/Sub i reenvia la informació als clients via **Socket.io**.

3.  **Backend Laravel (`backend-laravel`)**:
    *   Capa de persistència (**PostgreSQL**) i lògica de negoci pesada.
    *   **Worker Redis**: Escolta la cua (cua `BRPOP`) per processar tasques asíncrones.
    *   **Feedback**: Publica resultats o actualitzacions a Redis Pub/Sub per avisar al Gateway.

---

## 2. Normes de Codificació Estrictes

### Backend Laravel
*   **Prohibició**: No es poden fer servir operadors ternaris (`? :`). Cal usar `if / else`.
*   **Comentaris**: Sempre en **català**.
*   **Estructura**: Ús obligatori de blocs de secció (`//==== NAMESPACES ...`).
*   **Lògica**: S'ha d'extreure als **Services**.

### Backend Node (Gateway)
*   **Estil**: **ES5 Estricte** (per a codi nou/revisat).
*   **Variables**: Ús exclusiu de `var`. No `const` ni `let`.
*   **Funcions**: Paraula clau `function`. No arrow functions (`=>`).
*   **Prohibició**: No destructuring, no operadors ternaris.
*   **Async**: Es permet `async/await` però mantenint la resta de normes ES5.
*   **Comentaris**: Sempre en **català**.

---

## 3. Verificació del Flux (Global Audit)

L'agent ha de comprovar periòdicament:
- [ ] Les peticions de canvi d'estat (POST/PUT/DELETE) del frontend van al Node.
- [ ] Les consultes (GET) van a Laravel.
- [ ] Laravel no conté ternaris.
- [ ] Node no conté `const`, `let` o arrow functions.
- [ ] Tots els fitxers tenen comentaris en català i l'estructura de blocs.
