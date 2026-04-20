<?php

use App\Services\AforoService;
use App\Services\TempsRealService;
use App\Support\SeientTemporalEstat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Controllers\AdminOmdbController;
use App\Http\Controllers\AdminOmdbImportarController;
use App\Http\Controllers\AdminPeliculaImportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminInformesController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\EntradaController;
use App\Models\CategoriaSeient;
use App\Models\CompraEntrada;
use App\Models\Peli;
use App\Models\PreuSessio;
use App\Models\Sala;
use App\Models\Sessio;
use App\Models\Seient;

Route::post('/register', [AuthController::class, 'registrar']);
Route::post('/login', [AuthController::class, 'login']);
/** Logout sense auth:sanctum: revoca el token si és vàlid; si no (p. ex. BD reiniciada), resposta 200 igual. */
Route::post('/logout', [AuthController::class, 'logout']);

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

Route::get('/configuracio-venda', function () {
    return response()->json([
        'max_seients_per_sessio' => (int) config('entradas.max_seients_per_sessio'),
        'reserva_temporal_minuts' => (int) config('entradas.reserva_temporal_minuts'),
    ]);
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
        'descripcio' => $p->descripcio,
        'durada_minuts' => $p->durada_minuts,
        'estat' => $p->estat,
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
    return Sessio::where('esdeveniment_id', $id)
        ->with(['sala', 'preus.categoria'])
        ->get()
        ->map(function ($s) {
            $preus = $s->preus->map(function ($ps) {
                return [
                    'categoria' => $ps->categoria?->nom ?? '—',
                    'preu' => (string) $ps->preu,
                ];
            });

            return [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'sala_id' => $s->sala_id,
                'sala_nom' => $s->sala->nom,
                'data_hora' => $s->data_hora,
                'aforo_disponible' => AforoService::placesDisponiblesSessio((int) $s->id),
                'preus' => $preus,
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

        $fila = [
            'id' => $s->id,
            'fila' => $s->fila,
            'numero' => $s->numero,
            'categoria' => $s->categoria->nom,
            'color' => $s->categoria->color_hex,
            'reservat' => $isVenut,
            'seleccionat_per_altre' => $temporal['seleccionat_per_altre'],
            'la_meva_reserva' => $temporal['la_meva_reserva'],
        ];

        if ($temporal['la_meva_reserva'] && $reserva !== null && $reserva->expires_at !== null) {
            $fila['meva_expiracio_iso'] = $reserva->expires_at->toIso8601String();
        }

        return $fila;
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/usuari', [AuthController::class, 'usuari']);
    Route::get('/entrades', [EntradaController::class, 'indexAutenticat']);
    Route::get('/usuaris/{usuariId}/entrades', [EntradaController::class, 'indexPerUsuari']);
    Route::post('/comprar', [CompraController::class, 'desar'])->middleware('throttle:comprar');
    Route::post('/reservar', [CompraController::class, 'reservarTemporal'])->middleware('throttle:reservar');

    Route::get('/admin/panell-temps-real', [AdminInformesController::class, 'panellTempsReal']);
    Route::get('/admin/informes-resum', [AdminInformesController::class, 'informesResum']);

    /** OMDb: importació de metadades (clau OMDB_API_KEY només al servidor) */
    Route::get('/admin/omdb/search', [AdminOmdbController::class, 'search']);
    Route::get('/admin/omdb', [AdminOmdbController::class, 'show']);

    /** JSON (OMDb o intern) → INSERT a `pelis` */
    Route::post('/admin/peliculas/import', [AdminPeliculaImportController::class, 'store']);

    /** OMDb API → INSERT directe a `pelis` (imdb_ids al cos JSON) */
    Route::post('/admin/peliculas/fetch-omdb', [AdminOmdbImportarController::class, 'store']);

    /** OMDb: cerca per títol i importa fins a N pel·lícules */
    Route::post('/admin/peliculas/fetch-omdb-cerca', [AdminOmdbImportarController::class, 'importarDesCerca']);

    /** OMDb: importa fins a 25 pel·lícules des de config/omdb.php (llista recomanada) */
    Route::post('/admin/peliculas/fetch-omdb-demo', [AdminOmdbImportarController::class, 'importarDemo']);

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
        TempsRealService::notificarCatalogPelicules();

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
        TempsRealService::notificarCatalogPelicules();

        return response()->json($peli);
    });

    Route::delete('/peliculas/{id}', function (Illuminate\Http\Request $request, $id) {
        $user = $request->user();
        if ($user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }
        $peli = Peli::findOrFail($id);
        $peli->delete();
        TempsRealService::notificarCatalogPelicules();

        return response()->json(['ok' => true]);
    });

    // Admin: sales (selector sala)
    Route::get('/sales', function (Request $request) {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        return Sala::orderBy('id')->get(['id', 'nom', 'capacitat']);
    });

    // Admin: CRUD sessions (passis)
    Route::post('/peliculas/{peliId}/sesiones', function (Request $request, $peliId) {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        Peli::findOrFail($peliId);

        $dades = $request->validate([
            'sala_id' => 'required|integer|exists:sales,id',
            'data_hora' => 'required|date',
        ]);

        $sessio = Sessio::create([
            'esdeveniment_id' => (int) $peliId,
            'sala_id' => $dades['sala_id'],
            'data_hora' => $dades['data_hora'],
        ]);

        foreach (CategoriaSeient::orderBy('id')->get() as $cat) {
            $esVip = strcasecmp((string) $cat->nom, 'VIP') === 0;
            PreuSessio::create([
                'sessio_id' => $sessio->id,
                'categoria_id' => $cat->id,
                'preu' => $esVip ? 9.70 : 6.70,
            ]);
        }

        $sessio->load('sala');

        TempsRealService::notificarCatalogSessions((int) $peliId);

        return response()->json([
            'id' => $sessio->id,
            'uuid' => $sessio->uuid,
            'sala_id' => $sessio->sala_id,
            'sala_nom' => $sessio->sala->nom,
            'data_hora' => $sessio->data_hora,
            'aforo_disponible' => AforoService::placesDisponiblesSessio((int) $sessio->id),
        ], 201);
    });

    Route::put('/sesiones/{id}', function (Request $request, $id) {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $sessio = Sessio::findOrFail($id);

        $dades = $request->validate([
            'sala_id' => 'sometimes|integer|exists:sales,id',
            'data_hora' => 'sometimes|date',
        ]);

        $sessio->update($dades);
        $sessio->load('sala');

        TempsRealService::notificarCatalogSessions((int) $sessio->esdeveniment_id);

        return response()->json([
            'id' => $sessio->id,
            'uuid' => $sessio->uuid,
            'sala_id' => $sessio->sala_id,
            'sala_nom' => $sessio->sala->nom,
            'data_hora' => $sessio->data_hora,
            'aforo_disponible' => AforoService::placesDisponiblesSessio((int) $sessio->id),
        ]);
    });

    Route::delete('/sesiones/{id}', function (Request $request, $id) {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $sessio = Sessio::findOrFail($id);

        if (CompraEntrada::where('sessio_id', (int) $id)->exists()) {
            return response()->json([
                'error' => 'No es pot eliminar: hi ha entrades venudes per aquesta sessió.',
            ], 422);
        }

        $peliculaId = (int) $sessio->esdeveniment_id;
        $sessio->delete();

        TempsRealService::notificarCatalogSessions($peliculaId);

        return response()->json(['ok' => true]);
    });
});
