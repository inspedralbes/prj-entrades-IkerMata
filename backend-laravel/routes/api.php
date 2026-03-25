<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Peli;

Route::get('/peliculas', function () {
    return Peli::all()->map(function ($p) {
        return [
            'titol' => $p->titol,
            'imatge_url' => $p->imatge_url,
            'seats_available' => 45 // En un futur es calcularà per sessió
        ];
    });
});
