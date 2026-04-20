<?php

namespace App\Services;

//================================ NAMESPACES / IMPORTS ============

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * Registre i validació d'usuaris per a l'API amb Sanctum.
 */
class AutenticacioService
{
    //================================ MÈTODES / FUNCIONS ==============

    /**
     * Valida les dades de registre i crea un usuari nou.
     *
     * A. Validació amb el validador de Laravel.
     * B. Creació del registre amb contrasenya hash.
     */
    public function registrar(array $dades): array
    {
        $resultat = [];
        $resultat['ok'] = false;
        $resultat['usuari'] = null;
        $resultat['errors'] = [];
        $resultat['missatge'] = '';

        // A. Validació
        $regles = [
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ];

        $validador = Validator::make($dades, $regles);
        if ($validador->fails()) {
            $resultat['errors'] = $validador->errors()->toArray();

            return $resultat;
        }

        // B. Persistència
        $usuari = new User;
        $usuari->nom = $dades['nom'];
        $usuari->email = $dades['email'];
        $usuari->password = $dades['password'];
        $usuari->rol = 'client';
        $usuari->save();

        $resultat['ok'] = true;
        $resultat['usuari'] = $usuari;

        return $resultat;
    }

    /**
     * Comprova email i contrasenya i retorna l'usuari si són correctes.
     *
     * A. Cerca per email.
     * B. Verificació de la contrasenya amb Hash.
     */
    public function autenticarAmbEmailIPassword(string $email, string $password): ?User
    {
        // A. Recuperació de l'usuari
        $usuari = User::where('email', $email)->first();
        if ($usuari === null) {
            return null;
        }

        // B. Verificació de la contrasenya
        if (! Hash::check($password, $usuari->password)) {
            return null;
        }

        return $usuari;
    }

    /**
     * Invalida tots els tokens de l'usuari (tanca sessió).
     */
    public function tancarSessio(User $usuari): void
    {
        $usuari->tokens()->delete();
    }
}
