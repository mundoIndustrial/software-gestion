<?php

namespace App\Domain\PedidoProduccion\Services;

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
     * Construir objeto EPP con imágenes desde FormData
     * Retorna los UploadedFile sin guardarlos (se guardarán en PedidoEppService)
     */
    public function construirEppConImagenes(Request $request, int $itemIndex, array $item, int $pedidoId): array
    {
        $eppData = [
            'epp_id' => $item['epp_id'] ?? null,
            'nombre' => $item['nombre'] ?? '',
            'codigo' => $item['codigo'] ?? '',
            'categoria' => $item['categoria'] ?? '',
            'talla' => $item['talla'] ?? '',
            'cantidad' => $item['cantidad'] ?? 0,
            'observaciones' => $item['observaciones'] ?? null,
            'imagenes' => [],
            'tallas_medidas' => $item['tallas_medidas'] ?? $item['talla'],
            'pedido_id' => $pedidoId,
        ];

        // Extraer imágenes de EPP desde FormData (sin guardarlas aún)
        $imagenesEpp = $this->formDataProcessor->extraerImagenesEpp($request, $itemIndex);
        
        foreach ($imagenesEpp as $imagenIdx => $archivo) {
            // Pasar el UploadedFile directamente (se guardará en PedidoEppService)
            $eppData['imagenes'][] = [
                'archivo' => $archivo,
                'principal' => $imagenIdx === 0,
                'orden' => $imagenIdx,
            ];
        }

        return $eppData;
    }
}
