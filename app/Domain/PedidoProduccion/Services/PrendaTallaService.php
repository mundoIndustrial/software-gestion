<?php

namespace App\Domain\PedidoProduccion\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PrendaTallaService
 * 
 * Responsabilidad: Guardar tallas de prendas en la BD
 */
class PrendaTallaService
{
    /**
     * Guardar tallas de prenda
     */
    public function guardarTallasPrenda(int $prendaId, mixed $cantidades): void
    {
        try {
            $tallasCantidades = [];
            
            if (is_string($cantidades)) {
                $tallasCantidades = json_decode($cantidades, true) ?? [];
            } elseif (is_array($cantidades)) {
                $tallasCantidades = $cantidades;
            }

            if (empty($tallasCantidades)) {
                Log::warning(' [PrendaTallaService] Cantidades vacías', ['prenda_id' => $prendaId]);
                return;
            }

            $registros = [];
            foreach ($tallasCantidades as $talla => $cantidad) {
                if ($talla && $cantidad > 0) {
                    $registros[] = [
                        'prenda_ped_id' => $prendaId,
                        'talla' => (string)$talla,
                        'cantidad' => (int)$cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (!empty($registros)) {
                \App\Models\PrendaTalaPed::insert($registros);
                
                Log::info(' [PrendaTallaService] Tallas guardadas correctamente', [
                    'prenda_ped_id' => $prendaId,
                    'total_tallas' => count($registros),
                ]);
            }
        } catch (\Exception $e) {
            Log::error(' [PrendaTallaService] Error al guardar tallas', [
                'prenda_ped_id' => $prendaId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
