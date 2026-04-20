<?php

namespace App\Http\Controllers;

//================================ NAMESPACES / IMPORTS ============

use App\Services\EntradaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * Controlador HTTP per consultar entrades (només GET; la persistència de noves compres segueix el flux CUD del projecte).
 */
class EntradaController extends Controller
{
    protected EntradaService $entradaService;

    public function __construct(EntradaService $entradaService)
    {
        $this->entradaService = $entradaService;
    }

    //================================ MÈTODES / FUNCIONS ==============

    /**
     * GET /api/entrades — llista les entrades de l'usuari autenticat (Sanctum).
     */
    public function indexAutenticat(Request $request): JsonResponse
    {
        $usuari = $request->user();
        $llista = $this->entradaService->llistarPerUsuari($usuari->id);

        return response()->json($llista);
    }

    /**
     * GET /api/usuaris/{usuariId}/entrades — només el mateix usuari o rol admin.
     */
    public function indexPerUsuari(Request $request, string $usuariId): JsonResponse
    {
        $auth = $request->user();
        $idAuth = (string) $auth->id;
        if ($idAuth !== $usuariId) {
            if ($auth->rol !== 'admin') {
                return response()->json(['error' => 'No autoritzat'], 403);
            }
        }

        $llista = $this->entradaService->llistarPerUsuari($usuariId);

        return response()->json($llista);
    }
}
