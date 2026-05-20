# Manual d’instal·lació i configuració — TICKET-FAST

Aquest document unifica com aixecar el projecte **venda d’entrades en temps real** (Nuxt + gateway Node + Laravel + PostgreSQL + Redis) amb Docker, i quines variables cal tenir en compte.

## 1. Requisits

- **Docker Desktop** (o Docker Engine + Compose Plugin) amb suport per Linux containers.
- **Git** per clonar el repositori.
- Opcional: **Node.js 20+** i **npm** només si treballes el frontend fora de Docker (vegeu apartat 6).

## 2. Estructura de serveis (Docker Compose)

Des de l’arrel del projecte (`prj-entrades-IkerMata`):

```bash
docker compose up --build
```

| Servei | Contenidor | Port al host (per defecte) | Funció |
|--------|------------|-----------------------------|--------|
| `frontend` | ticket-frontend | **3002** → 3000 | Nuxt 3 (UI) |
| `web` | ticket-web | **8001** → 80 | Nginx + API Laravel (`/api`) |
| `gateway` | ticket-gateway | **3003** → 3001 | Node: HTTP proxy + Socket.IO |
| `app` | ticket-app | (intern) | PHP-FPM Laravel |
| `scheduler` | ticket-scheduler | — | `php artisan schedule:work` (reserves caducades) |
| `db` | ticket-db | **5433** → 5432 | PostgreSQL |
| `redis` | ticket-redis | **6379** | Pub/sub temps real |
| `adminer` | ticket-adminer | **8081** → 8080 | Gestió BD (opcional) |

**URL útils (navegador):**

- Cartellera: `http://localhost:3002`
- API REST (Laravel via Nginx): `http://localhost:8001/api`
- Gateway (WebSocket i algunes rutes proxy): `http://localhost:3003`
- Adminer: `http://localhost:8081` (servidor `db`, usuari `laravel`, contrasenya `secret`, base `laravel`)

El frontend està configurat (Compose) perquè el navegador cridi:

- `NUXT_PUBLIC_API_BASE=http://localhost:8001/api`
- `NUXT_PUBLIC_GATEWAY_URL=http://localhost:3003`

Si canvies ports al `docker-compose.yml`, caldrà alinear aquestes variables.

## 3. Base de dades (sense migracions Laravel d’esquema)

L’esquema i les dades inicials venen dels scripts SQL:

- `base_de_dades/sql/init.sql` — creació de taules (PostgreSQL).
- `base_de_dades/sql/insert.sql` — dades de prova (usuaris, sales, sessions, etc.).

Es monten al contenidor PostgreSQL i el script d’entrada del PHP també pot referenciar la mateixa carpeta (vegeu `docker/php/entrypoint.sh`).

**Usuaris de prova** (contrasenya comuna `password`, vegeu `insert.sql`):

- Client: `test@example.com`
- Admin: `admin@cine.com`

## 4. Laravel (`backend-laravel`)

- Copia `backend-laravel/.env.example` a `backend-laravel/.env` si encara no existeix.
- Dins del contenidor, la connexió a PostgreSQL sol usar host `db` (no `127.0.0.1` al host).
- Genera clau d’aplicació si cal:

  ```bash
  docker compose exec app php artisan key:generate
  ```

Variables rellevants (exemples; ajusta al teu `.env`):

- `DB_HOST=db`
- `DB_DATABASE=laravel`
- `DB_USERNAME=laravel`
- `DB_PASSWORD=secret`
- Redis: `REDIS_HOST=redis`
- Venda d’entrades (opcional): `MAX_SEIENTS_RESERVA_SESSIO`, `RESERVA_TEMPORAL_MINUTS` (vegeu `.env.example`)

## 5. Gateway Node (`backend-node`)

- Envia les peticions cap a Laravel amb `LARAVEL_API_URL` (dins de Docker: `http://web/api`).
- Escolta esdeveniments Redis i emet Socket.IO (sales `sessio:*`, `admin`, etc.).

## 6. Frontend sense Docker (opcional)

```bash
cd frontend
npm install
npm run dev
```

Per defecte Nuxt escolta al **3000**. Has d’ajustar `nuxt.config.ts` (`runtimeConfig.public.apiBase`, `gatewayUrl`) perquè apuntin als mateixos hosts/ports que facis servir per l’API i el gateway.

## 7. Proves E2E (Cypress)

Vegeu `frontend/README.md`: cal el frontend en execució i l’API accessible; amb Docker usa `CYPRESS_BASE_URL=http://localhost:3002` si escau.

## 8. Problemes freqüents

- **Ports ocupats:** canvia els mapejos al `docker-compose.yml` o atura altres serveis.
- **Gateway sense node_modules:** segons el comentari al Compose, pots fer `docker compose down` i eliminar el volum `gateway_node_modules` i tornar a construir.
- **Imatge Composer al build PHP:** el Dockerfile instal·la Composer amb l’instal·lador oficial (no depèn de `composer:latest`).

## 9. Documentació relacionada

- Prova de concurrencia (manual): `doc/prova-concurrencia-reserva.md`
- Nucli temps real i matís Socket.IO (seqüència / justificació): `doc/nucli-temps-real-i-matis-socket.md`
- Convencions Laravel (esquema SQL, sense migracions d’esquema): `agents/Agentlaravel.md`
