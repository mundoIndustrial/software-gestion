<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cotizacion>
 */
class CotizacionFactory extends Factory
{
    protected $model = Cotizacion::class;

    public function definition(): array
    {
        return [
            'asesor_id' => User::factory(),
            'cliente_id' => Cliente::factory(),
            'cliente_nit' => $this->faker->numerify('##########'),
            'cliente_direccion' => $this->faker->address(),
            'cliente_telefono' => $this->faker->numerify('3#########'),
            'numero_cotizacion' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'tipo_cotizacion_id' => null,
            'tipo_venta' => 'M',
            'fecha_inicio' => now(),
            'fecha_envio' => null,
            'fecha_enviado_a_aprobador' => null,
            'es_borrador' => true,
            'estado' => 'BORRADOR',
            'novedades' => null,
            'especificaciones' => [],
            'observaciones_generales' => null,
            'iva' => 19.00,
            'aprobada_por_contador_en' => null,
            'aprobada_por_aprobador_en' => null,
        ];
    }
}
