# Pendents respecte a l’enunciat de la pràctica

Aquest document resumeix **allò que encara falta o queda parcial** respecte al text oficial *Plataforma de Venda d’Entrades per a un Esdeveniment d’Alta Demanda (Temps Real)*, i allò que **ja cobreix el nucli** del projecte.

**Ús:** checklist per acabar el lliurament o preparar l’exposició.

---

## 1. Nucli (temps real, concurrència, servidor com a veritat)

| Aspecte | Estat | Comentari |
|--------|--------|-----------|
| Estat dels seients coherent (disponible / reservat altre / meu / venut) | Cobert en gran part | API + Socket + UI (`butaques`, etc.) |
| Només un usuari “guanya” una reserva conflictiva | Cobert | Validació a Laravel (ex. 409) |
| Socket.IO per sincronitzar canvis entre clients | Cobert | Gateway Node + esdeveniments (`aforo-actualitzat`, `seient-*`, `compra-creada`, catàleg…) |
| Reserva temporal amb caducitat | Cobert | `expires_at`, comanda d’alliberament, etc. |
| Compra amb usuari autenticat i propagació en temps real | Cobert | Flux `comprar` + notificacions |

**Matís:** l’enunciat diu que la reserva s’envia “via Socket.IO”; en aquest projecte la **petició de reserva és HTTP (`POST /api/reservar`)** i Socket.IO **difon** l’estat. És coherent (el servidor decideix), però **cal poder explicar-ho** a la memòria/exposició si el professor ho demana literalment.

---

## 2. Funcionalitats del PDF encara incompletes o parcials

### 2.1 Part usuari

| Requisit | Estat | Què falta / millorar |
|----------|--------|----------------------|
| Vista esdeveniment: nom, data/hora, recinte, descripció, preus per tipus | Parcial | Revisar que tot es mostri clar a `sala` / fitxa de sessió; preus VIP/Normal segons categoria |
| Fins a **N** seients seleccionables (límit definit al sistema) | Pendent de verificar | Cal **límit N al backend** (i missatge clar si s’excedeix) si l’enunciat ho exigeix explícitament |
| Temporitzador visible **3–5 min** (exemple de l’enunciat) | Parcial | El backend usa **10 min**; alinear temps (o justificar a la memòria) |
| Dades personals + confirmació de compra | Cobert en flux bàsic | Revisar coherència amb validació servidor |

### 2.2 Part administrativa

| Requisit | Estat | Què falta |
|----------|--------|-----------|
| Crear/editar esdeveniment, aforament, zones, categories i preus | Parcial | Admin actual orientat a **pel·lícules i sessions**; valorar si cal modelar més “esdeveniment” com al PDF |
| **Panell en temps real**: disponibles / reservats / venuts, usuaris connectats, reserves actives, compres | **Falta** | No hi ha dashboard amb aquestes mètriques agregades |
| **Informes**: recaptació per tipus, total, % ocupació, evolució temporal | **Falta** | Cal pantalla o export + consultes |

---

## 3. Requisits tècnics i qualitat

| Requisit | Estat | Què falta |
|----------|--------|-----------|
| Validació client + servidor | Revisar | Assegurar que tot el crític està validat al servidor |
| Limitació de reserves (anti-abús / spam) | Pendent | Rate limiting o límit per usuari/sessió si cal demostrar-ho |
| Reconnexió / recàrrega | Parcial | Ja hi ha pautes (p. ex. hidratar reserva); documentar-ho |
| **Tests Cypress** (flux normal i errors) | **Falta** | No consta suite Cypress al repositori |
| **Test de concurrència** (obligatori segons enunciat) | **Falta / parcial** | Cal test explícit (feature/integration) que demostri dos clients i un sol guanyador |
| Tests unitaris addicionals | Parcial | Ampliar segons criteri del professor |

---

## 4. Lliurables del PDF

| Lliurable | Estat |
|-----------|--------|
| Repositori Git | OK (assumint publicació) |
| Publicació a l’entorn indicat | Verificar amb el professor |
| Script SQL creació + dades inicials | Revisar `base_de_dades` / seeders |
| **Manual d’instal·lació i configuració** | **Falta o incomplet** (unificar en un README/MANUAL) |
| **Diagrames** (casos d’ús, seqüència amb Socket.IO, E/R) | **Falta** o dispers en `doc/` |
| **Cypress** | **Falta** |
| **Test concurrència** | **Falta** (com a lliurable explícit) |

---

## 5. Norma “No CDN externs” (observació de l’enunciat)

L’enunciat diu que **no es poden utilitzar CDN externs**.

Al frontend solen carregar-se des de **dominis externs**:

- Fonts (Google Fonts) i **Material Symbols** (`nuxt.config` o similar).
- Opcionalment: generació de **QR** via servei extern (si encara s’usa).

**Pendent:** servir tipografies i icones **locals** (npm + `assets`/`public`) i QR sense tercers, **o** demanar aclariment al professor si aquest punt és flexible.

---

## 6. Idioma

| Requisit | Estat |
|----------|--------|
| Tot el projecte en **català** (UI + exposició) | Parcial: encara hi ha **castellà** barrejat en etiquetes i pantalles |

**Pendent:** passada de textos a català coherent (o justificar dos idiomes si el professor ho permet).

---

## 7. Ampliació opcional (WebRTC)

No és obligatòria. Si no s’ha implementat **assistència 1:1 amb WebRTC + Socket com a signaling**, només cal dir-ho a la memòria com a “fora d’abast” o futur treball.

---

## 8. Resum ràpid: prioritats suggerides

1. **Lliurables acadèmics:** manual, diagrames, **Cypress**, **test de concurrència**.
2. **Enunciat “fort”:** panell admin + **informes** (com a mínim una versió mínima).
3. **Norma CDN + català** si la nota depèn del compliment literal.
4. **Límit N butaques** i **temps de reserva** alineats amb el PDF (o justificació escrita).

---

*Document generat com a checklist; adapta prioritats segons el que demani el professorat.*
