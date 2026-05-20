# Agent de sincronització (Redis)

Aquest document defineix el comportament esperat quan el projecte faci servir **Redis** com a **cua** i **bus** entre Node i Laravel (opcional segons desplegament).

## 1. Rol

- **Node (productor)**: enviar tasques amb `LPUSH` a una cua definida al projecte.
- **Laravel (consumidor)**: llegir amb `BRPOP` o equivalent en un worker persistent.
- **Tornada**: Laravel pot publicar confirmacions en un canal Pub/Sub; Node les rep i les reenvia al client (p. ex. via Socket.io).

Els **noms concrets** de la cua i del canal s’han d’alinear entre Node, Laravel i la documentació del repositori (no cal copiar noms d’altres projectes).

## 2. Restriccions de codi

**Node (ES5 estricta per codi nou):**

- Només `var`, funcions amb `function`, sense arrow functions ni destructuring ni ternaris als fragments nous que segueixin aquest agent.

**Laravel:**

- Fer servir la façana `Illuminate\Support\Facades\Redis` quan escaigui.
- Worker de cua amb processament **continu** mentre el servei estigui actiu.

**Idioma**: català als comentaris. **Nomenclatura**: camelCase (per exemple `processarTascaCua`, `publicarFeedbackRedis`).

## 3. Estructura de comentaris als fitxers Redis

```text
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

Dins de cada funció: passos **A, B, C** i comentaris abans de condicionals i bucles.

## 4. Fitxers al repositori

La ubicació exacta de cues, workers i serveis Redis ha de reflectir l’estructura real del projecte (es creen quan calgui, no cal inventar carpetes buides).

## Regla GET / CUD

- **GET**: contra l’API Laravel (`backend-laravel/routes/api.php`).
- **CUD**: flux asíncron **Node → Redis → Laravel** quan el bridge estigui implementat; en cas contrari, el gateway pot reenviar HTTP a Laravel tal com defineixi el projecte.
