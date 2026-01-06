<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoLogoCotizacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existen datos
        $count = DB::table('tipo_logo_cotizaciones')->count();
        
        if ($count > 0) {
            echo "ℹ️ La tabla ya tiene registros. No se insertarán duplicados.\n";
            return;
        }

        $tipos = [
            [
                'nombre' => 'BORDADO',
                'codigo' => 'BOR',
                'descripcion' => 'Técnica de bordado en prendas con hilos',
                'color' => '#e74c3c',
                'icono' => 'fa-needle',
                'orden' => 1,
                'activo' => true,
            ],
            [
                'nombre' => 'ESTAMPADO',
                'codigo' => 'EST',
                'descripcion' => 'Estampado directo en prendas con serigrafía o DTG',
                'color' => '#3498db',
                'icono' => 'fa-stamp',
                'orden' => 2,
                'activo' => true,
            ],
            [
                'nombre' => 'SUBLIMADO',
                'codigo' => 'SUB',
                'descripcion' => 'Sublimación a calor en prendas polyester',
                'color' => '#f39c12',
                'icono' => 'fa-fire',
                'orden' => 3,
                'activo' => true,
            ],
            [
                'nombre' => 'DTF',
                'codigo' => 'DTF',
                'descripcion' => 'Direct-to-Film, impresión en película y transferencia',
                'color' => '#9b59b6',
                'icono' => 'fa-film',
                'orden' => 4,
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipo_logo_cotizaciones')->insert(array_merge(
                $tipo,
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ));
        }

        echo "✅ Tipos de logo cotización creados correctamente\n";
    }
}
