<?php

namespace App\Application\Bodega\Constants;

/**
 * Constantes para el módulo de Bodega
 * Centraliza todos los valores hardcodeados para evitar typos y facilitar cambios
 */
class WarehouseConstants
{
    // ==================== ROLES ====================
    
    const ROLE_EPP_BODEGA = 'EPP-Bodega';
    const ROLE_COSTURA_BODEGA = 'Costura-Bodega';
    
    // ==================== ÁREAS ====================
    
    const AREA_EPP = 'EPP';
    const AREA_COSTURA = 'Costura';
    
    // ==================== ESTADOS ====================
    
    const STATE_PENDING = 'Pendiente';
    const STATE_DELIVERED = 'Entregado';
    const STATE_CANCELLED = 'Anulada';
    const STATE_CANCELLED_UPPER = 'ANULADA';
    
    // ==================== CAMPOS DE BD ====================
    
    const FIELD_ESTADO_BODEGA = 'estado_bodega';
    const FIELD_EPP_ESTADO = 'epp_estado';
    const FIELD_COSTURA_ESTADO = 'costura_estado';
    const FIELD_AREA = 'area';
    const FIELD_NUMERO_PEDIDO = 'numero_pedido';
    const FIELD_TALLA = 'talla';
    const FIELD_PRENDA_NOMBRE = 'prenda_nombre';
    const FIELD_CANTIDAD = 'cantidad';
    const FIELD_TALLA_COLOR_ID = 'talla_color_id';
    const FIELD_PRENDA_ID = 'prenda_id';
    const FIELD_PEDIDO_EPP_ID = 'pedido_epp_id';
    const FIELD_PEDIDO_PRODUCCION_ID = 'pedido_produccion_id';
    const FIELD_PENDIENTES = 'pendientes';
    const FIELD_OBSERVACIONES_BODEGA = 'observaciones_bodega';
    const FIELD_FECHA_ENTREGA = 'fecha_entrega';
    const FIELD_FECHA_PEDIDO = 'fecha_pedido';
    const FIELD_USUARIO_BODEGA_ID = 'usuario_bodega_id';
    const FIELD_USUARIO_BODEGA_NOMBRE = 'usuario_bodega_nombre';
    const FIELD_ROW_HASH = 'row_hash';
    const FIELD_GENERO = 'genero';
    const FIELD_ASESOR = 'asesor';
    const FIELD_EMPRESA = 'empresa';
    
    // ==================== FILTROS DE VISTAS ====================
    
    const VIEW_DETAILS = 'details';
    const VIEW_LIST = 'list';
    
    // ==================== VALORES ESPECIALES ====================
    
    const ESTADO_ANULADA_PREFIX = 'ANULAD';  // Para validar ANULADA/ANULADO
    const MD5_LENGTH = 32;  // Longitud de hash MD5
    const GENERIC_GENDER = 'GENERICO';
    const DEFAULT_NA = 'N/A';
    const DEFAULT_SIN_NOMBRE = 'Sin nombre';
    
    // ==================== PAGINACIÓN ====================
    
    const ITEMS_PER_PAGE = 20;
    
    // ==================== ESTADO CALCULADO ====================
    
    const EVENT_BODEGA_DETAILS_UPDATED = 'BodegaDetallesActualizados';
    const EVENT_PEDIDO_UPDATED = 'PedidoActualizado';
    
    /**
     * Obtener estados permitidos para filtros
     */
    public static function getEstadosPermitidos(): array
    {
        return [
            'Pendiente',
            'EN EJECUCIÓN',
            'NO INICIADO',
            'PENDIENTE_SUPERVISOR',
            'PENDIENTE_INSUMOS',
            'DEVUELTO_A_ASESORA',
            'pendiente_cartera'
        ];
    }
    
    /**
     * Validar si un estado es "Anulada"
     */
    public static function esAnulada(string $estado): bool
    {
        return str_starts_with(strtoupper(trim($estado)), self::ESTADO_ANULADA_PREFIX);
    }
    
    /**
     * Obtener nombre mostrable del rol
     */
    public static function getRoleDisplayName(string $role): string
    {
        return match($role) {
            self::ROLE_EPP_BODEGA => 'EPP',
            self::ROLE_COSTURA_BODEGA => 'Costura',
            default => $role
        };
    }
}
