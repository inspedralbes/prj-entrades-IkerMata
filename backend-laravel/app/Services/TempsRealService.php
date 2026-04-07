<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

//================================ CONSTANTS ================================

const CHANNEL_SESSIO = 'sessio';
const CHANNEL_PELICULA = 'pelicula';
const CHANNEL_GLOBAL = 'temps-real';

const EVENT_COMPRA_CREADA = 'compra:creada';
const EVENT_SEIENT_SELECCIONAT = 'seient:seleccionat';
const EVENT_SEIENT_ALLIBERAT = 'seient:alliberat';
const EVENT_AFORO_ACTUALITZAT = 'aforo:actualitzat';

//================================ MÈTODES / FUNCIONS ============

class TempsRealService
{
    public static function publish(string $channel, string $event, array $data): void
    {
        $missatge = json_encode([
            'event' => $event,
            'data' => $data,
            'timestamp' => time()
        ]);
        Redis::publish($channel, $missatge);
    }

    public static function notificarCompra(int $sessioId, array $seientIds): void
    {
        self::publish(CHANNEL_SESSIO, EVENT_COMPRA_CREADA, [
            'sessio_id' => $sessioId,
            'seient_ids' => $seientIds
        ]);
    }

    public static function notificarSeleccionat(int $sessioId, int $seientId, string $usuariId): void
    {
        self::publish(CHANNEL_SESSIO, EVENT_SEIENT_SELECCIONAT, [
            'sessio_id' => $sessioId,
            'seient_id' => $seientId,
            'usuari_id' => $usuariId
        ]);
    }

    public static function notificarAlliberat(int $sessioId, int $seientId): void
    {
        self::publish(CHANNEL_SESSIO, EVENT_SEIENT_ALLIBERAT, [
            'sessio_id' => $sessioId,
            'seient_id' => $seientId
        ]);
    }

    public static function notificarAforoActualitzat(int $peliId, int $sessioId, int $aforoDisponible, bool $hiHaDisponibilitat): void
    {
        // Notificació per a la sessió (sala.vue)
        self::publish(CHANNEL_SESSIO, EVENT_AFORO_ACTUALITZAT, [
            'sessio_id' => $sessioId,
            'aforo_disponible' => $aforoDisponible
        ]);

        // Notificació per a la pel·lícula (index.vue)
        self::publish(CHANNEL_PELICULA, EVENT_AFORO_ACTUALITZAT, [
            'pelicula_id' => $peliId,
            'hi_ha_disponibilitat' => $hiHaDisponibilitat
        ]);
    }
}