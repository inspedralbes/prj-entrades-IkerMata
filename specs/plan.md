# Estratègia d’implementació (pla)

**Font canònica:** `openspec/changes/sincronitzacio-estat-butaques-inicial/tasks.md` (+ detalls de desplegament a `design.md`)

---

## 1. Backend: contracte i dades

- Localitzar l’endpoint Laravel que retorna els asientos per `sessio`/`sessioId` i el flux actual (models `Seient`, reserva temporal).
- Afegir consulta o composició de reserves temporals actives per sessió (filtre `expires_at` / equivalent) i mapeig per `seient_id`.
- Poblar `seleccionat_per_altre` i `la_meva_reserva` comparant `usuari_id` amb l’usuari autenticat; documentar comportament sense auth.
- Assegurar resposta JSON retrocompatible (camps nous additius) i proves (unitàries/feature) que cobreixin els escenaris de l’spec.

## 2. Optimització (opcional)

- Avaluar temps de consulta; si cal, cache Redis amb TTL curt i invalidació en crear/actualitzar/alliberar reserva temporal.

## 3. Frontend

- Verificar que la pàgina de butaques i el store usen els nous camps en la primera càrrega sense sobreescriure’ls incorrectament.
- Comprovar que els handlers Socket.io continuen actualitzant el mateix estat i que un refresh mostra el mateix que abans (prova manual dos navegadors).

## 4. Verificació

- Validar sincronització inicial (dos clients, refresh) i registrar resultat.

---

## Desplegament (resum)

1. Desplegar canvis de backend (contracte nou, retrocompatible si els camps són additius).  
2. Desplegar frontend que llegeixi els nous camps.  
3. Monitoritzar errors i latència de l’endpoint.

Vegeu **Migration Plan** i **Rollback** a `openspec/.../design.md`.
