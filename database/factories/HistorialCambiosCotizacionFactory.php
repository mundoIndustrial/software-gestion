<?php

namespace Database\Factories;

use App\Models\HistorialCambiosCotizacion;
use App\Models\Cotizacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistorialCambiosCotizacion>
 */
class HistorialCambiosCotizacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cotizacion_id' => Cotizacion::factory(),
            'estado_anterior' => 'BORRADOR',
            'estado_nuevo' => 'ENVIADA_CONTADOR',
            'usuario_id' => User::factory(),
            'usuario_nombre' => $this->faker->name(),
            'rol_usuario' => 'asesor',
            'razon_cambio' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0',
            'datos_adicionales' => [
                'numero_cotizacion' => $this->faker->numberBetween(1000, 9999),
                'cliente' => $this->faker->company(),
            ],
            'created_at' => now(),
        ];
    }
}
