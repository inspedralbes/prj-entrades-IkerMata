# TICKET-FAST — Plataforma de venda d’entrades (temps real)

Projecte transversal: **frontend Nuxt 3**, **gateway Node (Express + Socket.IO)**, **API Laravel**, **PostgreSQL**, **Redis**. L’esquema de base de dades es defineix amb scripts **SQL** a `base_de_dades/sql` (`init.sql`, `insert.sql`), no amb migracions Laravel per al disseny de taules (convencions detallades a [`agents/Agentlaravel.md`](agents/Agentlaravel.md)).

## Integrants

- Iker Mata García

## Enllaços

- **Prototip / disseny (Google Stitch):** [https://stitch.withgoogle.com/projects/11757740297033267871?pli=1](https://stitch.withgoogle.com/projects/11757740297033267871?pli=1)
- **Producció:** [https://prj-entrades-ikermata.daw.inspedralbes.cat/](https://prj-entrades-ikermata.daw.inspedralbes.cat/)

*(El gestor de tasques o altres enllaços interns els pots afegir quan el tutor ho indiqui.)*

## Estat

Projecte funcional amb Docker, API Laravel, gateway Socket.IO i frontend Nuxt; desplegament de referència a l’URL de producció.

## Instal·lació i configuració

**Guia completa:** [doc/MANUAL-INSTALACIO-I-CONFIGURACIO.md](doc/MANUAL-INSTALACIO-I-CONFIGURACIO.md)

Arrenc ràpid amb Docker (des de l’arrel del repositori):

```bash
docker compose up --build
```

- Interfície web: `http://localhost:3002`
- API: `http://localhost:8001/api`

## Documentació

Vegeu [doc/README.md](doc/README.md) (índex dels documents de la pràctica) i [frontend/README.md](frontend/README.md) (Nuxt, desenvolupament local, **Cypress**). La carpeta [`agents/`](agents/) conté guies per component (Laravel, Node, Redis, Socket, etc.) per al desenvolupament; no formen part del lliurable mínim de l’enunciat.
