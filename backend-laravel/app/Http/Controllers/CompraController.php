<?php

namespace App\Http\Controllers;

//================================ NAMESPACES / IMPORTS ============

use App\Services\CompraService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * Rep sol·licituds de compra des del gateway Node i delega al servei de domini.
 */
class CompraController extends Controller
{
    protected CompraService $compraService;

    public function __construct(CompraService $compraService)
    {
        $this->compraService = $compraService;
    }

    //================================ MÈTODES / FUNCIONS ==============

    /**
     * POST /api/comprar — desa les entrades seleccionades (usuari per defecte si no s'envia).
     *
     * A. Es llegeixen i validen els camps del cos JSON.
     * B. Es crida el servei de registre de compres.
     * C. Es retorna el codi HTTP adequat.
     */
    public function desar(Request $request): JsonResponse
    {
        // A. Lectura dels paràmetres (usuari autenticat amb Sanctum)
        $usuari = $request->user();
        if ($usuari === null) {
            return response()->json(['error' => 'Cal iniciar sessió'], 401);
        }

        $usuariId = $usuari->id;

        $sessioIdBrut = $request->input('sessioId');
        $seientIds = $request->input('seientIds');

        if ($sessioIdBrut === null) {
            return response()->json(['error' => 'Falta sessioId'], 422);
        }

        if (! is_numeric($sessioIdBrut)) {
            return response()->json(['error' => 'sessioId no vàlid'], 422);
        }

        $sessioId = (int) $sessioIdBrut;

        if (! is_array($seientIds)) {
            return response()->json(['error' => 'seientIds ha de ser un array'], 422);
        }

        // B. Registre mitjançant el servei
        $resultat = $this->compraService->registrarCompres($usuariId, $sessioId, $seientIds);

        // C. Resposta segons el resultat
        if ($resultat['ok']) {
            return response()->json($resultat, 201);
        }

        $codi = 400;
        $missatge = $resultat['missatge'];
        if ($missatge !== '') {
            if (str_contains($missatge, 'venuts')) {
                $codi = 409;
            }
        }

        return response()->json($resultat, $codi);
    }
}
