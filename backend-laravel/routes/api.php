<?php

use App\Services\AforoService;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\EntradaController;
use App\Models\Peli;
use App\Models\Sessio;
use App\Models\Seient;

Route::post('/register', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);

// Redis health check — infra temps real (Agenttempsreal.md, tasca 1)
Route::get('/health/redis', function () {
    try {
        $pong = Redis::connection()->ping();

        return response()->json([
            'ok' => true,
            'redis' => $pong,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'ok' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/peliculas', function () {
    return Peli::all()->map(function ($p) {
        return [
            'id' => $p->id,
            'titol' => $p->titol,
            'imatge_url' => $p->imatge_url,
            'hi_ha_disponibilitat' => AforoService::peliculaTeDisponibilitat((int) $p->id),
        ];
    });
});

Route::get('/peliculas/{id}', function ($id) {
    $p = Peli::find($id);
    if (! $p) {
        return response()->json(['error' => 'Pelicula no encontrada'], 404);
    }
    return [
        'id' => $p->id,
        'titol' => $p->titol,
        'imatge_url' => $p->imatge_url,
        'descripcio' => $p->descripcio
    ];
});

Route::get('/debug-sessions', function () {
    return [
        'pelis_count' => Peli::count(),
        'sessions_count' => Sessio::count(),
        'seients_count' => Seient::count(),
        'all_sessions' => Sessio::all()
    ];
});

Route::get('/peliculas/{id}/sesiones', function ($id) {
    return Sessio::where('esdeveniment_id', $id)->with('sala')->get()->map(function ($s) {
        return [
            'id' => $s->id,
            'uuid' => $s->uuid,
            'sala_nom' => $s->sala->nom,
            'data_hora' => $s->data_hora,
            'aforo_disponible' => AforoService::placesDisponiblesSessio((int) $s->id),
        ];
    });
});

Route::get('/sesiones/{id}/asientos', function ($id) {
    $sessio = Sessio::find($id);
    if (! $sessio) {
        return [];
    }

    $seients = Seient::where('sala_id', $sessio->sala_id)
        ->with('categoria')
        ->orderBy('fila')
        ->orderBy('numero')
        ->get();

    // Obtenim seients ja venuts
    $venuts = \App\Models\CompraEntrada::where('sessio_id', $id)->pluck('seient_id')->toArray();

    // Obtenim reserves temporals actives
    $reserves = \App\Models\ReservaTemporal::where('sessio_id', $id)
        ->where('expires_at', '>', now())
        ->get();

    return $seients->map(function ($s) use ($venuts, $reserves) {
        $isVenut = in_array($s->id, $venuts);
        $reserva = $reserves->firstWhere('seient_id', $s->id);

        return [
            'id' => $s->id,
            'fila' => $s->fila,
            'numero' => $s->numero,
            'categoria' => $s->categoria->nom,
            'color' => $s->categoria->color_hex,
            'reservat' => $isVenut,
            'seleccionat_per_altre' => ! $isVenut && $reserva !== null,
            'usuari_reserva' => $reserva ? $reserva->usuari_id : null
        ];
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/usuari', [AuthController::class, 'usuari']);
    Route::get('/entrades', [EntradaController::class, 'indexAutenticat']);
    Route::get('/usuaris/{usuariId}/entrades', [EntradaController::class, 'indexPerUsuari']);
    Route::post('/comprar', [CompraController::class, 'desar']);
    Route::post('/reservar', [CompraController::class, 'reservarTemporal']);
});
