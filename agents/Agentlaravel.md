# Agent de desenvolupament backend (Laravel)

Aquest document defineix el comportament i les restriccions tècniques de l’agent per a la capa **Laravel** d’aquest projecte (**venda d’entrades de cinema**).

## 1. Rol

- Persistència en **PostgreSQL**.
- **API REST** (lectures) consumida pel frontend i, indirectament, pel gateway Node quan calgui.
- Models **Eloquent** sobre taules ja creades amb SQL.

## 2. Usuari per defecte (sense autenticació)

- **No s’utilitza autenticació** (Sanctum, sessions, etc.).
- Per a proves, els serveis poden usar l’**usuari de defecte** definit al codi (UUID de l’usuari de prova dels scripts SQL), no cal middleware d’autenticació.
- Les rutes API rellevants poden ser **públiques** mentre el projecte segueixi en mode demostració.

## 3. Restriccions tècniques

- **Framework**: Laravel i PHP compatibles amb el `composer.json` del repositori.
- **Base de dades**: PostgreSQL (versió la que fixi Docker o el servidor).
- **Condicionals**: no usar **operadors ternaris**; usar `if` / `else`, `for`, `foreach`, `while`.
- **Prohibició**: cap fitxer de `backend-laravel` no ha de contenir operadors ternaris.

### Esquema de base de dades (sense migracions de Laravel)

- **No** definir l’esquema amb fitxers a `backend-laravel/database/migrations/`.
- **Sí** mantenir el disseny amb **scripts `.sql`** (per exemple `base_de_dades/sql/`: `init.sql`, `insert.sql`, reseeds).
- Laravel només **llegeix i escriu** mitjançant Eloquent sobre taules existents.

## 4. Estructura de fitxers

- **Controllers (`app/Http/Controllers/`)**: entrada HTTP, validació bàsica, resposta JSON.
- **Services (`app/Services/`)**: lògica de negoci (compres, llistats, etc.).
- **Models (`app/Models/`)**: mapatge Eloquent.

### Convenció de rutes API (GET amb identificadors)

- Els **identificadors** (ids) han d’anar al **path** quan sigui possible, no com a query string.
- **Exemple adequat**: `GET /api/peliculas/{id}`, `GET /api/usuaris/{usuariId}/entrades`.
- **Evitar**: `GET /api/peliculas?id=3` (excepte filtres opcionals que no siguin id principal).

## 5. Estructura de codi i comentaris (obligatori)

Cada classe (Controller, Service, Command) ha de fer servir blocs de secció, per exemple:

```php
//================================ NAMESPACES / IMPORTS ============

//================================ PROPIETATS / ATRIBUTS ==========

//================================ MÈTODES / FUNCIONS ===========

//================================ RUTES / LÒGICA PRIVADA =========
```

Dins de cada mètode:

1. **Capçalera**: què fa la funció.
2. **Passos A, B, C…** amb comentaris del tipus `// A. …`, `// B. …`.
3. Abans de cada `if`, `foreach` o `while`, un comentari breu que expliqui la condició o el recorregut.

## 6. Estil

- **Idioma**: català per a comentaris i noms propis del domini; variables i funcions en **camelCase**.
- La lògica **no** ha de quedar només al controlador: cal **Services** per al negoci.

## 7. Redis (opcional / evolució)

Si s’activa el pont asíncron amb Redis, el consum de cues i la publicació de confirmacions seguiran el patró definit a `Agentredis.md` (noms de cua i canals segons el que acordi el projecte).

## Regla GET / CUD

- **GET**: preferentment contra l’API Laravel (`backend-laravel/routes/api.php`).
- **CUD** (crear / actualitzar / eliminar): en aquest projecte sovint passa pel **gateway Node** cap a Laravel (i en una versió amb cua, **Node → Redis → Laravel**). Els sockets només per **feedback** en temps real si cal.
