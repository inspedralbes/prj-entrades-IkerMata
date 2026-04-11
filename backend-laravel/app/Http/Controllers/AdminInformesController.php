<?php

namespace App\Http\Controllers;

use App\Models\CompraEntrada;
use App\Models\Peli;
use App\Models\ReservaTemporal;
use App\Models\Sessio;
use App\Services\AforoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInformesController extends Controller
{
    /**
     * Panell agregat: reserves actives, mètriques per sessió (temps real des del servidor).
     */
    public function panellTempsReal(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $ara = now();

        $reservesActives = ReservaTemporal::query()->where('expires_at', '>', $ara)->count();

        // distinct()->count() amb PostgreSQL pot donar resultats incorrectes; pluck + unique és fiable.
        $usuarisAmbReserva = ReservaTemporal::query()
            ->where('expires_at', '>', $ara)
            ->pluck('usuari_id')
            ->unique()
            ->count();

        $sessions = Sessio::with(['sala', 'peli'])
            ->orderBy('data_hora')
            ->get();

        $perSessio = $sessions->map(function (Sessio $s) use ($ara) {
            $sid = (int) $s->id;
            $capacitat = (int) ($s->sala?->capacitat ?? 0);
            $venuts = CompraEntrada::where('sessio_id', $sid)->count();
            $reservatsTemp = ReservaTemporal::where('sessio_id', $sid)
                ->where('expires_at', '>', $ara)
                ->count();
            $disponiblesAforo = AforoService::placesDisponiblesSessio($sid);
            $importSessio = (string) CompraEntrada::where('sessio_id', $sid)->sum('preu_pagat');

            $ocupacioPct = $capacitat > 0 ? round(100 * $venuts / $capacitat, 1) : 0.0;

            return [
                'sessio_id' => $sid,
                'peli_titol' => $s->peli?->titol ?? '—',
                'data_hora' => $s->data_hora,
                'sala_nom' => $s->sala?->nom ?? '—',
                'capacitat_sala' => $capacitat,
                'places_venudes' => $venuts,
                'places_disponibles_aforo' => $disponiblesAforo,
                'seients_reservats_temporalment' => $reservatsTemp,
                'percentatge_ocupacio_vendes' => $ocupacioPct,
                'import_total_sessio_eur' => $importSessio,
            ];
        });

        return response()->json([
            'generat_a' => now()->toIso8601String(),
            'reserves_actives_total' => $reservesActives,
            'usuaris_amb_reserva_activa' => $usuarisAmbReserva,
            'per_sessio' => $perSessio,
        ]);
    }

    /**
     * Informes: recaptació per categoria, total, evolució diària (últims 30 dies).
     */
    public function informesResum(Request $request): JsonResponse
    {
        if ($request->user()->rol !== 'admin') {
            return response()->json(['error' => 'Accés denegat'], 403);
        }

        $sum = CompraEntrada::sum('preu_pagat');
        $total = $sum !== null ? number_format((float) $sum, 2, '.', '') : '0.00';

        $perCategoria = CompraEntrada::query()
            ->join('seients', 'compres_entrades.seient_id', '=', 'seients.id')
            ->join('categories_seients', 'seients.categoria_id', '=', 'categories_seients.id')
            ->selectRaw('categories_seients.nom as categoria, COUNT(*) as unitats, SUM(compres_entrades.preu_pagat) as total_eur')
            ->groupBy('categories_seients.id', 'categories_seients.nom')
            ->orderBy('categories_seients.nom')
            ->get()
            ->map(fn ($r) => [
                'categoria' => $r->categoria,
                'unitats' => (int) $r->unitats,
                'total_eur' => (string) $r->total_eur,
            ]);

        $desDe = now()->subDays(30)->startOfDay();

        $compresRecents = CompraEntrada::query()
            ->where('data_compra', '>=', $desDe)
            ->get(['data_compra', 'preu_pagat']);

        $perDia = [];
        foreach ($compresRecents as $c) {
            $dia = $c->data_compra->format('Y-m-d');
            if (! isset($perDia[$dia])) {
                $perDia[$dia] = ['compres' => 0, 'import' => 0.0];
            }
            $perDia[$dia]['compres']++;
            $perDia[$dia]['import'] += (float) $c->preu_pagat;
        }
        ksort($perDia);
        $evolucio = collect($perDia)->map(fn ($v, $dia) => [
            'data' => $dia,
            'compres' => $v['compres'],
            'import_eur' => number_format($v['import'], 2, '.', ''),
        ])->values();

        $pelis = Peli::orderBy('titol')->get(['id', 'titol']);

        return response()->json([
            'generat_a' => now()->toIso8601String(),
            'recaptacio_total_eur' => $total,
            'per_categoria' => $perCategoria,
            'evolucio_diaria_30_dies' => $evolucio,
            'pelicules' => $pelis,
        ]);
    }
}
