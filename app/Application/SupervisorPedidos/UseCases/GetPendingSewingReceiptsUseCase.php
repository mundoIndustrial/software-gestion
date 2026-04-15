<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingSewingReceiptsResponse;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;
use Illuminate\Support\Facades\Log;

class GetPendingSewingReceiptsUseCase
{
    public function __construct(
        private readonly ReceiptRepository $receiptRepository
    ) {}

    public function execute(GetPendingSewingReceiptsRequest $request): GetPendingSewingReceiptsResponse
    {
        try {
            $recibosCostura = collect(
                $this->receiptRepository->findPendingSewingReceipts(
                    $this->buildFilters($request)
                )
            );

            Log::info('Recibos COSTURA recuperados', ['count' => $recibosCostura->count()]);

            // Procesar recibos con prendas
            $procesosConCantidad = $recibosCostura->map(function ($recibo) {
                return $this->formatReceipt($recibo);
            });

            return new GetPendingSewingReceiptsResponse($procesosConCantidad->toArray());

        } catch (\Exception $e) {
            Log::error('Error en GetPendingSewingReceipts: ' . $e->getMessage());
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
        ];
    }

    private function formatReceipt($recibo): array
    {
        $isPartial = (bool) ($recibo->es_parcial ?? false);
        $partialId = (int) ($recibo->pedido_parcial_id ?? 0);

        $proceso = [
            'fecha_creacion' => $recibo->fecha_creacion,
            'numero_recibo' => $recibo->numero_recibo,
            'prenda_id' => $recibo->prenda_id,
            'cliente' => $recibo->cliente,
            'area' => $recibo->area,
            'pedido_id' => $recibo->pedido_id,
            'asesor' => $recibo->asesor,
            'color_costura' => $recibo->color_costura,
            'es_parcial' => $isPartial,
            'pedido_parcial_id' => $partialId > 0 ? $partialId : null,
            'prendas' => collect(),
        ];

        if ($isPartial && $partialId > 0) {
            $proceso['prendas'] = collect(
                $this->receiptRepository->findPartialGarmentsByPartialId($partialId)
            );

            return $proceso;
        }

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
