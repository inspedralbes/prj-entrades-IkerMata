# Prova de concurrencia (dos usuaris, un seient)

L’esquema de base de dades es manté amb **`base_de_dades/sql`** (sense migracions Laravel), tal com indica `agents/Agentlaravel.md`. Per això la prova d’integració **no** depèn de `php artisan migrate` per crear taules; cal tenir **PostgreSQL** amb `init.sql` / `insert.sql` aplicats (p. ex. Docker Compose).

## Objectiu

Demostrar que **només un usuari** obté la reserva vàlida d’un mateix seient quan dos intenten reservar-lo: l’altre ha de rebre **409** (o missatge equivalent del backend).

## Passos (manual)

1. Arrenca l’API (Laravel + gateway + BD amb dades de prova).
2. Obtingues dos **tokens Sanctum** (dos usuaris `client` diferents), p. ex. `POST /api/login` per a cadascun.
3. Amb el **mateix** `sessioId` i `seientId`, envia **dues vegades** `POST /api/reservar` (o via gateway `POST .../api/reservar`) amb `estat: true`:
   - Primer usuari: resposta **200** i `ok: true`.
   - Segon usuari (sense alliberar el seient): resposta **409** i missatge del tipus «Seient ja reservat per un altre usuari».

Opció amb dues finestres del navegador: obre **butaques** amb dos comptes, clica el **mateix seient lliure** gairebé alhora; un queda seleccionat i l’altre ha de veure error / refresc coherent.

## Automatització

Un test PHPUnit que necessiti taules completes hauria d’executar-se contra una BD ja inicialitzada amb els scripts SQL del repositori, no amb migracions Eloquent.
