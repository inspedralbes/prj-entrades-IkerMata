<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Models\Esdeveniment;

Route::get('/esdeveniments', function () {
    return Esdeveniment::all()->map(function ($e) {
        return [
            'titol' => $e->titol,
            'imatge_url' => $e->imatge_url,
            'seats_available' => 45 // Mocked for now to match the "Seats available: 45" text in prompt
        ];
    });
});
