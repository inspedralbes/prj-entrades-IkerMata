<?php

namespace App\Http\Controllers;

use App\Services\OmdbService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Importació de metadades des d’OMDb (només admin; clau API al servidor).
 */
class AdminOmdbController extends Controller
{
    public function __construct(
        protected OmdbService $omdb
    ) {}

    /**
     * GET /api/admin/omdb?i=tt3896198 — retorna camps compatibles amb el formulari de pel·lícula.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $imdbId = (string) $request->query('i', '');
        try {
            $mapped = $this->omdb->peliculaPerImdbId($imdbId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        if ($mapped === null) {
            return response()->json(['error' => 'No s’ha trobat cap pel·lícula amb aquest IMDb ID a OMDb.'], 404);
        }

        return response()->json($mapped);
    }

    /**
     * GET /api/admin/omdb/search?s=guardians — llista de pel·lícules per triar IMDb ID.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null || $user->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $s = (string) $request->query('s', '');
        try {
            $results = $this->omdb->cercar($s);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }

        return response()->json(['results' => $results]);
    }
}
