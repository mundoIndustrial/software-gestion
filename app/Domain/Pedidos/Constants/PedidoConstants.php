<?php

namespace App\Domain\Pedidos\Constants;

/**
 * PedidoConstants
 * 
 * RESPONSABILIDAD ÚNICA:
 * - Centralizar todas las constantes del dominio Pedidos
 * - Evitar duplicación de valores hardcodeados
 * - Punto único de cambio para valores que se usan en múltiples lugares
 * 
 * ANTES (ANTIPATRÓN):
 * ```php
 * // En CrearPedidoEditableController
 * $formasPago = ['Contado', 'Crédito 15 días', ...];
 * 
 * // En OtroController
 * $formasPago = ['Contado', 'Crédito 15 días', ...];
 * ```
 * 
 * AHORA (CORRECTO):
 * ```php
 * use App\Domain\Pedidos\Constants\PedidoConstants;
 * $formasPago = PedidoConstants::FORMAS_PAGO;
 * ```
 */
class PedidoConstants
{
    // ========================================
    // FORMAS DE PAGO
    // ========================================
    public const FORMAS_PAGO = [
        'Contado',
        'Crédito 15 días',
        'Crédito 30 días',
        'Crédito 60 días',
        'Transferencia',
        'Cheque'
    ];

    // ========================================
    // TÉCNICAS DE CONFECCIÓN
    // ========================================
    public const TECNICAS_CONFECCION = [
        'Bordado',
        'Estampado',
        'DTF',
        'Sublimado',
        'Tejido',
        'Serigrafía'
    ];

    // ========================================
    // ESTADOS DE PEDIDO
    // ========================================
    public const ESTADO_BORRADOR = 'Borrador';
    public const ESTADO_PENDIENTE = 'Pendiente';
    public const ESTADO_EN_PRODUCCION = 'En Producción';
    public const ESTADO_COMPLETADO = 'completado';
    public const ESTADO_CANCELADO = 'Cancelado';

    public const ESTADOS_PEDIDO = [
        self::ESTADO_BORRADOR,
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PRODUCCION,
        self::ESTADO_COMPLETADO,
        self::ESTADO_CANCELADO,
    ];

    // ========================================
    // ESTADOS DE COTIZACIÓN
    // ========================================
    public const COTIZACION_APROBADA_COTIZACIONES = 'APROBADA_COTIZACIONES';
    public const COTIZACION_APROBADO_PARA_PEDIDO = 'APROBADO_PARA_PEDIDO';
    
    public const COTIZACIONES_PARA_PEDIDO = [
        self::COTIZACION_APROBADA_COTIZACIONES,
        self::COTIZACION_APROBADO_PARA_PEDIDO,
    ];

    // ========================================
    // TIPOS DE ITEMS
    // ========================================
    public const TIPO_ITEM_PRENDA = 'prenda';
    public const TIPO_ITEM_EPP = 'epp';

    // ========================================
    // CONFIGURACIÓN DE TIMERS
    // ========================================
    public const PRECISION_TIMER_MS = 2;      // Milisegundos con 2 decimales
    public const PRECISION_TIMER_SEG = 4;     // Segundos con 4 decimales
}
