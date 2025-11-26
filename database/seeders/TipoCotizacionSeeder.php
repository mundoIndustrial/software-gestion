<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TipoCotizacion;

class TipoCotizacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            [
                'codigo' => 'M',
                'nombre' => 'Muestras',
                'descripcion' => 'Cotización de muestras de prendas',
                'activo' => true,
            ],
            [
                'codigo' => 'D',
                'nombre' => 'Distribuidor',
                'descripcion' => 'Cotización para distribuidores',
                'activo' => true,
            ],
            [
                'codigo' => 'X',
                'nombre' => 'Exportación',
                'descripcion' => 'Cotización para exportación',
                'activo' => true,
            ],
        ];

        foreach ($tipos as $tipo) {
            TipoCotizacion::updateOrCreate(
                ['codigo' => $tipo['codigo']],
                $tipo
            );
        }
    }
}
