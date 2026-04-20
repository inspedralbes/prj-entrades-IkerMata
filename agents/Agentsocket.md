# Agent de comunicació en temps real (Socket.io)

Normes per a **Socket.io** al **gateway Node** en el projecte d’**entrades de cinema** (notificacions, feedback de compra, etc.).

## 1. Objectiu

- Gestionar connexions **Socket.io** entre el navegador i el servidor Node.
- Opcionalment reenviar al client esdeveniments vinculats al **Redis bridge** o a la lògica del gateway (per exemple `compra-registrada`).
- Aquest projecte **no** exigeix WebRTC ni vídeo; si en el futur s’afegís, es documentaria apart.

## 2. Estil ES5 (per codi nou)

Per coherència amb `Agentnode.md`, en codi nou:

- `var`, `function`, bucles clàssics.
- Sense arrow functions, sense destructuring ni operadors ternaris als fragments nous que segueixin aquest agent.

## 3. Autenticació

- En mode **demostració sense login**, no cal exigir JWT al handshake; si més endavant s’afegís autenticació, s’hauria de documentar aquí i al gateway.

## 4. Estructura de fitxer recomanada

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

Documentació interna amb passos **A, B, C** on convingui.

## 5. Esdeveniments típics (exemples)

- Connexió i desconnexió de clients.
- Emissió d’esdeveniments després d’una acció del gateway (p. ex. confirmació de compra), sempre alineats amb el que faci el codi real del projecte.

## 6. Idioma i nomenclatura

- Català als comentaris. **camelCase** per a variables i funcions.

## Regla GET / CUD

- **GET**: dades des de l’API Laravel.
- **CUD**: persistència via Node / Redis / Laravel segons el flux del projecte; els sockets només per **feedback** en temps real.
