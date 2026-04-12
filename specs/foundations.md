# Foundations — context, objectius i restriccions

**Canvi OpenSpec:** `sincronitzacio-estat-butaques-inicial`  
**Font canònica:** `openspec/changes/sincronitzacio-estat-butaques-inicial/design.md` (+ motivació a `proposal.md`)

---

## Context

El sistema combina una API REST (Laravel) per carregar la graella de seients i Socket.io (via gateway) per propagar canvis de selecció temporal. La resposta inicial dels asientos no reflectia les reserves temporals actives; només ho feien els esdeveniments en temps real. Això desincronitzava la UI en càrregues tardanes o refrescos.

## Objectius

- Al primer render després de la càrrega HTTP, cada seient ha de mostrar si una reserva temporal d’un altre usuari el cobreix (`seleccionat_per_altre`) i, si escau, si la reserva és de l’usuari actual (`la_meva_reserva`).
- Una sola font de veritat per l’estat persistent: base de dades i el que el backend exposa; el socket continua sent la font d’actualitzacions incrementals.
- Comportament predecible entre finestres: un refresh ha de coincidir amb l’estat coherent amb el servidor abans del refresh.

## Restriccions / No-objectius

- No substituir Socket.io per polling com a mecanisme principal.
- No canviar la durada de les reserves temporals ni la lògica de conflicte principal (fora de l’abast d’aquest canvi).
- No redisseny visual de la graella; només correcció de dades/estat.

## Decisions i riscos (resum)

Vegeu el fitxer `design.md` complet al camí OpenSpec per a decisions detallades (HTTP enriquit, socket incremental, Redis opcional, autenticació per `la_meva_reserva`), riscos i pla de migració/rollback.
