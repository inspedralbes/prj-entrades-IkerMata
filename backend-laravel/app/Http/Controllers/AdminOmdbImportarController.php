<?php

namespace App\Http\Controllers;

use App\Models\Peli;
use App\Services\OmdbService;
use App\Services\TempsRealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Baixa metadades des de l’API OMDb i fa INSERT a `pelis` (només admin).
 */
class AdminOmdbImportarController extends Controller
{
    public function store(Request $request, OmdbService $omdb): JsonResponse
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        if (! $omdb->apiKeyConfigured()) {
            return response()->json([
                'error' => 'Configura OMDB_API_KEY al servidor (fitxer .env de Laravel o variable al docker-compose del servei app).',
            ], 503);
        }

        $ids = $request->input('imdb_ids');
        if (! is_array($ids)) {
            $one = $request->input('imdb_id');
            $ids = $one !== null && $one !== '' ? [$one] : [];
        }

        $ids = array_values(array_unique(array_filter(array_map(function ($id) {
            return strtolower(trim((string) $id));
        }, $ids))));

        if (count($ids) === 0) {
            return response()->json([
                'error' => 'Envia «imdb_ids»: [ «tt…», … ] o «imdb_id»: «tt…» al cos JSON.',
            ], 422);
        }

        $created = [];
        $errors = [];

        foreach ($ids as $id) {
            if (! preg_match('/^tt\d+$/', $id)) {
                $errors[$id] = 'Format invàlid (cal tt + números).';

                continue;
            }
            try {
                $attrs = $omdb->atributsPerCrearPeliDesOmdb($id);
                if ($attrs === null) {
                    $errors[$id] = 'No resposta o no trobat a OMDb.';

                    continue;
                }
                $created[] = Peli::create($attrs);
            } catch (\InvalidArgumentException $e) {
                $errors[$id] = $e->getMessage();
            } catch (\Throwable $e) {
                $errors[$id] = $e->getMessage();
            }
        }

        if (count($created) === 0) {
            return response()->json([
                'error' => 'No s’ha pogut importar cap pel·lícula.',
                'per_id' => $errors,
            ], 422);
        }

        TempsRealService::notificarCatalogPelicules();

        return response()->json([
            'imported' => count($created),
            'peliculas' => collect($created)->values()->all(),
            'errors' => count($errors) ? $errors : null,
        ], 201);
    }

    /**
     * Cerca per títol a OMDb i importa les primeres N pel·lícules (detall complet per cada IMDb ID).
     */
    public function importarDesCerca(Request $request, OmdbService $omdb): JsonResponse
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        if (! $omdb->apiKeyConfigured()) {
            return response()->json([
                'error' => 'Configura OMDB_API_KEY al servidor (fitxer .env de Laravel o variable al docker-compose del servei app).',
            ], 503);
        }

        $validated = $request->validate([
            'q' => 'required|string|min:2|max:120',
            'limit' => 'sometimes|integer|min:1|max:15',
        ]);

        $limit = isset($validated['limit']) ? (int) $validated['limit'] : 8;
        $limit = min(15, max(1, $limit));

        try {
            $hits = $omdb->cercar($validated['q']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $hits = array_slice($hits, 0, $limit);

        if (count($hits) === 0) {
            return response()->json([
                'error' => 'Cap pel·lícula trobada per aquesta cerca (o només sèries/altres tipus).',
                'imported' => 0,
                'peliculas' => [],
            ], 422);
        }

        $created = [];
        $errors = [];

        foreach ($hits as $row) {
            $id = strtolower(trim((string) ($row['imdbID'] ?? '')));
            if ($id === '' || ! preg_match('/^tt\d+$/', $id)) {
                continue;
            }
            try {
                $attrs = $omdb->atributsPerCrearPeliDesOmdb($id);
                if ($attrs === null) {
                    $errors[$id] = 'No resposta o no trobat a OMDb.';

                    continue;
                }
                $created[] = Peli::create($attrs);
            } catch (\InvalidArgumentException $e) {
                $errors[$id] = $e->getMessage();
            } catch (\Throwable $e) {
                $errors[$id] = $e->getMessage();
            }

            usleep(150000);
        }

        if (count($created) === 0) {
            return response()->json([
                'error' => 'No s’ha pogut importar cap pel·lícula des dels resultats de cerca.',
                'per_id' => $errors,
            ], 422);
        }

        TempsRealService::notificarCatalogPelicules();

        return response()->json([
            'imported' => count($created),
            'peliculas' => collect($created)->values()->all(),
            'errors' => count($errors) ? $errors : null,
            'cerca' => $validated['q'],
        ], 201);
    }

    /**
     * Importa fins a 25 pel·lícules des d’una llista fixa (config/omdb.php). Salta si el títol ja existeix.
     */
    public function importarDemo(Request $request, OmdbService $omdb): JsonResponse
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        if (! $omdb->apiKeyConfigured()) {
            return response()->json([
                'error' => 'Configura OMDB_API_KEY al servidor (fitxer .env de Laravel o variable al docker-compose del servei app).',
            ], 503);
        }

        $ids = config('omdb.demo_imdb_ids', []);
        if (! is_array($ids) || count($ids) === 0) {
            return response()->json(['error' => 'Llista demo no configurada (config/omdb.php).'], 500);
        }

        $ids = array_slice(array_values(array_unique(array_map(function ($id) {
            return strtolower(trim((string) $id));
        }, $ids))), 0, 25);

        $created = [];
        $errors = [];
        $skipped = [];

        foreach ($ids as $id) {
            if (! preg_match('/^tt\d+$/', $id)) {
                continue;
            }
            try {
                $attrs = $omdb->atributsPerCrearPeliDesOmdb($id);
                if ($attrs === null) {
                    $errors[$id] = 'No resposta o no trobat a OMDb.';

                    continue;
                }
                if (Peli::where('titol', $attrs['titol'])->exists()) {
                    $skipped[$id] = 'Ja existeix: '.$attrs['titol'];

                    continue;
                }
                $created[] = Peli::create($attrs);
            } catch (\InvalidArgumentException $e) {
                $errors[$id] = $e->getMessage();
            } catch (\Throwable $e) {
                $errors[$id] = $e->getMessage();
            }

            usleep(150000);
        }

        if (count($created) === 0) {
            return response()->json([
                'error' => 'No s’ha importat cap pel·lícula nova.',
                'per_id' => count($errors) ? $errors : null,
                'skipped' => count($skipped) ? $skipped : null,
            ], 422);
        }

        TempsRealService::notificarCatalogPelicules();

        return response()->json([
            'imported' => count($created),
            'peliculas' => collect($created)->values()->all(),
            'errors' => count($errors) ? $errors : null,
            'skipped' => count($skipped) ? $skipped : null,
        ], 201);
    }
}
