<?php

namespace Database\Factories;

use App\Models\DesparChoParcialesModel;
use App\Models\PedidoProduccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesparChoParcialesModelFactory extends Factory
{
    protected $model = DesparChoParcialesModel::class;

    public function definition(): array
    {
        return [
            'pedido_id' => PedidoProduccion::factory(),
            'tipo_item' => 'prenda',
            'item_id' => 1,
            'talla_id' => null,
            'talla_color_id' => null,
            'genero' => 'UNISEX',
            'observaciones' => null,
            'fecha_despacho' => now(),
            'usuario_id' => User::factory(),
            'entregado' => false,
            'fecha_entrega' => null,
        ];
    }
}

