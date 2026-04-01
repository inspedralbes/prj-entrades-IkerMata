<?php

namespace App\Services;

//================================ NAMESPACES / IMPORTS ============

use App\Models\CompraEntrada;
use App\Models\PreuSessio;
use App\Models\Seient;
use App\Models\Sessio;
use Illuminate\Support\Facades\DB;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * Registre de compres d'entrades a la taula compres_entrades.
 */
class CompraService
{
    //================================ MÈTODES / FUNCIONS ==============

    /**
     * Intenta registrar una o més entrades per a una sessió i un usuari.
     *
     * A. Es valida la sessió i cada seient (mateixa sala, no duplicat).
     * B. Es calcula el preu segons preus_sessio o categoria.
     * C. Es persisteix dins d'una transacció.
     */
    public function registrarCompres(string $usuariId, int $sessioId, array $seientIds): array
    {
        $resultat = [];
        $resultat['ok'] = false;
        $resultat['missatge'] = '';
        $resultat['entrades'] = [];

        // A. Validació de la sessió
        $sessio = Sessio::find($sessioId);
        if ($sessio === null) {
            $resultat['missatge'] = 'Sessió no trobada';

            return $resultat;
        }

        $salaId = $sessio->sala_id;

        if (count($seientIds) === 0) {
            $resultat['missatge'] = 'Cap seient seleccionat';

            return $resultat;
        }

        DB::beginTransaction();

        try {
            foreach ($seientIds as $seientIdBrut) {
                $idNumeric = 0;
                if (is_int($seientIdBrut)) {
                    $idNumeric = $seientIdBrut;
                } else {
                    if (is_numeric($seientIdBrut)) {
                        $idNumeric = (int) $seientIdBrut;
                    }
                }

                if ($idNumeric < 1) {
                    DB::rollBack();
                    $resultat['missatge'] = 'Identificador de seient invàlid';

                    return $resultat;
                }

                // Es comprova que el seient pertany a la sala de la sessió
                $seient = Seient::where('id', $idNumeric)->where('sala_id', $salaId)->first();
                if ($seient === null) {
                    DB::rollBack();
                    $resultat['missatge'] = 'El seient no correspon a la sala de la sessió';

                    return $resultat;
                }

                // Es comprova que no hi hagi ja una compra per aquest seient i sessió
                $jaExisteix = CompraEntrada::where('sessio_id', $sessioId)->where('seient_id', $idNumeric)->exists();
                if ($jaExisteix) {
                    DB::rollBack();
                    $resultat['missatge'] = 'Un o més seients ja estan venuts per aquesta sessió';

                    return $resultat;
                }

                // B. Càlcul del preu pagat
                $preuPagat = $this->obtenirPreuPerSeientISessio($sessioId, $seient);

                // C. Inserció del registre
                $nova = new CompraEntrada;
                $nova->usuari_id = $usuariId;
                $nova->sessio_id = $sessioId;
                $nova->seient_id = $idNumeric;
                $nova->preu_pagat = $preuPagat;
                $nova->data_compra = now();
                $nova->save();

                $filaResposta = [];
                $filaResposta['id'] = $nova->id;
                $filaResposta['seient_id'] = $idNumeric;
                $filaResposta['preu_pagat'] = $preuPagat;
                $resultat['entrades'][] = $filaResposta;
            }

            DB::commit();
            $resultat['ok'] = true;
            $resultat['missatge'] = 'Compra registrada';
        } catch (\Throwable $ex) {
            DB::rollBack();
            $resultat['missatge'] = 'Error en desar la compra: '.$ex->getMessage();
        }

        return $resultat;
    }

    /**
     * Obté el preu aplicable segons preus_sessio; si no hi ha fila, usa valors per categoria.
     *
     * A. Es busca preus_sessio per sessió i categoria del seient.
     * B. Si no existeix, es distingeix VIP de la resta.
     */
    protected function obtenirPreuPerSeientISessio(int $sessioId, Seient $seient): string
    {
        // A. Preu des de la taula de preus per sessió
        $categoriaId = $seient->categoria_id;
        $filaPreu = PreuSessio::where('sessio_id', $sessioId)->where('categoria_id', $categoriaId)->first();
        if ($filaPreu !== null) {
            return (string) $filaPreu->preu;
        }

        // B. Valors per defecte segons el nom de la categoria
        $nomCategoria = '';
        $seient->load('categoria');
        if ($seient->categoria !== null) {
            $nomCategoria = $seient->categoria->nom;
        }

        if ($nomCategoria === 'VIP') {
            return '50.00';
        }

        return '20.00';
    }
}
