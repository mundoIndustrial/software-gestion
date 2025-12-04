<?php

namespace Database\Factories;

use App\Models\HistorialCambiosPedido;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HistorialCambiosPedido>
 */
class HistorialCambiosPedidoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pedido_id' => PedidoProduccion::factory(),
            'estado_anterior' => 'PENDIENTE_SUPERVISOR',
            'estado_nuevo' => 'APROBADO_SUPERVISOR',
            'usuario_id' => User::factory(),
            'usuario_nombre' => $this->faker->name(),
            'rol_usuario' => 'supervisor_pedidos',
            'razon_cambio' => $this->faker->sentence(),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => 'Mozilla/5.0',
            'datos_adicionales' => [
                'numero_pedido' => $this->faker->numberBetween(500, 5000),
                'numero_cotizacion' => $this->faker->numberBetween(1000, 9999),
                'cliente' => $this->faker->company(),
            ],
            'created_at' => now(),
        ];
    }
}
