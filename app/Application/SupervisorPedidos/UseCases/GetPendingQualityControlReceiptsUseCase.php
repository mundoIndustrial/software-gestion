<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsResponse;
use App\Application\SupervisorPedidos\Support\CalculaDiasRestantesEntrega;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\Log;

class GetPendingQualityControlReceiptsUseCase
{
    use CalculaDiasRestantesEntrega;

    public function __construct(
        private readonly ReceiptRepository $receiptRepository
    ) {}

    public function execute(GetPendingSewingReceiptsRequest $request): GetPendingSewingReceiptsResponse
    {
        try {
            $recibosControlCalidad = collect(
                $this->receiptRepository->findPendingQualityControlReceipts(
                    $this->buildFilters($request)
                )
            );

            Log::info('Recibos Control-Calidad recuperados', ['count' => $recibosControlCalidad->count()]);

            // Procesar recibos con prendas
            $procesosConCantidad = $recibosControlCalidad->map(function ($recibo) {
                return $this->formatReceipt($recibo);
            });

            return new GetPendingSewingReceiptsResponse($procesosConCantidad->toArray());

        } catch (\Exception $e) {
            Log::error('Error en GetPendingQualityControlReceipts: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFilters(GetPendingSewingReceiptsRequest $request): array
    {
        return [
            'numero_recibo' => $this->parseCsvFilter($request->getNumeroRecibo()),
            'cliente' => $this->parseCsvFilter($request->getCliente()),
            'asesor' => $this->parseCsvFilter($request->getAsesor()),
            'prendas' => $this->parseCsvFilter($request->getPrendas()),
            'fecha_creacion' => ($fecha = trim((string) $request->getFechaCreacion())) !== '' ? $fecha : null,
            'area' => $this->parseCsvFilter($request->getArea()),
            'busqueda' => $request->getBusqueda(),
        ];
    }

    private function formatReceipt($recibo): array
    {
        $proceso = [
            'fecha_creacion' => $recibo->fecha_creacion,
            'numero_recibo' => $recibo->numero_recibo,
            'tipo_recibo' => $recibo->tipo_recibo ?? null,
            'prenda_id' => $recibo->prenda_id,
            'cliente' => $recibo->cliente,
            'area' => $recibo->area,
            'pedido_id' => $recibo->pedido_id,
            'asesor' => $recibo->asesor,
            'color_costura' => $recibo->color_costura,
            'color_control_calidad' => $recibo->color_control_calidad ?? null,
            'color_entrega' => $recibo->color_entrega ?? null,
            'aprobado_por_cartera_en' => $recibo->aprobado_por_cartera_en ?? null,
            'dia_de_entrega' => isset($recibo->dia_de_entrega) ? (int) $recibo->dia_de_entrega : null,
            'fecha_estimada_de_entrega' => $recibo->fecha_estimada_de_entrega ?? null,
            'dias_restantes' => $this->calcularDiasRestantesEntrega(
                $recibo->aprobado_por_cartera_en ?? null,
                isset($recibo->dia_de_entrega) ? (int) $recibo->dia_de_entrega : null,
                $recibo->fecha_estimada_de_entrega ?? null
            ),
            'prendas' => collect(),
        ];

        if (empty($recibo->prenda_id)) {
            return $proceso;
        }

        $prendasConColores = collect(
            $this->receiptRepository->findGarmentsWithColorsByPrendaId((int) $recibo->prenda_id)
        );
        $prendasSinColores = collect(
            $this->receiptRepository->findGarmentsWithoutColorsByPrendaId((int) $recibo->prenda_id)
        );

        $proceso['prendas'] = $prendasConColores->merge($prendasSinColores);
        return $proceso;
    }

    /**
     * @return array<int, string>
     */
    private function parseCsvFilter(?string $value): array
    {
        if ($value === null || trim($value) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
