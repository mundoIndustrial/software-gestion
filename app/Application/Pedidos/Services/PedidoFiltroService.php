<?php

namespace App\Application\Pedidos\Services;

use Illuminate\Support\Facades\Log;

/**
 * PedidoFiltroService
 * Servicio que encapsula la lógica de filtrado de datos dentro de un pedido
 * según el rol del usuario.
 * Responsabilidades:
 * - Filtrar procesos para bodeguero (solo costura-bodega)
 * - Filtrar prendas para insumos (de_bodega=false)
 * - Validar que queda contenido después del filtro
 */
class PedidoFiltroService
{
    /**
     * Aplica filtro de bodeguero: solo procesos COSTURA-BODEGA
     * @return array Tupla [responseData, mensajeError]
     */
    public function filtrarParaBodeguero(int $pedidoId, array &$responseData): ?string
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return null;
        }

        Log::info('[PedidoFiltroService] FILTRO BODEGUERO: Filtrando procesos - Solo COSTURA-BODEGA', [
            'pedido_id' => $pedidoId,
            'total_prendas' => count($responseData['prendas'])
        ]);

        foreach ($responseData['prendas'] as &$prenda) {
            if (!isset($prenda['procesos']) || !is_array($prenda['procesos'])) {
                continue;
            }

            $procesosFiltrados = array_filter($prenda['procesos'], function ($proceso) {
                $tipoProceso = $proceso['tipo_proceso'] ?? $proceso['nombre_proceso'] ?? $proceso['nombre'] ?? $proceso['proceso'] ?? '';
                $tipoLower = strtolower(trim($tipoProceso));

                return $tipoLower === 'costura-bodega' || $tipoLower === 'costurabodega';
            });

            $prenda['procesos'] = array_values($procesosFiltrados);
        }
        unset($prenda);

        // Verificar que existe al menos un proceso costura-bodega
        $tieneProcesoCosturaBodega = false;
        foreach ($responseData['prendas'] as $prenda) {
            if (isset($prenda['procesos']) && is_array($prenda['procesos']) && !empty($prenda['procesos'])) {
                $tieneProcesoCosturaBodega = true;
                break;
            }
        }

        if (!$tieneProcesoCosturaBodega) {
            Log::warning('[PedidoFiltroService]  Bodeguero sin procesos costura-bodega', [
                'pedido_id' => $pedidoId,
                'usuario_id' => auth()?->id()
            ]);

            return 'Este pedido no tiene procesos de COSTURA-BODEGA disponibles';
        }

        return null;
    }

    /**
     * Aplica filtro de insumos: solo prendas con de_bodega = false
     * @return ?string Mensaje de error si no quedan prendas
     */
    public function filtrarParaInsumos(int $pedidoId, array &$responseData): ?string
    {
        if (!isset($responseData['prendas']) || !is_array($responseData['prendas'])) {
            return null;
        }

        Log::info('[PedidoFiltroService] FILTRO INSUMOS: Mostrando solo prendas con de_bodega = false', [
            'pedido_id' => $pedidoId,
            'total_prendas_antes' => count($responseData['prendas'])
        ]);

        $prendasFiltradas = array_filter($responseData['prendas'], function ($prenda) {
            $deBodega = $prenda['de_bodega'] ?? false;
            if (is_string($deBodega)) {
                $deBodega = filter_var($deBodega, FILTER_VALIDATE_BOOLEAN);
            }
            return !$deBodega;
        });

        $responseData['prendas'] = array_values($prendasFiltradas);

        Log::info('[PedidoFiltroService] Prendas filtradas para insumos', [
            'pedido_id' => $pedidoId,
            'total_prendas_despues' => count($prendasFiltradas)
        ]);

        if (empty($prendasFiltradas)) {
            Log::warning('[PedidoFiltroService]  Insumos sin prendas disponibles', [
                'pedido_id' => $pedidoId,
                'usuario_id' => auth()?->id()
            ]);

            return 'Este pedido no tiene prendas disponibles para insumos (todas son de bodega)';
        }

        return null;
    }
}
