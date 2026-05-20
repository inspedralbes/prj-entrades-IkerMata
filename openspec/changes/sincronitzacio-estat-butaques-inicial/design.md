## Context

El sistema combina una API REST (Laravel) per carregar la graella de seients i Socket.io (via gateway) per propagar canvis de selecció temporal. Avui la resposta inicial dels asientos no reflecteix les reserves temporals actives; només ho fan els esdeveniments en temps real. Això desincronitza la UI en càrregues tardanes o refrescos.

## Goals / Non-Goals

**Goals:**

- Al primer render després de `useFetch` (o equivalent), cada seient ha de mostrar si una reserva temporal d’un altre usuari el cobreix (`seleccionat_per_altre`) i, si es defineix al contracte, si la reserva és de l’usuari actual (`la_meva_reserva` o camp equivalent).
- Una sola font de veritat per l’estat persistent de reserves: la base de dades (i el que el backend exposi); el socket continua sent la font d’actualitzacions incrementals.
- Comportament predecible en dues finestres: recarregar la B ha de coincidir amb l’estat que ja mostrava el socket abans del refresh.

**Non-Goals:**

- Substituir Socket.io per polling com a mecanisme principal d’actualització.
- Canviar la durada de les reserves temporals ni la lògica de negoci de “qui guanya” en conflicte (això pot estar cobert per altres canvis).
- Disseny visual nou de la graella; només correcció de dades/estat.

## Decisions

1. **Enriquir la resposta HTTP dels asientos de sessió**  
   - **Què**: El backend calcula, per cada `seient_id`, si existeix una reserva temporal no caducada per a aquesta `sessio_id` i exposa booleans derivats (p. ex. `seleccionat_per_altre`, `la_meva_reserva`) comparant `usuari_id` amb l’usuari autenticat.  
   - **Per què**: La càrrega inicial ja ha de ser consistent amb el que el servidor considera “reservat temporalment”; el client no pot inferir-ho sense dades.  
   - **Alternatives**: Només socket + endpoint apart per “snapshot” (més crides i més races); només DB sense camps nous (el client hauria de mapejar IDs de reserva manualment).

2. **Mantenir el patch incremental per socket**  
   - **Què**: `onSeientSeleccionat` i handlers relacionats continuen actualitzant `seleccionat_per_altre`; la resposta inicial només fixa el baseline.  
   - **Per què**: Evita regressions en latència i evita duplicar lògica de negoci al client.

3. **Redis (opcional)**  
   - **Què**: Si el volum o la latència de la consulta de reserves per sessió és problemàtic, cachejar un mapa `seient_id → reserva` per `sessio_id` amb TTL curt i invalidar en crear/actualitzar/alliberar reserva temporal.  
   - **Per què**: Redueix pressió a la DB en pic d’entrades; el TTL ha de ser inferior al temps de vida de la reserva i la invalidació ha de ser fiable.  
   - **Alternatives**: Només SQL amb índexs adequats en `(sessio_id, expires_at)`; materialitzar menys camps.

4. **Autenticació per `la_meva_reserva`**  
   - **Què**: El camp que distingeix “la meva selecció” només té sentit si l’endpoint coneix l’usuari (token). Si la ruta és pública, documentar que aquest camp és sempre `false` o ometre’l.  
   - **Per què**: Evita falsa sensació de restauració de sessió sense identitat.

## Risks / Trade-offs

- **[Risc] Consulta extra per petició d’asientos** → Mitigació: consulta única amb join o subconsulta indexada; opcionalment cache amb invalidació estricta.
- **[Risc] Desalineació cache vs DB** → Mitigació: TTL curt i invalidació en tots els camins que muten reserves; proves de càrrega concurrent.
- **[Risc] Doble font de veritat (HTTP vs socket)** → Mitigació: el socket actualitza el mateix model de dades que va poblar l’HTTP; evitar sobreescriure amb valors obsolets (ordre d’esdeveniments i timestamps si cal en futurs refactors).

## Migration Plan

1. Desplegar canvis de backend (nou contracte de resposta, retrocompatible si els camps nous són additius).
2. Desplegar frontend que llegeix els nous camps a la primera càrrega.
3. Monitoritzar errors 4xx/5xx i temps de resposta de l’endpoint d’asientos.

**Rollback**: revertir desplegament del frontend (ignora camps nous) i del backend; els clients antics ignoren camps desconeguts si la resposta segueix sent JSON vàlid.

## Open Questions

- Confirmar el nom exacte dels models (`ReservaTemporal`, etc.) i si l’endpoint d’asientos ja retorna `reservat` permanent per separat.
- Decidir si cal Redis a la primera iteració o mesurar abans.
