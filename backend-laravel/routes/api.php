<?php

use App\Services\AforoService;
use App\Support\SeientTemporalEstat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
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
            'descripcio' => $p->descripcio,
            'imatge_url' => $p->imatge_url,
            'durada_minuts' => $p->durada_minuts,
            'estat' => $p->estat,
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

Route::get('/sesiones/{id}/asientos', function (Request $request, $id) {
    $sessio = Sessio::find($id);
    if (! $sessio) {
        return [];
    }

    $authUserId = null;
    $bearer = $request->bearerToken();
    if ($bearer) {
        $accessToken = PersonalAccessToken::findToken($bearer);
        $tokenable = $accessToken?->tokenable;
        if ($tokenable instanceof User) {
            $authUserId = (string) $tokenable->getAuthIdentifier();
        }
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

    return $seients->map(function ($s) use ($venuts, $reserves, $authUserId) {
        $isVenut = in_array($s->id, $venuts);
        $reserva = $reserves->firstWhere('seient_id', $s->id);
        $temporal = SeientTemporalEstat::flags($isVenut, $reserva, $authUserId);

        return [
            'id' => $s->id,
            'fila' => $s->fila,
            'numero' => $s->numero,
            'categoria' => $s->categoria->nom,
            'color' => $s->categoria->color_hex,
            'reservat' => $isVenut,
            'seleccionat_per_altre' => $temporal['seleccionat_per_altre'],
            'la_meva_reserva' => $temporal['la_meva_reserva'],
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

    // Rutes Admin - CRUD Pel·lícules
    Route::post('/peliculas', function (Illuminate\Http\Request $request) {
        $user = $request->user();
        if ($user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }
        $peli = Peli::create($request->validate([
            'titol' => 'required|string|max:255',
            'descripcio' => 'required|string',
            'imatge_url' => 'nullable|string',
            'durada_minuts' => 'required|integer|min:1',
            'estat' => 'nullable|in:actiu,inactiu'
        ]));
        return response()->json($peli, 201);
    });

    Route::put('/peliculas/{id}', function (Illuminate\Http\Request $request, $id) {
        $user = $request->user();
        if ($user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }
        $peli = Peli::findOrFail($id);
        $peli->update($request->validate([
            'titol' => 'sometimes|string|max:255',
            'descripcio' => 'sometimes|string',
            'imatge_url' => 'nullable|string',
            'durada_minuts' => 'sometimes|integer|min:1',
            'estat' => 'nullable|in:actiu,inactiu'
        ]));
        return response()->json($peli);
    });

    Route::delete('/peliculas/{id}', function (Illuminate\Http\Request $request, $id) {
        $user = $request->user();
        if ($user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }
        $peli = Peli::findOrFail($id);
        $peli->delete();
        return response()->json(['ok' => true]);
    });
});
