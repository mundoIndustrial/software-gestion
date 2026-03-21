<?php

namespace App\Domain\Pedidos\Services;

use Illuminate\Http\Request;

/**
 * Servicio de Dominio para procesar datos de EPP
 */
class EppProcessorService
{
    public function __construct(
        private FormDataProcessorService $formDataProcessor
    ) {}

    /**
     * Construir objeto EPP con imagenes desde FormData
     * Retorna los UploadedFile sin guardarlos (se guardaron en PedidoEppService)
     */
    public function construirEppConImagenes(Request $request, int $itemIndex, array $item, int $pedidoId): array
    {
        $eppData = [
            'epp_id' => $item['epp_id'] ?? null,
            'nombre' => $item['nombre'] ?? '',
            'codigo' => $item['codigo'] ?? '',
            'categoria' => $item['categoria'] ?? '',
            'cantidad' => $item['cantidad'] ?? 0,
            'observaciones' => $item['observaciones'] ?? null,
            'imagenes' => [],
            'pedido_id' => $pedidoId,
        ];

        // Extraer imagenes de EPP desde FormData (sin guardarlas año)
        $imagenesEpp = $this->formDataProcessor->extraerImagenesEpp($request, $itemIndex);
        
        foreach ($imagenesEpp as $imagenIdx => $archivo) {
            // Pasar el UploadedFile directamente (se guardaron en PedidoEppService)
            $eppData['imagenes'][] = [
                'archivo' => $archivo,
                'principal' => $imagenIdx === 0,
                'orden' => $imagenIdx,
            ];
        }

        return $eppData;
    }
}

