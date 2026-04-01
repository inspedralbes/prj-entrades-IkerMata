<?php

namespace App\Services;

//================================ NAMESPACES / IMPORTS ============

use App\Models\CompraEntrada;

//================================ PROPIETATS / ATRIBUTS ============

/**
 * UUID de l'usuari de prova definit a base_de_dades/sql/insert.sql (sense autenticació).
 */
class EntradaService
{
    public const USUARI_DEFECTE_ID = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

    //================================ MÈTODES / FUNCIONS ==============

    /**
     * Retorna totes les entrades comprades per un usuari, amb pel·lícula, sala i seient.
     *
     * A. Es consulten les compres amb les relacions necessàries.
     * B. Es transforma cada registre en un array pla per a l'API JSON.
     */
    public function llistarPerUsuari(string $usuariId): array
    {
        // A. Recuperació de dades de la base de dades
        $comandes = CompraEntrada::where('usuari_id', $usuariId)
            ->with(['sessio.peli', 'sessio.sala', 'seient'])
            ->orderBy('data_compra', 'desc')
            ->get();

        // B. Processament final: construcció de la llista de resposta
        $sortida = [];
        foreach ($comandes as $compra) {
            $sessio = $compra->sessio;
            if ($sessio === null) {
                continue;
            }

            $peli = $sessio->peli;
            $sala = $sessio->sala;
            $seient = $compra->seient;

            $etiquetaSeient = '';
            if ($seient !== null) {
                $etiquetaSeient = $seient->fila.$seient->numero;
            }

            $titolPeli = '';
            if ($peli !== null) {
                $titolPeli = $peli->titol;
            }

            $nomSala = '';
            if ($sala !== null) {
                $nomSala = $sala->nom;
            }

            $fila = [
                'id' => $compra->id,
                'peli_titol' => $titolPeli,
                'data_hora' => $sessio->data_hora,
                'sala_nom' => $nomSala,
                'seient' => $etiquetaSeient,
                'preu_pagat' => $compra->preu_pagat,
                'data_compra' => $compra->data_compra,
            ];

            $sortida[] = $fila;
        }

        return $sortida;
    }
}
