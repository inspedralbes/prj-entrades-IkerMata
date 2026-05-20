<?php

namespace App\Support;

/**
 * Estat de reserva temporal d'un seient respecte de l'usuari autenticat (opcional).
 */
final class SeientTemporalEstat
{
    /**
     * @param  object|null  $reserva  Instància amb propietat usuari_id si hi ha reserva activa
     * @return array{seleccionat_per_altre: bool, la_meva_reserva: bool}
     */
    public static function flags(bool $isVenut, $reserva, ?string $authUserId): array
    {
        if ($isVenut) {
            return [
                'seleccionat_per_altre' => false,
                'la_meva_reserva' => false,
            ];
        }

        if ($reserva === null) {
            return [
                'seleccionat_per_altre' => false,
                'la_meva_reserva' => false,
            ];
        }

        $owner = (string) $reserva->usuari_id;
        $me = $authUserId !== null ? (string) $authUserId : null;
        $laMevaReserva = $me !== null && $owner === $me;

        return [
            'seleccionat_per_altre' => ! $laMevaReserva,
            'la_meva_reserva' => $laMevaReserva,
        ];
    }
}
