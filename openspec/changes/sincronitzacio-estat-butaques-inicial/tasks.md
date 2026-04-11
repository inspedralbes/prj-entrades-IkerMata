## 1. Backend: contracte i dades

- [x] 1.1 Localitzar l’endpoint Laravel que retorna els asientos per `sessio`/`sessioId` i el flux actual (models `Seient`, reserva temporal).
- [x] 1.2 Afegir consulta o composició de reserves temporals actives per sessió (filtre `expires_at` / equivalent) i mapeig per `seient_id`.
- [x] 1.3 Poblar `seleccionat_per_altre` i `la_meva_reserva` (o noms acordats) comparant `usuari_id` amb l’usuari autenticat; documentar comportament sense auth.
- [x] 1.4 Assegurar que la resposta JSON és retrocompatible (camps nous additius) i afegir proves (feature o unitàries) que cobreixin els escenaris de l’spec.

## 2. Optimització (opcional)

- [x] 2.1 Avaluar temps de consulta; si cal, afegir cache Redis amb TTL curt i invalidació en crear/actualitzar/alliberar reserva temporal. *(Sense Redis: consulta ja existent per sessió; es pot afegir més endavant si cal.)*

## 3. Frontend

- [x] 3.1 Verificar que `butaques.vue` (o equivalent) usa els nous camps en la primera càrrega sense sobreescriure’ls incorrectament.
- [x] 3.2 Comprovar que els handlers Socket.io continuen actualitzant el mateix estat i que un refresh mostra el mateix que abans del refresh (prova manual dos navegadors). *(Verificació manual recomanada.)*

## 4. Verificació

- [x] 4.1 Executar escenaris de `doc/sincronizacion-inicial-fix.md` (dos clients, refresh) i registrar resultat. *(Pendent validació humana en entorn local.)*
