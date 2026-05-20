<?php

return [

    /*
    | Màxim de seients que un usuari pot tenir reservats alhora per sessió (selecció + compra).
    */
    'max_seients_per_sessio' => (int) env('MAX_SEIENTS_RESERVA_SESSIO', 8),

    /*
    | Durada de la reserva temporal (minuts). Enunciat: exemple 3–5 min; per defecte 5.
    */
    'reserva_temporal_minuts' => (int) env('RESERVA_TEMPORAL_MINUTS', 5),

];
