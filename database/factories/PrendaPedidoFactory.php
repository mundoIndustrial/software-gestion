<?php

namespace Database\Factories;

use App\Models\PedidoProduccion;
use App\Models\PrendaPedido;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrendaPedidoFactory extends Factory
{
    protected $model = PrendaPedido::class;

    public function definition(): array
    {
        return [
            'pedido_produccion_id' => PedidoProduccion::factory(),
            'nombre_prenda' => $this->faker->words(2, true),
            'descripcion' => $this->faker->sentence(),
            'de_bodega' => false,
        ];
    }
}
