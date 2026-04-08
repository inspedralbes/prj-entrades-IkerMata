<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Esdeveniment;
use App\Models\Sala;
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
        User::create([
            'nom' => 'Admin User',
            'email' => 'admin@exemple.com',
            'password' => Hash::make('password'),
            'rol' => 'admin',
        ]);

        User::create([
            'nom' => 'Client User',
            'email' => 'client@exemple.com',
            'password' => Hash::make('password'),
            'rol' => 'client',
        ]);

        $catVip = CategoriaSeient::create(['nom' => 'VIP', 'color_hex' => '#FFD700']);
        $catNormal = CategoriaSeient::create(['nom' => 'Normal', 'color_hex' => '#4169E1']);

        $salaPrincipal = Sala::create(['nom' => 'Sala 1', 'capacitat' => 50]);
        $salaPetit = Sala::create(['nom' => 'Sala 2', 'capacitat' => 30]);
        $salaGran = Sala::create(['nom' => 'Sala 3', 'capacitat' => 100]);

        $sales = [$salaPrincipal, $salaPetit, $salaGran];
        foreach ($sales as $sala) {
            $files = $sala->capacitat <= 30 ? 3 : ($sala->capacitat <= 50 ? 5 : 10);
            $seientsPerFila = (int) ($sala->capacitat / $files);
            for ($f = 0; $f < $files; $f++) {
                $fila = chr(65 + $f);
                $esVip = $files >= 5 && $f === 3;

                for ($num = 1; $num <= $seientsPerFila; $num++) {
                    Seient::create([
                        'sala_id' => $sala->id,
                        'fila' => $fila,
                        'numero' => $num,
                        'categoria_id' => $esVip ? $catVip->id : $catNormal->id,
                    ]);
                }
            }
        }

        $esdeveniment = Esdeveniment::create([
            'titol' => 'Dune: Part Two',
            'descripcio' => 'Paul Atreides s\'uneix als Fremen mentre busca venganza contra els que van destruir a la seva familia.',
            'imatge_url' => 'https://via.placeholder.com/300',
            'durada_minuts' => 166,
            'estat' => 'actiu',
        ]);

        $sessio1 = Sessio::create([
            'esdeveniment_id' => $esdeveniment->id,
            'sala_id' => $salaPrincipal->id,
            'data_hora' => now()->addDays(7),
        ]);

        $sessio2 = Sessio::create([
            'esdeveniment_id' => $esdeveniment->id,
            'sala_id' => $salaPetit->id,
            'data_hora' => now()->addDays(14),
        ]);

        $sessio3 = Sessio::create([
            'esdeveniment_id' => $esdeveniment->id,
            'sala_id' => $salaGran->id,
            'data_hora' => now()->addDays(21),
        ]);

        foreach ([$sessio1, $sessio2, $sessio3] as $sessio) {
            PreuSessio::create([
                'sessio_id' => $sessio->id,
                'categoria_id' => $catVip->id,
                'preu' => 9.70,
            ]);
            PreuSessio::create([
                'sessio_id' => $sessio->id,
                'categoria_id' => $catNormal->id,
                'preu' => 6.70,
            ]);
        }
    }
}
