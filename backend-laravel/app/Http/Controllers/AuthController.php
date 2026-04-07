<?php

namespace App\Http\Controllers;

//================================ NAMESPACES / IMPORTS ============

use App\Services\AutenticacioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * Autenticació amb tokens Sanctum (login, registre, tancament de sessió).
 */
class AuthController extends Controller
{
    protected AutenticacioService $autenticacioService;

    public function __construct(AutenticacioService $autenticacioService)
    {
        $this->autenticacioService = $autenticacioService;
    }

    //================================ MÈTODES / FUNCIONS ==============

    /**
     * POST /api/register — crea un compte i retorna token Sanctum.
     */
    public function registrar(Request $request): JsonResponse
    {
        $dades = [];
        $dades['nom'] = $request->input('nom');
        $dades['email'] = $request->input('email');
        $dades['password'] = $request->input('password');

        $resultat = $this->autenticacioService->registrar($dades);
        if (! $resultat['ok']) {
            return response()->json(['errors' => $resultat['errors']], 422);
        }

        $usuari = $resultat['usuari'];
        $token = $usuari->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuari' => $this->serialitzarUsuari($usuari),
        ], 201);
    }

    /**
     * POST /api/login — retorna token Sanctum si les credencials són vàlides.
     */
    public function login(Request $request): JsonResponse
    {
        $regles = [
            'email' => 'required|email',
            'password' => 'required|string',
        ];

        $validador = Validator::make($request->all(), $regles);
        if ($validador->fails()) {
            return response()->json(['errors' => $validador->errors()], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');
        if ($password === null) {
            $password = '';
        }

        $usuari = $this->autenticacioService->autenticarAmbEmailIPassword($email, $password);
        if ($usuari === null) {
            return response()->json(['missatge' => 'Credencials incorrectes'], 401);
        }

        $usuari->tokens()->delete();
        $token = $usuari->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'usuari' => $this->serialitzarUsuari($usuari),
        ]);
    }

    /**
     * POST /api/logout — invalida el token actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $usuari = $request->user();
        if ($usuari === null) {
            return response()->json(['missatge' => 'No autenticat'], 401);
        }

        $this->autenticacioService->tancarSessio($usuari);

        return response()->json(['missatge' => 'Sessió tancada']);
    }

    /**
     * GET /api/usuari — usuari autenticat.
     */
    public function usuari(Request $request): JsonResponse
    {
        $usuari = $request->user();
        if ($usuari === null) {
            return response()->json(['missatge' => 'No autenticat'], 401);
        }

        return response()->json($this->serialitzarUsuari($usuari));
    }

    /**
     * Construeix l'array públic de l'usuari sense camps sensibles.
     */
    protected function serialitzarUsuari($usuari): array
    {
        $fila = [];
        $fila['id'] = $usuari->id;
        $fila['nom'] = $usuari->nom;
        $fila['email'] = $usuari->email;
        $fila['rol'] = $usuari->rol;

        return $fila;
    }
}
