<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Peli;
use App\Models\Sessio;
use App\Models\Seient;
use App\Models\Sala;

Route::get('/peliculas', function () {
    return Peli::all()->map(function ($p) {
        return [
            'id' => $p->id,
            'titol' => $p->titol,
            'imatge_url' => $p->imatge_url,
            'seats_available' => 45
        ];
    });
});

Route::get('/peliculas/{id}/sesiones', function ($id) {
    return Sessio::where('esdeveniment_id', $id)->with('sala')->get()->map(function ($s) {
        return [
            'id' => $s->id,
            'uuid' => $s->uuid,
            'sala_nom' => $s->sala->nom,
            'data_hora' => $s->data_hora
        ];
    });
});

Route::get('/sesiones/{id}/asientos', function ($id) {
    $sessio = Sessio::find($id);
    if (!$sessio) {
        return [];
    }
    
    $seients = Seient::where('sala_id', $sessio->sala_id)->with('categoria')->get();

    return $seients->map(function ($s) {
        return [
            'id' => $s->id,
            'fila' => $s->fila,
            'numero' => $s->numero,
            'categoria' => $s->categoria->nom,
            'color' => $s->categoria->color_hex,
            'reservat' => false
        ];
    });
});
