<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposProcesosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposProc = [
            [
                'nombre' => 'Reflectivo',
                'slug' => 'reflectivo',
                'descripcion' => 'Material reflectivo de seguridad que brilla en la oscuridad. Ideal para prendas de trabajo y seguridad.',
                'color' => '#FFB000',
                'icono' => 'shield-alert',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Bordado',
                'slug' => 'bordado',
                'descripcion' => 'Bordado personalizado en máquina. Crea diseños duraderos y profesionales en tela.',
                'color' => '#8B4513',
                'icono' => 'needle-thread',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Estampado',
                'slug' => 'estampado',
                'descripcion' => 'Estampado de imágenes o logos en prendas. Método de impresión por calor.',
                'color' => '#FF6B6B',
                'icono' => 'image',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'DTF',
                'slug' => 'dtf',
                'descripcion' => 'Direct-to-Fabric: Impresión directa en tela con tinta pigmentada. Colores vibrantes y detallados.',
                'color' => '#4ECDC4',
                'icono' => 'printer',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'Sublimado',
                'slug' => 'sublimado',
                'descripcion' => 'Sublimación: Transferencia de tinta sublimada a tela con calor. Resultado permanente y lavable.',
                'color' => '#A8E6CF',
                'icono' => 'cloud-upload',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('tipos_procesos')->insert($tiposProc);
    }
}
