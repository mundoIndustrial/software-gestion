<?php

namespace Database\Seeders;

use App\Models\InventarioTela;
use Illuminate\Database\Seeder;

class InventarioTelasSeeder extends Seeder
{
    public function run(): void
    {
        $telas = [
            [
                'categoria' => 'Algodón',
                'nombre_tela' => 'Algodón Premium 100%',
                'stock' => 500,
                'metraje_sugerido' => 100,
            ],
            [
                'categoria' => 'Algodón',
                'nombre_tela' => 'Algodón Orgánico',
                'stock' => 450,
                'metraje_sugerido' => 80,
            ],
            [
                'categoria' => 'Poliéster',
                'nombre_tela' => 'Poliéster Stretch',
                'stock' => 600,
                'metraje_sugerido' => 120,
            ],
            [
                'categoria' => 'Poliéster',
                'nombre_tela' => 'Poliéster Brillante',
                'stock' => 550,
                'metraje_sugerido' => 100,
            ],
            [
                'categoria' => 'Mezcla',
                'nombre_tela' => 'Algodón-Poliéster 65/35',
                'stock' => 480,
                'metraje_sugerido' => 90,
            ],
            [
                'categoria' => 'Mezcla',
                'nombre_tela' => 'Algodón-Elastano 95/5',
                'stock' => 520,
                'metraje_sugerido' => 110,
            ],
            [
                'categoria' => 'Lino',
                'nombre_tela' => 'Lino Natural',
                'stock' => 350,
                'metraje_sugerido' => 70,
            ],
            [
                'categoria' => 'Denim',
                'nombre_tela' => 'Denim Premium 12oz',
                'stock' => 400,
                'metraje_sugerido' => 80,
            ],
            [
                'categoria' => 'Jersey',
                'nombre_tela' => 'Jersey de Algodón',
                'stock' => 550,
                'metraje_sugerido' => 100,
            ],
            [
                'categoria' => 'Drill',
                'nombre_tela' => 'Drill Borneo',
                'stock' => 480,
                'metraje_sugerido' => 95,
            ],
        ];

        foreach ($telas as $tela) {
            InventarioTela::firstOrCreate(
                [
                    'nombre_tela' => $tela['nombre_tela'],
                    'categoria' => $tela['categoria'],
                ],
                $tela
            );
        }
    }
}
