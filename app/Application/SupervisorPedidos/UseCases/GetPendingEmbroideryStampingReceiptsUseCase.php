<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsRequest;
use App\Application\SupervisorPedidos\DTOs\GetPendingEmbroideryStampingReceiptsResponse;
use App\Application\SupervisorPedidos\Support\CalculaDiasRestantesEntrega;
use App\Domain\SupervisorPedidos\Repositories\ReceiptRepository;

class GetPendingEmbroideryStampingReceiptsUseCase
{
    use CalculaDiasRestantesEntrega;

    public function __construct(
        private readonly ReceiptRepository $receiptRepository
    ) {}

    public function execute(GetPendingEmbroideryStampingReceiptsRequest $request): GetPendingEmbroideryStampingReceiptsResponse
    {
        try {
            $receiptTypes = $request->getReceiptTypes();
            $busqueda = $request->getBusqueda();
            $procesosPendientes = collect(
                $this->receiptRepository->findPendingEmbroideryStampingReceipts($receiptTypes, $busqueda)
            );

            $prendaIds = $procesosPendientes
                ->pluck('prenda_id')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $parcialIds = $procesosPendientes
                ->map(fn ($proceso) => $this->resolveParcialId($proceso))
                ->filter(fn ($id) => $id !== null)
                ->unique()
                ->values()
                ->all();

            $cantidadPorPrenda = $this->receiptRepository->sumQuantitiesByPrendaIds($prendaIds);
            $cantidadPorParcial = $this->receiptRepository->sumQuantitiesByPartialIds($parcialIds);

            $procesosConCantidad = $procesosPendientes->map(function ($proceso) use ($cantidadPorPrenda, $cantidadPorParcial) {
                $parcialId = $this->resolveParcialId($proceso);
                $proceso->dias_restantes = $this->calcularDiasRestantesEntrega(
                    $proceso->aprobado_por_cartera_en ?? null,
                    isset($proceso->dia_de_entrega) ? (int) $proceso->dia_de_entrega : null,
                    $proceso->fecha_estimada_de_entrega ?? null
                );

                if ($parcialId !== null) {
                    $proceso->cantidad_total_prendas = (int) ($cantidadPorParcial[$parcialId] ?? 0);
                    return $proceso;
                }

                $proceso->cantidad_total_prendas = (int) ($cantidadPorPrenda[$proceso->prenda_id] ?? 0);
                return $proceso;
            });

            return new GetPendingEmbroideryStampingReceiptsResponse($procesosConCantidad->toArray());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function resolveParcialId(object $proceso): ?int
    {
        $parcialId = isset($proceso->pedido_parcial_id) ? (int) $proceso->pedido_parcial_id : 0;
        if ($parcialId > 0) {
            return $parcialId;
        }

        return $this->extractParcialIdFromNotes($proceso->recibo_notas ?? null);
    }

    private function extractParcialIdFromNotes(?string $notas): ?int
    {
        $notas = (string) ($notas ?? '');

        if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
