# Registre de prompts — Metodologia SDD + IA (OpenSpec)

**Projecte:** TICKET-FAST — sincronització de l’estat inicial de butaques amb reserves temporals  
**Canvi OpenSpec:** `sincronitzacio-estat-butaques-inicial`  
**Autor:** Iker Mata García  

Aquest fitxer documenta el **procés cronològic** (especificació amb IA, implementació guiada pel spec, correccions). Els prompts s’han reconstruït i condensat a partir del treball real amb l’agent; si el professorat exigeix **còpies literals**, enganxa des de l’historial de Cursor/Chat les versions exactes en els blocs marcats com a *opcional*.

---

## Fase 0 — Acord de la funcionalitat (amb el docent)

| Data (orientativa) | Acció |
|--------------------|--------|
| Abans del `propose` | Es va comentar una feature **acotada**: corregir la desincronització entre la primera resposta HTTP dels seients i l’estat que ja reflecteix Socket.io (reserves temporals). |

*(Si cal justificar-ho per escrit al PDF, resumeix aquí l’email o el missatge del tutor.)*

---

## Fase 1 — Especificació OpenSpec (`opsx:propose`)

### 1.1 — Context i proposta

**Prompt (reconstruït):**

> Crea un canvi OpenSpec anomenat `sincronitzacio-estat-butaques-inicial`. El problema: l’endpoint que llista els seients per sessió no inclou reserves temporals actives a la primera càrrega; `seleccionat_per_altre` només s’actualitza per Socket.io, així que un refresh o un client tardà veu seients com a lliures.  
> Genera `proposal.md` (why/what/impact) i una capability `session-seats-initial-state` amb `spec.md` en format requisits + escenaris (WHEN/THEN).  
> El backend és Laravel, frontend Nuxt; no canviïs la durada de les reserves ni el protocol del gateway.

**Resultat:** `openspec/changes/sincronitzacio-estat-butaques-inicial/proposal.md` + `specs/session-seats-initial-state/spec.md`.

---

### 1.2 — Disseny (foundations / design)

**Prompt (reconstruït):**

> Amplia el canvi amb `design.md`: context, goals i non-goals, decisions (enriquir HTTP amb booleans derivats, mantenir socket incremental, Redis opcional, auth per `la_meva_reserva`), riscos i pla de migració/rollback.

**Resultat:** `design.md` amb decisions explícites i riscos.

---

### 1.3 — Tasca d’implementació

**Prompt (reconstruït):**

> Genera `tasks.md` amb passos verificables: localitzar endpoint d’asientos, consultar `ReservaTemporal` amb `expires_at`, poblar camps, proves PHPUnit, després frontend i verificació manual dos navegadors.

**Resultat:** checklist a `tasks.md`.

---

## Fase 2 — Implementació amb IA (`opsx:apply`)

### 2.1 — Backend: contracte i lògica

**Prompt (reconstruït):**

> Implementa el que diu l’spec al backend Laravel: a la ruta que retorna els seients per sessió (`api.php`), carrega reserves temporals actives per `sessio_id`, i per cada seient afegeix `seleccionat_per_altre` i `la_meva_reserva` segons l’usuari del Bearer (Sanctum) si n’hi ha. Extreu la lògica a una classe petita tipus `SeientTemporalEstat::flags` per testejar-la. Si la reserva és meva, exposa `meva_expiracio_iso` quan calgui.

**Codi clau (traçabilitat):**

- `backend-laravel/app/Support/SeientTemporalEstat.php`
- `backend-laravel/routes/api.php` (map de seients amb `ReservaTemporal` i flags)
- `backend-laravel/tests/Unit/SeientTemporalEstatTest.php`

---

### 2.2 — Frontend: primera càrrega + store

**Prompt (reconstruït):**

> Assegura que el store Pinia (`sessioSeients.js`) sincronitza `la_meva_reserva` i `meva_expiracio_iso` des de la resposta inicial (`aplicaLlistaDesDelServidor`, `syncExpiresFromLlista`) i restaura la selecció amb `setSelectedFromHydration` quan escau. No trencar els handlers de Socket.io existents.

**Codi clau:** `frontend/stores/sessioSeients.js`, `frontend/pages/butaques.vue`.

---

## Fase 3 — Correcció i refinament (iteracions controlades)

### Iteració A — Semàntica “venut” vs temporal

| Problema detectat | El spec diu que si el seient està **venut**, els flags temporals no han de competir amb la venda. |
|-------------------|-----------------------------------------------------------------------------------------------------|
| Com s’ha corregit | `SeientTemporalEstat::flags` retorna `false/false` si `$isVenut` és cert (test `test_seient_venut_cap_temporal`). |
| Canvi al prompt | Es va explicitar: *“ignora reserva temporal si el seient ja consta com a venut; els tests han de reflectir-ho”*. |

---

### Iteració B — Peticions sense token

| Problema detectat | Sense autenticació, una reserva d’un altre ha de mostrar-se com a “seleccionat per altre”, no com a “meva”. |
|-------------------|---------------------------------------------------------------------------------------------------------------|
| Com s’ha corregit | Tests `test_reserva_dun_altre_sense_auth` i `test_reserva_dun_altre_amb_auth`; `la_meva_reserva` només si `authUserId` coincideix amb `usuari_id` de la reserva. |
| Canvi al prompt | *“Documenta el comportament sense Bearer: `la_meva_reserva` fals; si hi ha reserva d’un altre, `seleccionat_per_altre` cert.”* |

---

### Iteració C — Coherència amb el spec “baseline + socket”

| Problema detectat | Risc de sobreescriure l’estat inicial quan arriben esdeveniments en cua. |
|-------------------|---------------------------------------------------------------------------|
| Com s’ha corregit | Cua d’esdeveniments Socket (`pendingSeatSocketEvents`, `flushPendingSeatSocketEvents`) fins que la llista HTTP està carregada. |
| Relació prompt–codi | Es va demanar explícitament no regressar el flux temps real; el disseny ja ho marcava com a decisió 2. |

---

## Resum numèric (per al PDF)

| Concepte | Valor orientatiu |
|----------|------------------|
| Fases documentades | Especificació (3 prompts) + implementació (2) + refinament (3 iteracions) |
| Canvis al **spec** després del primer `propose` | Mínims (aclariments de noms de camps; la capacitat no va canviar de propòsit) |
| Canvis principalment a **prompts / codi** | Sí: detall de casos límit i proves |

---

## Opcional: enganxa aquí prompts literals de Cursor

```
(Pega el historial exportado o capturas si el profesorado lo exige)
```

---

## Referències ràpides al repositori

| Artefacte | Camí |
|-----------|------|
| OpenSpec (canvi complet) | `openspec/changes/sincronitzacio-estat-butaques-inicial/` |
| Còpies amb noms de l’enunciat | `specs/foundations.md`, `specs/spec.md`, `specs/plan.md` |
| Document PDF (font MD) | `docs/entrega-metodologia-IA-OpenSpec.md` |
