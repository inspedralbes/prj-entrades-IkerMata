<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Esdeveniment;
use App\Models\Sala;
use App\Models\Sessions; // Wait, model is Sessio
use App\Models\Sessio;
use App\Models\CategoriaSeient;
use App\Models\Seient;
use App\Models\PreuSessio;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuaris
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@exemple.com',
            'password' => Hash::make('password'),
            'rol' => 'admin',
        ]);

        User::create([
            'name' => 'Client User',
            'email' => 'client@exemple.com',
            'password' => Hash::make('password'),
            'rol' => 'client',
        ]);

        // 2. Categories de Seients
        $catVip = CategoriaSeient::create(['nom' => 'VIP', 'color_hex' => '#FFD700']);
        $catGeneral = CategoriaSeient::create(['nom' => 'General', 'color_hex' => '#008000']);

        // 3. Sala i Seients
        $sala = Sala::create(['nom' => 'Sala Principal', 'capacitat' => 50]);

        // Crear 5 files de 10 seients
        foreach (range('A', 'E') as $fila) {
            for ($num = 1; $num <= 10; $num++) {
                Seient::create([
                    'sala_id' => $sala->id,
                    'fila' => $fila,
                    'numero' => $num,
                    'categoria_id' => ($fila === 'A') ? $catVip->id : $catGeneral->id,
                ]);
            }
        }

        // 4. Esdeveniment i Sessió
        $esdeveniment = Esdeveniment::create([
            'titol' => 'Concert de Prova',
            'descripcio' => 'Un concert espectacular per provar el sistema.',
            'imatge_url' => 'https://via.placeholder.com/300',
            'durada_minuts' => 120,
            'estat' => 'actiu',
        ]);

        $sessio = Sessio::create([
            'esdeveniment_id' => $esdeveniment->id,
            'sala_id' => $sala->id,
            'data_hora' => now()->addDays(7),
        ]);

        // 5. Preus per a la sessió
        PreuSessio::create([
            'sessio_id' => $sessio->id,
            'categoria_id' => $catVip->id,
            'preu' => 50.00,
        ]);

        PreuSessio::create([
            'sessio_id' => $sessio->id,
            'categoria_id' => $catGeneral->id,
            'preu' => 25.00,
        ]);
    }
}
