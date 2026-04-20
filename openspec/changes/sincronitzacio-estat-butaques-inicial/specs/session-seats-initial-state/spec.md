## ADDED Requirements

### Requirement: API inicial inclou estat de reserva temporal per seient

La resposta de l’endpoint que llista els asientos d’una sessió SHALL incloure, per a cada seient, indicadors derivats de les reserves temporals actives (no caducades) per a aquella sessió, de manera que el client pugui renderitzar la graella sense esperar esdeveniments Socket.io.

#### Scenario: Seient amb reserva temporal d’un altre usuari

- **WHEN** existeix una reserva temporal vàlida per al `seient_id` i la `sessio_id` sol·licitades i el titular no és l’usuari autenticat
- **THEN** el recurs del seient SHALL incloure `seleccionat_per_altre` amb valor cert (o camp equivalent documentat al contracte d’API)

#### Scenario: Seient sense reserva temporal d’un altre usuari

- **WHEN** no hi ha reserva temporal activa d’un altre usuari per a aquest seient en aquesta sessió
- **THEN** el recurs del seient SHALL incloure `seleccionat_per_altre` amb valor fals (o camp equivalent)

### Requirement: Distinció de la pròpia reserva temporal quan hi ha autenticació

Quan la petició identifica un usuari autenticat, la resposta SHALL permetre distinguir si la reserva temporal activa del seient és pròpia (per restaurar la UI en recàrrega).

#### Scenario: Usuari amb la seva pròpia reserva temporal

- **WHEN** l’usuari autenticat té una reserva temporal activa per al seient en la sessió
- **THEN** el recurs del seient SHALL incloure un camp booleà (p. ex. `la_meva_reserva`) cert i `seleccionat_per_altre` fals per a aquest cas

#### Scenario: Petició sense identitat d’usuari

- **WHEN** el client no envia autenticació vàlida i el endpoint roman accessible
- **THEN** el sistema SHALL definir un comportament explícit (p. ex. `la_meva_reserva` sempre fals o camp absent) documentat al contracte, sense trencar la resposta

### Requirement: Coherència amb actualitzacions en temps real

El client SHALL poder fusionar la resposta inicial amb actualitzacions posteriors via Socket.io sense estat contradictori persistent respecte al servidor immediatament després de la càrrega.

#### Scenario: Baseline i després esdeveniment socket

- **WHEN** la pàgina rep la llista inicial d’asientos amb camps de reserva temporal i, tot seguit, rep un esdeveniment de selecció o alliberament del mateix seient
- **THEN** l’estat mostrat SHALL reflectir l’esdeveniment sense requerir una nova petició HTTP obligatòria
