<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetComparisonDataRequest;
use App\Application\SupervisorPedidos\DTOs\GetComparisonDataResponse;
use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\Log;

class GetComparisonDataUseCase
{
    public function execute(GetComparisonDataRequest $request): GetComparisonDataResponse
    {
        try {
            $orderId = $request->getOrderId();

            // Obtener orden con cotización
            $orden = PedidoProduccion::with([
                'prendas',
                'asesora',
                'cotizacion' => function($query) {
                    $query->with([
                        'prendas' => function($q) {
                            $q->with('tallas');
                        },
                        'asesor'
                    ]);
                }
            ])->findOrFail($orderId);

            // Construir datos de comparación
            $datosComparacion = $this->buildComparisonData($orden);

            Log::info('Datos de comparación obtenidos', [
                'order_id' => $orderId,
                'has_quotation' => $datosComparacion['cotizacion'] !== null
            ]);

            return new GetComparisonDataResponse($datosComparacion);

        } catch (\Exception $e) {
            Log::error('Error en GetComparisonData: ' . $e->getMessage(), [
                'order_id' => $request->getOrderId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function buildComparisonData($orden): array
    {
        return [
            'pedido' => [
                'numero' => $orden->numero_pedido,
                'cliente' => $orden->cliente,
                'asesora' => $orden->asesora?->name ?? 'N/A',
                'estado' => $orden->estado,
                'fecha' => $orden->fecha_de_creacion_de_orden,
                'prendas' => $this->formatPrendas($orden->prendas),
            ],
            'cotizacion' => $this->formatQuotation($orden)
        ];
    }

    private function formatPrendas($prendas): array
    {
        return $prendas->map(function($prenda, $index) {
            return [
                'nombre' => $prenda->nombre_prenda,
                'descripcion' => $prenda->generarDescripcionDetallada($index + 1),
                'tallas' => $prenda->cantidad_talla ?? []
            ];
        })->toArray();
    }

    private function formatQuotation($orden): ?array
    {
        if (!$orden->cotizacion) {
            return null;
        }

        $cotizacion = $orden->cotizacion;

        return [
            'numero' => 'COT-' . str_pad($cotizacion->id, 5, '0', STR_PAD_LEFT),
            'cliente' => $cotizacion->cliente?->nombre ?? $orden->cliente ?? 'N/A',
            'asesora' => $cotizacion->asesor?->name ?? 'N/A',
            'estado' => $cotizacion->estado,
            'fecha' => $cotizacion->created_at,
            'prendas' => $this->formatQuotationPrendas($cotizacion->prendas)
        ];
    }

    private function formatQuotationPrendas($prendas): array
    {
        return $prendas->map(function($prenda, $index) {
            $tallas = $prenda->tallas ? $prenda->tallas->pluck('talla')->toArray() : [];
            return [
                'nombre' => $prenda->nombre_producto,
                'descripcion' => $prenda->generarDescripcionDetallada($index + 1),
                'tallas' => $tallas
            ];
        })->toArray();
    }
}
