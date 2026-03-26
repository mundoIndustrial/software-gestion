<?php

namespace App\Infrastructure\Services\Pedidos;

use App\Application\Services\PrendaProcesoService;
use App\Models\PrendaPedido;
use Illuminate\Support\Facades\Log;

class PrendaRelationsPersistenceService
{
    public function __construct(
        private PrendaImagenService $prendaImagenService,
        private PrendaLogoPersistenceService $prendaLogoPersistenceService,
        private TelaImagenService $telaImagenService,
        private PrendaProcesoService $prendaProcesoService,
    ) {
    }

    public function guardarRelaciones(PrendaPedido $prenda, array $prendaData): void
    {
        $fotosPrenda = $prendaData['fotos'] ?? $prendaData['imagenes'] ?? [];
        if (!empty($fotosPrenda)) {
            Log::info(' [PedidoPrendaService] Guardando fotos de prenda via PrendaImagenService', [
                'prenda_id' => $prenda->id,
                'cantidad_fotos' => count($fotosPrenda),
            ]);
            $this->prendaImagenService->guardarFotosPrenda(
                $prenda->id,
                $prenda->pedido_produccion_id,
                $fotosPrenda
            );
        }

        if (!empty($prendaData['logos'])) {
            $this->prendaLogoPersistenceService->guardarLogos($prenda->id, $prendaData['logos']);
        }

        Log::info(' [PedidoPrendaService::guardarPrenda] Verificando si hay telas para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_telas' => !empty($prendaData['telas']),
            'cantidad_telas' => !empty($prendaData['telas']) ? count($prendaData['telas']) : 0,
            'telas_data' => $prendaData['telas'] ?? null,
        ]);

        if (!empty($prendaData['telas'])) {
            $this->telaImagenService->guardarFotosTelas(
                $prenda->id,
                $prenda->pedido_produccion_id,
                $prendaData['telas']
            );
        } else {
            Log::warning(' [PedidoPrendaService] No hay telas para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
                'prenda_data_keys' => array_keys($prendaData),
            ]);
        }

        Log::info(' [PedidoPrendaService::guardarPrenda] Verificando si hay procesos para guardar', [
            'prenda_id' => $prenda->id,
            'tiene_procesos' => !empty($prendaData['procesos']),
            'cantidad_procesos' => !empty($prendaData['procesos']) ? count($prendaData['procesos']) : 0,
            'procesos_data' => $prendaData['procesos'] ?? null,
        ]);

        if (!empty($prendaData['procesos'])) {
            $this->prendaProcesoService->guardarProcesosPrenda(
                $prenda->id,
                $prenda->pedido_produccion_id,
                $this->normalizarProcesos($prendaData['procesos'])
            );
        } else {
            Log::info(' [PedidoPrendaService] No hay procesos para guardar en esta prenda', [
                'prenda_id' => $prenda->id,
            ]);
        }
    }

    private function normalizarProcesos(array $procesos): array
    {
        $procesosNormalizados = [];

        foreach ($procesos as $key => $proceso) {
            if (isset($proceso['datos']) && is_array($proceso['datos'])) {
                $procesosNormalizados[] = array_merge(
                    ['tipo' => $proceso['tipo'] ?? $key],
                    $proceso['datos'],
                    isset($proceso['modoTallas']) ? ['modoTallas' => $proceso['modoTallas']] : [],
                    isset($proceso['modo_tallas']) ? ['modo_tallas' => $proceso['modo_tallas']] : []
                );
                continue;
            }

            $procesosNormalizados[] = $proceso;
        }

        return $procesosNormalizados;
    }
}
