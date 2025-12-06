<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Servicio para obtener información de procesos y encargados
 * 
 * Centraliza:
 * - Obtención del último proceso (área) para órdenes
 * - Obtención de encargados de "Creación de Orden"
 * - Mapeo de procesos a números de pedido
 */
class RegistroOrdenProcessService
{
    /**
     * Obtener el último proceso (área) para cada número de pedido
     * 
     * Busca en procesos_prenda el proceso más reciente (por fecha_inicio DESC)
     * para cada número de pedido
     * 
     * @param array $numeroPedidos Array de números de pedido
     * @return array Mapa [numero_pedido => proceso]
     */
    public function getLastProcessByOrderNumbers(array $numeroPedidos = []): array
    {
        $areasMap = [];

        if (empty($numeroPedidos)) {
            return $areasMap;
        }

        // Filtrar valores null y eliminar duplicados
        $numeroPedidos = array_filter(array_unique($numeroPedidos));

        if (empty($numeroPedidos)) {
            return $areasMap;
        }

        // Obtener procesos ordenados por fecha_inicio DESC (más reciente)
        $procesosActuales = DB::table('procesos_prenda')
            ->whereIn('numero_pedido', $numeroPedidos)
            ->orderBy('numero_pedido', 'asc')
            ->orderBy('fecha_inicio', 'DESC')
            ->orderBy('id', 'DESC')
            ->select('numero_pedido', 'proceso', 'fecha_inicio', 'id')
            ->get();

        // Agrupar por numero_pedido - tomar el primero (más reciente por fecha)
        foreach ($procesosActuales as $p) {
            if (!isset($areasMap[$p->numero_pedido])) {
                $areasMap[$p->numero_pedido] = $p->proceso;
            }
        }

        return $areasMap;
    }

    /**
     * Obtener encargados de "Creación de Orden" para cada número de pedido
     * 
     * @param array $numeroPedidos Array de números de pedido
     * @return array Mapa [numero_pedido => encargado]
     */
    public function getCreacionOrdenEncargados(array $numeroPedidos = []): array
    {
        $encargadosMap = [];

        if (empty($numeroPedidos)) {
            return $encargadosMap;
        }

        // Filtrar valores null y eliminar duplicados
        $numeroPedidos = array_filter(array_unique($numeroPedidos));

        if (empty($numeroPedidos)) {
            return $encargadosMap;
        }

        // Obtener el encargado del proceso "Creación de Orden" para cada pedido
        $procesos = DB::table('procesos_prenda')
            ->whereIn('numero_pedido', $numeroPedidos)
            ->where('proceso', 'Creación de Orden')
            ->select('numero_pedido', 'encargado')
            ->get();

        // Mapear numero_pedido a encargado
        foreach ($procesos as $p) {
            $encargadosMap[$p->numero_pedido] = $p->encargado ?? '';
        }

        return $encargadosMap;
    }
}
