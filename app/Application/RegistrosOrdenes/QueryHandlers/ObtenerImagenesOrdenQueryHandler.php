<?php

namespace App\Application\RegistrosOrdenes\QueryHandlers;

use App\Domain\RegistrosOrdenes\Contracts\ImagenesOrdenService;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerImagenesOrdenQueryHandler
 * 
 * Handler para obtener imágenes de órdenes
 */
class ObtenerImagenesOrdenQueryHandler
{
    public function __construct(
        private ImagenesOrdenService $imagenesService,
    ) {}

    /**
     * Ejecutar query de imágenes
     */
    public function handle($numeroPedido, $tipo = null)
    {
        try {
            $imagenes = [];

            if ($tipo === 'logo') {
                $imagenes = $this->imagenesService->obtenerImagenesLogo($numeroPedido);
            } else {
                $imagenes = $this->imagenesService->obtenerImagenesOrden($numeroPedido);
            }

            Log::info('Imágenes de orden obtenidas', [
                'numero_pedido' => $numeroPedido,
                'tipo' => $tipo,
                'total' => count($imagenes),
            ]);

            return [
                'success' => true,
                'pedido' => $numeroPedido,
                'tipo' => $tipo ?? 'all',
                'imagenes' => $imagenes,
            ];

        } catch (\Exception $e) {
            Log::error('Error en ObtenerImagenesOrdenQueryHandler: ' . $e->getMessage());
            throw $e;
        }
    }
}
