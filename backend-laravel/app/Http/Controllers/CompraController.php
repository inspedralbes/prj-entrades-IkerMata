<?php

namespace App\Http\Controllers;

//================================ NAMESPACES / IMPORTS ============

use App\Services\CompraService;
use App\Services\TempsRealService;
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
            $resultat['sessio_id'] = $sessioId;

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

    /**
     * POST /api/reservar — crea o elimina una reserva temporal d'un seient.
     */
    public function reservarTemporal(Request $request): JsonResponse
    {
        $usuari = $request->user();
        if ($usuari === null) {
            return response()->json(['error' => 'Cal iniciar sessió'], 401);
        }

        $sessioId = $request->input('sessioId');
        $seientId = $request->input('seientId');
        $estat = $request->input('estat'); // true = reservar, false = alliberar

        if (! $sessioId || ! $seientId) {
            return response()->json(['error' => 'Faltes dades'], 422);
        }

        if ($estat) {
            // Comprovar si ja està ocupat per algú altre
            $existent = \App\Models\ReservaTemporal::where('sessio_id', $sessioId)
                ->where('seient_id', $seientId)
                ->where('expires_at', '>', now())
                ->where('usuari_id', '!=', $usuari->id)
                ->exists();

            if ($existent) {
                return response()->json(['error' => 'Seient ja reservat per un altre usuari'], 409);
            }

            // Crear o renovar reserva
            \App\Models\ReservaTemporal::updateOrCreate(
                ['sessio_id' => $sessioId, 'seient_id' => $seientId, 'usuari_id' => $usuari->id],
                ['expires_at' => now()->addMinutes(10)]
            );

            TempsRealService::notificarSeleccionat($sessioId, $seientId, (string) $usuari->id);

            return response()->json([
                'ok' => true,
                'missatge' => 'Seient reservat temporalment',
                'sessio_id' => (int) $sessioId,
                'seient_id' => (int) $seientId,
                'usuari_id' => (string) $usuari->id,
                'reserva_activa' => true,
            ]);
        } else {
            // Alliberar reserva
            \App\Models\ReservaTemporal::where('sessio_id', $sessioId)
                ->where('seient_id', $seientId)
                ->where('usuari_id', $usuari->id)
                ->delete();

            TempsRealService::notificarAlliberat($sessioId, $seientId);

            return response()->json([
                'ok' => true,
                'missatge' => 'Reserva temporal lliure',
                'sessio_id' => (int) $sessioId,
                'seient_id' => (int) $seientId,
                'usuari_id' => (string) $usuari->id,
                'reserva_activa' => false,
            ]);
        }
    }
}
