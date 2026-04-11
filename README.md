# TICKET-FAST — Plataforma de venda d’entrades (temps real)

Projecte transversal: **frontend Nuxt 3**, **gateway Node (Express + Socket.IO)**, **API Laravel**, **PostgreSQL**, **Redis**. L’esquema de base de dades es defineix amb scripts **SQL** a `base_de_dades/sql` (`init.sql`, `insert.sql`), no amb migracions Laravel per al disseny de taules (convencions detallades a [`agents/Agentlaravel.md`](agents/Agentlaravel.md)).

## Integrants

- *(Afegeix aquí els noms del grup)*

## Enllaços opcionals

- **Prototip / disseny:** *(URL si en tens)*
- **Producció:** *(URL quan la tingueu)*

*(El gestor de tasques o altres enllaços interns els pots afegir quan el tutor ho indiqui.)*

## Estat

*(Breu descripció del punt on esteu: MVP, proves, lliurament, etc.)*

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
