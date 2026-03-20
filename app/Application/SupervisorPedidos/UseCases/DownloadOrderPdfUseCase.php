<?php

namespace App\Application\SupervisorPedidos\UseCases;

use App\Application\SupervisorPedidos\DTOs\DownloadOrderPdfRequest;
use App\Application\SupervisorPedidos\DTOs\DownloadOrderPdfResponse;
use App\Models\PedidoProduccion;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class DownloadOrderPdfUseCase
{
    public function execute(DownloadOrderPdfRequest $request): DownloadOrderPdfResponse
    {
        try {
            $orden = PedidoProduccion::with([
                'prendas' => function($q) {
                    $q->with(['color', 'tela', 'tipoManga', 'tipoBrocheBoton']);
                },
                'prendas.procesos'
            ])->findOrFail($request->getOrderId());

            $pdf = Pdf::loadView('supervisor-pedidos.pdf', compact('orden'));
            $filename = 'Orden_' . $orden->numero_pedido . '.pdf';

            Log::info('[DownloadOrderPdfUseCase] Generated PDF for order: ' . $orden->numero_pedido);

            return new DownloadOrderPdfResponse($pdf, $filename);

        } catch (\Exception $e) {
            Log::error('[DownloadOrderPdfUseCase] Error: ' . $e->getMessage(), [
                'orden_id' => $request->getOrderId()
            ]);
            throw $e;
        }
    }
}
