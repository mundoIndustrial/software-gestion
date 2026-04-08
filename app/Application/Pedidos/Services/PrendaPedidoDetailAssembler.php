<?php

namespace App\Application\Pedidos\Services;

use App\Models\PrendaPedido;

class PrendaPedidoDetailAssembler
{
    public function __construct(
        private PrendaPedidoQuantityCalculator $quantityCalculator
    ) {
    }

    public function toArray(PrendaPedido $prenda): array
    {
        return [
            'id' => $prenda->id,
            'nombre' => $prenda->nombre_prenda,
            'descripcion' => $prenda->descripcion,
            'genero' => $prenda->genero,
            'de_bodega' => $prenda->de_bodega,
            'cantidad_total' => $this->quantityCalculator->calculate($prenda),
            'variantes' => $prenda->variantes->map(function ($variante) {
                return [
                    'id' => $variante->id,
                    'talla' => $variante->talla,
                    'cantidad' => $variante->cantidad,
                    'color' => $variante->color?->nombre,
                    'tela' => $variante->tela?->nombre,
                    'manga' => $variante->tipoManga?->nombre,
                    'broche_boton' => $variante->tipoBrocheBoton?->nombre,
                    'tiene_bolsillos' => $variante->tiene_bolsillos,
                    'observaciones' => [
                        'manga' => $variante->manga_obs,
                        'broche_boton' => $variante->broche_boton_obs,
                        'bolsillos' => $variante->bolsillos_obs,
                    ],
                ];
            })->toArray(),
        ];
    }
}
