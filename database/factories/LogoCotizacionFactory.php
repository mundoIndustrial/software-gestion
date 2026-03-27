<?php

namespace Database\Factories;

use App\Models\Cotizacion;
use App\Models\LogoCotizacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LogoCotizacion>
 */
class LogoCotizacionFactory extends Factory
{
    protected $model = LogoCotizacion::class;

    public function definition(): array
    {
        return [
            'cotizacion_id' => Cotizacion::factory(),
            'observaciones_generales' => [],
            'tipo_venta' => 'CONTADO',
        ];
    }
}

