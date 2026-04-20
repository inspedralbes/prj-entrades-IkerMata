<?php

namespace App\Http\Controllers;

use App\Services\PeliculaImportService;
use App\Services\TempsRealService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST JSON → diverses files a `pelis` (OMDb o format intern).
 */
class AdminPeliculaImportController extends Controller
{
    public function __construct(
        protected PeliculaImportService $import
    ) {}

    public function store(Request $request): JsonResponse
    {
        if ($request->user()?->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $raw = json_decode($request->getContent(), true);
        if ($raw === null && json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['error' => 'JSON invàlid: '.json_last_error_msg()], 422);
        }
        if (! is_array($raw)) {
            return response()->json(['error' => 'El cos ha de ser un objecte o llista JSON.'], 422);
        }

        try {
            $models = $this->import->importar($raw);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        TempsRealService::notificarCatalogPelicules();

        return response()->json([
            'imported' => count($models),
            'peliculas' => collect($models)->values()->all(),
        ], 201);
    }
}
