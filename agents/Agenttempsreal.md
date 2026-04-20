# Agent de temps real (requisits per pàgina)

Aquest document recull **què ha de fer el temps real** al frontend i fa de referència conjunta amb `Agentredis.md` i `Agentsocket.md`. Es pot ampliar quan s’afegeixin més pantalles.

## Plan d’implementació (tasques tècniques)

1. **Infraestructura Redis**: servei Docker, variables `REDIS_*`, connexió Laravel (`predis`) i Node (`ioredis`), endpoints de salut. *(Fet: veure secció “Tasca 1” al final.)*
2. **Pont Redis → Node** (Pub/Sub o cua) i esdeveniments alineats amb CUD a Laravel.
3. **Socket.io per sales** (`join` per sessió / pel·lícula) i emissió d’esdeveniments des del gateway.
4. **Frontend**: `index.vue`, `sala.vue`, `butaques.vue` (composables, listeners, estat).

## 1. Rol

- **Dades persistides i GET**: API Laravel (`backend-laravel`), com a la resta del projecte.
- **Feedback immediat a la UI**: gateway **Node** amb **Socket.io**; opcionalment pont **Redis** (Pub/Sub o cua) quan Laravel o altres processos hagin d’avisar el Node sense acoblar-ho tot a HTTP síncron.
- Els **noms concrets** de canals, cues i esdeveniments s’han d’alinear entre Node, Laravel i aquest fitxer quan s’implementi.

## 2. `frontend/pages/index.vue` (llistat de pel·lícules)

| Requisito (español) | Detalle |
|---------------------|---------|
| Plazas por película | En el listado, cada película debe mostrar si **quedan asientos** en alguna de sus sesiones (criterio exacto a fijar con la API: p. ej. “hay al menos una sesión con aforo disponible”). |
| Tiempo real | Si cambia esa disponibilidad (compra, liberación, etc.), el indicador debe **actualizarse** sin recargar (Socket.io; opcionalmente refuerzo vía GET). |

**Nota**: La API debe definir cómo se agrega “quedan asientos” por película para que el cliente y el servidor coincidan.

## 3. `frontend/pages/sala.vue` (horaris de la pel·lícula)

| Requisito (español) | Detalle |
|---------------------|---------|
| Por horario / sesión | En cada horario debe verse **cuántas butacas quedan** (número o estado claro). |
| Tiempo real | Si **alguien compra** (o libera) plazas de esa sesión, el dato debe **actualizarse en tiempo real** para quien esté en esa pantalla. |
| Sesión llena | Si **no quedan asientos**, mostrar que la **sesión está llena** (texto acordado: “Sesión completa”, “Sin plazas”, etc.). |

**Nota**: Los clientes deberían unirse a una **sala Socket** por `id` de sesión (y/o película) para no broadcast global innecesario.

## 4. `frontend/pages/butaques.vue` (selecció d’asients)

| Requisito (español) | Detalle |
|---------------------|---------|
| Selección visible para otros | Si una persona **selecciona** butacas (misma **película** y misma **sesión**), el resto de usuarios en ese mismo flujo debe **ver** que esos asientos están **en proceso / reservados temporalmente** por alguien (estado distinto de libre y de vendido). |
| Alcance | Misma película + misma sesión (mismo identificador de sesión en BD/API). |

**Nota tècnica (a concretar en implementació)**:

- **TTL** máximo para la selección temporal y eventos tipo preselección / liberación.
- Política si dos usuarios chocan en el mismo asiento: quien confirma primero, o error (definir).

## 5. Regla GET / CUD (alineada amb altres agents)

- **GET**: datos desde la API Laravel (aforo inicial, estado de sesiones, etc.).
- **CUD**: persistencia según el flujo del proyecto (Node → Redis → Laravel si el puente está activo); los sockets para **vista en vivo** y **estados temporales** de selección.

## 6. Idees opcionals (per comentar; no són requisit encara)

- Aviso cuando una sesión pasa a **llena** mientras el usuario la está mirando.
- Posición en **cola virtual** si más adelante hay límite de acceso concurrente.
- Indicador “**X personas** viendo esta sesión” (solo si aporta y respeta privacidad).
- Sincronización de **temporizador** de pago si hay cuenta atrás compartida.
- **Aforo casi lleno** (“Últimas plazas”) como aviso visual.

Per afegir més pàgines més endavant: nova secció numerada amb el mateix format (taula + notes).

---

## Tasca 1 (infra Redis) — implementat

- **Docker**: servei `redis` (imatge `redis:7-alpine`), volum `redis_data`, xarxa `ticket-net`. `app` i `gateway` depenen de `redis`; variables `REDIS_HOST=redis` i `REDIS_CLIENT=predis` al servei `app`; `REDIS_HOST` / `REDIS_PORT` al `gateway`.
- **Laravel**: `predis/predis`; `.env` amb `REDIS_CLIENT=predis` i `REDIS_HOST=redis` (coherent amb Docker). Endpoint `GET /api/health/redis` (resposta JSON `ok` + resultat del `ping`).
- **Node**: dependència `ioredis`; fitxer `backend-node/redisClient.js`; `GET /health/redis` al gateway.
- **Comprovació** (amb `docker compose up`): `http://localhost:8001/api/health/redis` i `http://localhost:3003/health/redis` han de respondre `ok: true` quan Redis estigui actiu.
