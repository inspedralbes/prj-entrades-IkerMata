<?php

namespace App\Console\Commands;

use App\Models\ReservaTemporal;
use App\Services\TempsRealService;
use Illuminate\Console\Command;

class AlliberarReservesExpirades extends Command
{
    protected $signature = 'reserves:alliberar-expirades';

    protected $description = 'Elimina reserves temporals caducades i notifica alliberament (butaques) via Redis';

    public function handle(): int
    {
        $expired = ReservaTemporal::where('expires_at', '<=', now())->get();

        if ($expired->isEmpty()) {
            return self::SUCCESS;
        }

        foreach ($expired as $r) {
            TempsRealService::notificarAlliberat((int) $r->sessio_id, (int) $r->seient_id);
        }

        ReservaTemporal::whereIn('id', $expired->pluck('id'))->delete();

        return self::SUCCESS;
    }
}
