<?php

namespace App\Services;

use App\Models\CompraEntrada;
use App\Models\Sessio;

/**
 * Aforament per cartellera / sala: només entrades venudes (compra confirmada).
 * Les reserves temporals es veuen a butaques (seients), no aquí.
 */
class AforoService
{
    public static function placesDisponiblesSessio(int $sessioId): int
    {
        $sessio = Sessio::with('sala')->find($sessioId);
        if ($sessio === null || $sessio->sala === null) {
            return 0;
        }

        $capacitat = (int) $sessio->sala->capacitat;
        $venuts = CompraEntrada::where('sessio_id', $sessioId)->count();

        return max(0, $capacitat - $venuts);
    }

    public static function peliculaTeDisponibilitat(int $peliculaId): bool
    {
        $sessions = Sessio::where('esdeveniment_id', $peliculaId)->get();

        foreach ($sessions as $sessio) {
            if (self::placesDisponiblesSessio((int) $sessio->id) > 0) {
                return true;
            }
        }

        return false;
    }
}
