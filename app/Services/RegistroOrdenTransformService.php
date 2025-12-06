<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para transformación y formateo de datos de órdenes
 * 
 * Centraliza:
 * - Conversión de orden a array con campos sensibles filtrados
 * - Adición de campos calculados (área, encargado, descripción de prendas)
 * - Ocultamiento de campos según rol del usuario
 */
class RegistroOrdenTransformService
{
    /**
     * Transformar orden a array con campos sensibles filtrados
     * 
     * Incluye:
     * - Filtrado de campos sensibles por rol
     * - Adición de área y encargado desde procesos_prenda
     * - Descripción de prendas calculada
     * 
     * @param PedidoProduccion $orden Orden a transformar
     * @param array $areasMap Mapa de áreas por número de pedido
     * @param array $encargadosMap Mapa de encargados por número de pedido
     * @return array Orden transformada
     */
    public function transformarOrden(
        PedidoProduccion $orden,
        array $areasMap = [],
        array $encargadosMap = []
    ): array {
        $ordenArray = $orden->toArray();

        // Campos que se ocultan para todos los usuarios
        $camposOcultosGlobal = ['created_at', 'updated_at', 'deleted_at', 'asesor_id', 'cliente_id'];

        // Campos que se ocultan para no-asesores
        $camposOcultosNoAsesor = ['cotizacion_id', 'numero_cotizacion'];

        // Agregar nombre del asesor en lugar de ID
        if ($orden->asesora) {
            $ordenArray['asesor'] = $orden->asesora->name ?? '';
        } else {
            $ordenArray['asesor'] = '';
        }

        // Agregar nombre del cliente
        if (!empty($ordenArray['cliente_id'])) {
            try {
                $cliente = \App\Models\Cliente::find($ordenArray['cliente_id']);
                $ordenArray['cliente_nombre'] = $cliente ? $cliente->nombre : ($ordenArray['cliente'] ?? '');
            } catch (\Exception $e) {
                $ordenArray['cliente_nombre'] = $ordenArray['cliente'] ?? '';
            }
        } else {
            $ordenArray['cliente_nombre'] = $ordenArray['cliente'] ?? '';
        }

        // Agregar el área desde procesos_prenda
        $ordenArray['area'] = $areasMap[$orden->numero_pedido] ?? 'Creación Orden';

        // Agregar el encargado de "Creación Orden"
        $ordenArray['encargado_orden'] = $encargadosMap[$orden->numero_pedido] ?? '';

        // Agregar descripción de prendas
        $ordenArray['descripcion_prendas'] = $orden->descripcion_prendas ?? '';

        // Eliminar campos ocultos globales
        foreach ($camposOcultosGlobal as $campo) {
            unset($ordenArray[$campo]);
        }

        // Eliminar campos sensibles para no-asesores
        if (!auth()->user() || !auth()->user()->role || auth()->user()->role->name !== 'asesor') {
            foreach ($camposOcultosNoAsesor as $campo) {
                unset($ordenArray[$campo]);
            }
        }

        return $ordenArray;
    }

    /**
     * Transformar múltiples órdenes
     * 
     * @param array $ordenes Array de órdenes a transformar
     * @param array $areasMap Mapa de áreas
     * @param array $encargadosMap Mapa de encargados
     * @return array Array de órdenes transformadas
     */
    public function transformarMultiples(
        array $ordenes,
        array $areasMap = [],
        array $encargadosMap = []
    ): array {
        return array_map(function ($orden) use ($areasMap, $encargadosMap) {
            return $this->transformarOrden($orden, $areasMap, $encargadosMap);
        }, $ordenes);
    }
}
