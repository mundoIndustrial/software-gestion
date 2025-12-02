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
                'codigo' => 'PB',
                'nombre' => 'Prenda/Bordado',
                'descripcion' => 'Cotización de prendas con bordado',
                'activo' => true,
            ],
            [
                'codigo' => 'B',
                'nombre' => 'Bordado',
                'descripcion' => 'Cotización de bordado únicamente',
                'activo' => true,
            ],
            [
                'codigo' => 'P',
                'nombre' => 'Prenda',
                'descripcion' => 'Cotización de prendas únicamente',
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
