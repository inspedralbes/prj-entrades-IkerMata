## Why

La pantalla de butaques obté els seients per API però el camp que indica si un seient està seleccionat temporalment per un altre usuari (`seleccionat_per_altre`) només s’actualitza via Socket.io. Qui es connecta tard o recarrega la pàgina no rep esdeveniments passats i veu seients com a lliures quan ja tenen reserva temporal activa, cosa que empitjora l’experiència i pot conduir a conflictes fins que el servidor respon amb error.

## What Changes

- L’endpoint que llista els asientos d’una sessió ha d’incloure, en la resposta inicial, l’estat de reserves temporals actives (p. ex. qui té el seient “en selecció” i distinció respecte de la pròpia reserva de l’usuari autenticat, si aplica).
- El frontend (`butaques.vue` o equivalent) ha d’usar aquests camps en la primera càrrega i seguir reconciliant amb esdeveniments en temps real (Socket.io) sense contradir l’estat servidor.
- (Opcional segons disseny) Estratègia de caché o optimització per no sobrecarregar la base de dades, coherent amb la invalidació quan canviïn les reserves.

## Capabilities

### New Capabilities

- `session-seats-initial-state`: Contracte i requisits perquè la API de seients per sessió exposi l’estat inicial alineat amb reserves temporals i el client el mostri correctament abans i després dels esdeveniments en temps real.

### Modified Capabilities

- Ninguna (no hi ha capacitats especificades prèviament a `openspec/specs/`).

## Impact

- Backend Laravel: controlador o capa de servei que construeix la resposta de `/sesiones/{id}/asientos` (o ruta equivalent), consulta o composició amb `ReservaTemporal` (o model equivalent), possible ús de Redis si s’adopta caché.
- Frontend Nuxt: primera càrrega de dades i classes CSS/estat visual per `seleccionat_per_altre` (i camps afegits com `la_meva_reserva` si es defineixen).
- Gateway temps real: sense canvi obligatori de protocol; els esdeveniments segueixen sent la font d’actualitzacions incrementals després de la càrrega inicial.
