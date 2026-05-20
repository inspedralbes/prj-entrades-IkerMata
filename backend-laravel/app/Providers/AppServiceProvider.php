<?php

namespace App\Providers;

//================================ NAMESPACES / IMPORTS ============

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

//================================ PROPIETATS / ATRIBUTS ==========

/**
 * Proveïdor base: límits de peticions per anti-abús (sense migracions; esquema via SQL).
 */
class AppServiceProvider extends ServiceProvider
{
    //================================ MÈTODES / FUNCIONS ===========

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarLimitadorsPeticions();
    }

    /**
     * Defineix limitadors per a rutes crítiques (reserva / compra).
     */
    protected function configurarLimitadorsPeticions(): void
    {
        // A. Reserves temporals: per usuari autenticat o IP si cal
        RateLimiter::for('reservar', function (Request $request) {
            $clau = $request->ip();
            if ($request->user() !== null) {
                $clau = $request->user()->getAuthIdentifier();
            }
            $maxPerMinut = 60;
            if (app()->environment('testing')) {
                $maxPerMinut = 500;
            }

            return Limit::perMinute($maxPerMinut)->by((string) $clau);
        });

        // B. Compres: límit més estricte
        RateLimiter::for('comprar', function (Request $request) {
            $clau = $request->ip();
            if ($request->user() !== null) {
                $clau = $request->user()->getAuthIdentifier();
            }
            $maxPerMinut = 30;
            if (app()->environment('testing')) {
                $maxPerMinut = 500;
            }

            return Limit::perMinute($maxPerMinut)->by((string) $clau);
        });
    }
}
