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
     */
    public function construirEppConImagenes(Request $request, int $itemIndex, array $item): array
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
        ];

        // Extraer imágenes de EPP desde FormData
        $imagenesEpp = $this->formDataProcessor->extraerImagenesEpp($request, $itemIndex);
        foreach ($imagenesEpp as $imagenIdx => $archivo) {
            $path = $archivo->store('epp/temp', 'local');
            $eppData['imagenes'][] = [
                'archivo' => $path,
                'principal' => $imagenIdx === 0,
                'orden' => $imagenIdx,
            ];
        }

        return $eppData;
    }
}
