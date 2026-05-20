# Checklist — API vía gateway (Node → Laravel)

Objectiu: el client (Nuxt) parla **primer amb Node** (`gateway`) i el gateway **reenvia** a Laravel quan calgui. Laravel segueix sent l’autoritat de negoci (`routes/api.php`).

---

## A. Abast i contracte

- [ ] Llistar **totes** les rutes que el Nuxt usa avui amb `apiBase` (GET / POST / PUT / DELETE).
- [ ] Decidir si **tota** l’API REST passa per `:3003/api` o només una part (pel·lícules, sessions, etc.).
- [ ] Definir **una sola** variable de configuració pública (`NUXT_PUBLIC_API_BASE` / `apiBase`) apuntant al **gateway** en dev i en desplegament.

---

## B. Gateway (Node)

- [ ] Afegir **reenviament** cap a Laravel per les rutes que **encara no** estiguin a `backend-node/index.js`, **o** un **proxy genèric** `/api/*` amb cura de **no duplicar** rutes que ja tinguin lògica extra (`/api/reservar`, `/api/comprar`, login, logout…).
- [ ] Reenviar **Authorization**, **Content-Type** i **cos** en POST / PUT / PATCH.
- [ ] Reenviar **query string** en GET.
- [ ] Provar manualment: mateix JSON i mateixos codis HTTP que cridant directe a Laravel.

---

## C. Nuxt (frontend)

- [ ] Canviar **`apiBase`** (i `.env` / `docker-compose` si escau) cap a `http://localhost:3003/api` (o la URL del gateway).
- [ ] Revisar **`useApiBase`** / URLs relatives perquè no segueixin apuntant a `:8001` per error.
- [ ] Confirmar que **login / register / logout** continuen sent coherents (`gatewayUrl` vs `apiBase` si abans estaven duplicats).

---

## D. CORS i xarxa

- [ ] Verificar **CORS** al gateway per l’origen del Nuxt (si el navegador crida un altre port).
- [ ] En Docker: comprovar que des del **navegador** la URL del gateway és assolible (host / port publicats).

---

## E. Laravel (sense canviar lògica si no cal)

- [ ] Confirmar que les rutes a **`routes/api.php`** segueixen sent la **font de veritat**; el gateway només fa de **proxy**.
- [ ] Si falta algun endpoint de pelis / sessions a Laravel, documentar-ho i afegir-ho **primer** a Laravel, després proxy a Node.

---

## F. Proves

- [ ] Cartellera, detall pel·lícula, sessions, butaques (asientos), compra, reserva, admin CRUD — cada flux **via gateway**.
- [ ] Casos **401 / 403** amb i sense token.
- [ ] Socket.io: continua usant **`gatewayUrl`**; comprovar que no es trenca en canviar `apiBase`.

---

## G. Documentació / lliurament

- [ ] Actualitzar **llista d’endpoints** (taula: ruta, mètode, gateway sí/no, destí Laravel).
- [ ] Afegir al **`prompts-log`** o document de la pràctica: decisió «API darrere de Node» i data.

---

## Ordre recomanat

1. A → B → C → D → E → F → G.

---

## Taula auxiliar — rutes a inventariar (omplir)

| Ús al Nuxt | Mètode | Ruta (relativa a `/api`) | Passa avui per `apiBase` (sí/no) |
|------------|--------|---------------------------|----------------------------------|
| | | | |
| | | | |

---

## H. Dades inicials (SQL) — només pel·lícules per API

> **Pas a pas:** decidir i aplicar **un punt abans** de passar al següent. No cal fer-ho tot d’un cop.

- [ ] **Treure del seed** (`base_de_dades/sql/insert.sql`) els `INSERT` de la taula **`pelis`**, perquè la cartellera surti **només** del que es creï des de l’API (admin / CRUD).
- [ ] **Atenció FK:** si hi ha `INSERT` a **`sessions`** i **`preus_sessio`** que apunten a `esdeveniment_id` de pelis seed, cal **ajustar el mateix script** (treure també aquests blocs o deixar dades coherents); si no, el `INSERT` fallarà o quedarà incoherent.
- [ ] Després de buidar seed de pelis: **`SELECT setval`** de seqüències (`pelis_id_seq`, `sessions_id_seq`, …) ha de reflectir **max(id)** o el valor que vulgueu per als següents `INSERT` des de l’app.
- [ ] Provar **reset / migració** en entorn de desenvolupament abans de donar per bo el script.

---

## I. Frontend — cartellera, sessions i fitxa (UX)

> **Pas a pas:** implementar i provar **cada subapartat** abans d’afegir el següent.

### I.1 Pàgina d’inici (`/`)

- [ ] A la **graella / carrusel de pel·lícules**, mostrar **només la imatge** (cartell), sense títol llarg, sense descripció, sense “Disponible/Esgotat” a la targeta (o el mínim que acoteu).
- [ ] Revisar si el **hero** (secció destacada) també s’ha d’aprimar (només imatge / menys text); deixar-ho explicitat aquí quan ho decidiu.

### I.2 Navegació en clic

- [ ] En fer clic a una pel·lícula, anar a la pàgina de **sessions** (p. ex. ruta actual ` /sala?peli=… ` o la que definiu), on es tria **hora / sala** i es continua cap a butaques.

### I.3 Pàgina de **més informació** de la pel·lícula (nova o ruta nova)

- [ ] Nova vista on es mostri **tot el que retorna l’API** de detall de pel·lícula (vegeu taula inferior).
- [ ] Enllaç des de la pàgina de sessions (o des del lloc que acoteu): text tipus “Més informació”, “Fitxa”, etc.

### I.4 Camps que proporciona l’API (referència per a la fitxa)

**`GET /api/peliculas`** (llista; cada ítem és un objecte amb, entre d’altres):

| Camp (concepte) | Notes |
|-----------------|--------|
| `id` | Identificador |
| `titol` | |
| `descripcio` | |
| `imatge_url` | |
| `durada_minuts` | |
| `estat` | p. ex. actiu / inactiu |
| `hi_ha_disponibilitat` | Calculat (aforament); útil si el voleu a la llista |

**`GET /api/peliculas/{id}`** (detall per una pel·lícula):

| Camp | Notes |
|------|--------|
| `id` | |
| `titol` | |
| `imatge_url` | |
| `descripcio` | |
| `durada_minuts` | |
| `estat` | |

*(Si en el futur l’API afegeix més camps — p. ex. `uuid` — actualitzar aquesta taula i la fitxa.)*

### I.5 Config Nuxt

- [ ] Afegir `routeRules` per la nova ruta de fitxa si cal **SSR: false** (com la resta de pàgines que fan `useFetch` al client), quan s’implementi.

---

## Ordre suggerit (tot el document)

1. **H** (dades SQL) quan toqui buidar seed.  
2. **I** (UX) quan toqui canviar la cartellera i afegir la fitxa.  
3. **A–G** (gateway / apiBase) quan abordeu l’API darrere de Node.

Pots seguir **H → I → A→G** o **A→G → H → I** segons el que prioritzeu al projecte; l’important és **un pas revisat abans del següent**.
