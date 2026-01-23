<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;

class ObtenerFacturaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerFacturaDTO $dto): array
    {
        // Obtener pedido con todas las relaciones necesarias incluyendo tallas
        // El repositorio ya carga 'prendas.tallas' en su método obtenerPorId()
        $pedido = $this->pedidoRepository->obtenerPorId((int)$dto->pedidoId);

        if (!$pedido) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Transformar prendas para incluir tallas formateadas
        $prendasTransformadas = $pedido->prendas->map(function($prenda) {
            // Obtener tallas agrupadas por género en formato { GENERO: { TALLA: CANTIDAD } }
            $tallasAgrupadas = [];
            if ($prenda->tallas && $prenda->tallas->count() > 0) {
                foreach ($prenda->tallas as $talla) {
                    if (!isset($tallasAgrupadas[$talla->genero])) {
                        $tallasAgrupadas[$talla->genero] = [];
                    }
                    $tallasAgrupadas[$talla->genero][$talla->talla] = $talla->cantidad;
                }
            }

            return [
                'id' => $prenda->id,
                'nombre_producto' => $prenda->nombre_prenda ?? 'Prenda sin nombre',
                'nombre' => $prenda->nombre_prenda ?? 'Prenda sin nombre',
                'cantidad' => $prenda->cantidad ?? 0,
                'precio_unitario' => $prenda->precio_unitario ?? 0,
                'descripcion' => $prenda->descripcion ?? '',
                'tallas' => $tallasAgrupadas,  // Estructura: { DAMA: { L: 5, M: 15, S: 10 } }
                'variantes' => $prenda->variantes ? $prenda->variantes->map(function($var) {
                    return [
                        'talla' => $var->talla ?? 'N/A',
                        'cantidad' => $var->cantidad ?? 0,
                        'manga' => $var->tipoManga?->nombre ?? null,
                        'manga_obs' => $var->manga_obs ?? null,
                        'broche' => $var->tipoBroche?->nombre ?? null,
                        'broche_obs' => $var->broche_obs ?? null,
                        'bolsillos' => $var->tiene_bolsillos ?? false,
                        'bolsillos_obs' => $var->bolsillos_obs ?? null
                    ];
                })->toArray() : []
            ];
        })->toArray();

        return [
            'numero_pedido' => $pedido->numero_pedido,
            'cliente' => $pedido->cliente,
            'fecha' => $pedido->created_at,
            'forma_de_pago' => $pedido->forma_de_pago,
            'estado' => $pedido->estado,
            'total' => $this->calcularTotal($pedido),
            'prendas' => $prendasTransformadas,
        ];
    }

    private function calcularTotal($pedido): float
    {
        $total = 0;
        foreach ($pedido->prendas as $prenda) {
            $total += $prenda->precio_unitario * $prenda->cantidad;
        }
        return $total;
    }
}
