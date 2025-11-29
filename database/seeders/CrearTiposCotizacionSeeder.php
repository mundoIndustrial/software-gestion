<?php

namespace Database\Seeders;

use App\Models\TipoCotizacion;
use Illuminate\Database\Seeder;

class CrearTiposCotizacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Prenda/Logo', 'codigo' => 'prenda_logo', 'descripcion' => 'Cotización con prendas y bordado/estampado'],
            ['nombre' => 'Logo', 'codigo' => 'logo', 'descripcion' => 'Cotización solo con bordado/estampado'],
            ['nombre' => 'General', 'codigo' => 'general', 'descripcion' => 'Cotización general'],
        ];

        foreach ($tipos as $tipo) {
            TipoCotizacion::firstOrCreate(
                ['codigo' => $tipo['codigo']],
                [
                    'nombre' => $tipo['nombre'],
                    'descripcion' => $tipo['descripcion']
                ]
            );
            $this->command->info("✅ Tipo de cotización creado: {$tipo['nombre']}");
        }

        $this->command->info('✅ Todos los tipos de cotización han sido creados');
    }
}
