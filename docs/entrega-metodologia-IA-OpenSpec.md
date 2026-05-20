---
title: "Desenvolupament guiat per especificació (OpenSpec) amb suport d'IA"
subtitle: "Sincronització de l'estat inicial de butaques amb reserves temporals"
author: "Iker Mata García"
date: "2026"
lang: ca
---

# 1. Explicació de la funcionalitat

La pantalla de selecció de butaques carrega la graella de seients mitjançant una petició HTTP a l’API (Laravel) i, en paral·lel, es connecta al canal de temps real (Socket.io via gateway) per rebre canvis sobre reserves temporals d’altres usuaris.

**Problema abans del canvi:** el camp que indica que un seient està ocupat temporalment per una altra persona (`seleccionat_per_altre`) només s’actualitzava quan arribaven esdeveniments nous pel socket. Qualsevol usuari que **recarregués la pàgina** o **entrés tard** no rebia esdeveniments passats i veia seients com a disponibles quan ja hi havia una reserva temporal activa, fins que el servidor intervenia en una acció posterior.

**Solució implementada:** l’endpoint que retorna els seients d’una sessió ara inclou, per a cada seient, informació derivada de les **reserves temporals no caducades** emmagatzemades a la base de dades: `seleccionat_per_altre`, `la_meva_reserva` (quan hi ha token vàlid i la reserva és pròpia) i, si escau, `meva_expiracio_iso` per al temporitzador. El socket segueix sent la font d’**actualitzacions incrementals** després de la primera càrrega; el disseny manté una sola lectura coherent de l’estat persistent al servidor.

---

# 2. Procés seguit amb la IA

1. **Definició amb OpenSpec (`opsx:propose`):** es va crear el canvi `sincronitzacio-estat-butaques-inicial` amb `proposal.md` (motivació i abast), `design.md` (context, objectius, restriccions, decisions i riscos) i la capability `session-seats-initial-state` amb `spec.md` (requisits i escenaris). Es va generar `tasks.md` com a pla d’implementació verificable.

2. **Implementació guiada (`opsx:apply`):** es va demanar a l’agent que implementés el contracte al backend (consulta de `ReservaTemporal`, classe `SeientTemporalEstat`, integració a la ruta d’asientos, proves unitàries) i que adaptés el store Pinia i la pàgina de butaques per consumir els nous camps a la primera càrrega sense trencar el flux del socket.

3. **Iteració controlada:** davant cada desviació (p. ex. seient ja venut, petició sense autenticació, ordre entre resposta HTTP i esdeveniments socket), es va corregir **referint-se a l’spec** o als tests, no només “provant prompts” a l’atzar.

El detall de prompts i correccions figura a `docs/prompts-log.md`.

---

# 3. Principals problemes trobats

- **Interpretació dels casos límit:** calia deixar clar que un seient **venut** no ha de mostrar conflicte de reserva temporal als flags (la venda preval).

- **Autenticació opcional:** l’endpoint pot respondre sense usuari identificat; calia definir explícitament que `la_meva_reserva` només és cert amb coincidència d’identitat i token vàlid.

- **Ordre d’esdeveniments:** si els missatges del socket arriben abans que la llista HTTP estigui pintada, cal mecanisme de cua o flush per no perdre coherència (abordat al frontend amb la cua d’esdeveniments pendents).

---

# 4. Decisions preses (prompts vs especificació)

- **Especificació:** es va mantenir estable en el seu objectiu (baseline HTTP alineat amb DB + continuïtat per socket). Només s’van fer **microajustos de redacció** o noms de camps per coincidir amb el codi existent (`meva_expiracio_iso`, etc.).

- **Prompts i codi:** la major part del refinament va ser **precisar prompts** (“extreure lògica a classe testejable”, “cobrir testos sense auth i amb auth”) i afegir **proves automatitzades** com a xarxa de seguretat davant regressions.

- **Redis:** el disseny el contemplava com a opcional; la primera iteració va optar per **consulta SQL directa** sense cache, coherent amb el comentari a `tasks.md`.

---

# 5. Valoració crítica (anàlisi obligatòria)

**L’agent ha seguit realment l’especificació?**  
En conjunt **sí**: els requisits de l’spec (camps a la resposta inicial, distinció de la pròpia reserva amb autenticació, coherència amb actualitzacions posteriors per socket) tenen correspondència directa al codi als fitxers indicats al registre de prompts. La part més delicada (ordre HTTP vs socket) depèn també de decisions de frontend que van caldre **explicitar** al prompt; sense això, la IA tendeix a tocar només el backend.

**Quantes iteracions han estat necessàries?**  
Aproximadament **dues a tres** voltes de refinament després del primer esborrany: venuts vs temporal, comportament sense token, i verificació del flux amb cua d’esdeveniments.

**On falla més la IA?**  
- **Interpretació:** casos límit i supòsits implícits (auth opcional, precedència venut/temporal).  
- **Execució:** sovint encerta l’estructura però calen **proves** per agafar errors de detall.  
- **Coherència:** pot proposar canvis al client o al servidor de forma desigual; cal **guiar** amb `tasks.md` i amb referències a fitxers concrets.

**S’ha hagut de modificar l’especificació o només els prompts?**  
Principalment **prompts i codi**; l’spec conceptual **no** va canviar de direcció. Els ajustos al document van ser de **claredat i alineació amb el contracte JSON** ja existent al projecte.

---

# 6. Conclusió

La metodologia Spec-Driven Development amb OpenSpec ha permès **ancorar** el treball de l’agent a requisits verificables i a una llista de tasques ordenada. La traçabilitat (`docs/prompts-log.md`) és el que converteix el resultat funcional en **evidència de procés**, que és el que avalua aquesta assignatura.
