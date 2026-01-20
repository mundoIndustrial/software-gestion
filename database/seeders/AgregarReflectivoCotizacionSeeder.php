<?php

namespace Database\Seeders;

use App\Models\TipoCotizacion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgregarReflectivoCotizacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Agregar tipo REFLECTIVO
        TipoCotizacion::updateOrCreate(
            ['codigo' => 'RF'],
            [
                'codigo' => 'RF',
                'nombre' => 'Reflectivo',
                'descripcion' => 'Cotización de reflectivo',
            ]
        );

        $this->command->info(' Tipo REFLECTIVO (RF) agregado correctamente');
    }
}
