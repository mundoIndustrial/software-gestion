<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoRepository;

class ObtenerFacturaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoRepository $pedidoRepository
    ) {}

    public function ejecutar(ObtenerFacturaDTO $dto): array
    {
        // Obtener pedido con todas las relaciones necesarias incluyendo tallas
        // NOTA: PedidoRepository->porId() retorna un PedidoAggregate, no un Eloquent Model
        // Para acceder a prendas con tallas, necesitamos obtener el modelo directamente
        $pedidoModel = \App\Models\PedidoProduccion::with('prendas.tallas')
            ->findOrFail((int)$dto->pedidoId);

        if (!$pedidoModel) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Transformar prendas para incluir tallas formateadas
        $prendasTransformadas = $pedidoModel->prendas->map(function($prenda) {
            // Obtener tallas agrupadas por gÃ©nero en formato { GENERO: { TALLA: CANTIDAD } }
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
            'numero_pedido' => $pedidoModel->numero_pedido,
            'cliente' => $pedidoModel->cliente,
            'fecha' => $pedidoModel->created_at,
            'forma_de_pago' => $pedidoModel->forma_de_pago,
            'estado' => $pedidoModel->estado,
            'total' => $this->calcularTotal($pedidoModel),
            'prendas' => $prendasTransformadas,
        ];
    }

    private function calcularTotal($pedidoModel): float
    {
        $total = 0;
        foreach ($pedidoModel->prendas as $prenda) {
            $total += $prenda->precio_unitario * $prenda->cantidad;
        }
        return $total;
    }
}


