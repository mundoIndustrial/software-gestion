<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PedidoProduccion>
 */
class PedidoProduccionFactory extends Factory
{
    protected $model = PedidoProduccion::class;

    public function definition(): array
    {
        return [
            'cotizacion_id' => null,
            'numero_cotizacion' => null,
            'numero_pedido' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'orden_compra' => null,
            'cliente' => $this->faker->company(),
            'cliente_id' => Cliente::factory(),
            'novedades' => null,
            'observaciones' => null,
            'asesor_id' => User::factory(),
            'forma_de_pago' => 'CONTADO',
            'estado' => 'Pendiente',
            'motivo_revision' => null,
            'fecha_revision' => null,
            'usuario_revision' => null,
            'area' => 'creacion de pedido',
            'fecha_ultimo_proceso' => null,
            'fecha_de_creacion_de_orden' => now(),
            'dia_de_entrega' => 8,
            'fecha_estimada_de_entrega' => now()->addDays(8),
            'aprobado_por_supervisor_en' => null,
            'cantidad_total' => $this->faker->numberBetween(1, 200),
            'aprobado_por_usuario_cartera' => null,
            'aprobado_por_cartera_en' => null,
            'rechazado_por_usuario_cartera' => null,
            'rechazado_por_cartera_en' => null,
            'motivo_rechazo_cartera' => null,
            'viewed_at' => null,
            'ocultado_en' => null,
            'usuario_ocultado_por' => null,
            'anulado_por_asesora_id' => null,
            'anulado_por_asesora_en' => null,
        ];
    }
}
