<?php

namespace App\Domain\Pedidos;

/**
 * Constantes del dominio de Pedidos de Producción
 *
 * Centraliza todos los valores constantes relacionados con pedidos,
 * estados, procesos y configuraciones del dominio.
 */
class PedidoConstants
{
    // ========== ESTADOS DE COTIZACIÓN (para crear pedido) ==========
    // Fuente: estados persistidos en tabla `cotizaciones.estado`.
    // Incluye compatibilidad con nombre legacy `APROBADA_POR_APROBADOR`.
    const COTIZACIONES_PARA_PEDIDO = [
        'APROBADA_APROBADOR',
        'APROBADA_POR_APROBADOR',
        'ACEPTADA',
    ];

    // ========== ESTADOS DE PEDIDO ==========
    const ESTADO_PENDIENTE = 'Pendiente';
    const ESTADO_NO_INICIADO = 'No iniciado';
    const ESTADO_EN_EJECUCION = 'En Ejecución';
    const ESTADO_ENTREGADO = 'Entregado';
    const ESTADO_ANULADA = 'Anulada';
    const ESTADO_PENDIENTE_SUPERVISOR = 'PENDIENTE_SUPERVISOR';
    const ESTADO_PENDIENTE_INSUMOS = 'PENDIENTE_INSUMOS';
    const ESTADO_PENDIENTE_CARTERA = 'pendiente_cartera';
    const ESTADO_RECHAZADO_CARTERA = 'RECHAZADO_CARTERA';
    const ESTADO_DEVUELTO_A_ASESORA = 'DEVUELTO_A_ASESORA';

    // Array completo de estados
    const ESTADOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_NO_INICIADO,
        self::ESTADO_EN_EJECUCION,
        self::ESTADO_ENTREGADO,
        self::ESTADO_ANULADA,
        self::ESTADO_PENDIENTE_SUPERVISOR,
        self::ESTADO_PENDIENTE_INSUMOS,
        self::ESTADO_PENDIENTE_CARTERA,
        self::ESTADO_RECHAZADO_CARTERA,
        self::ESTADO_DEVUELTO_A_ASESORA
    ];

    // Estados con nombres formateados para display
    const ESTADOS_DISPLAY = [
        'Pendiente',
        'No iniciado',
        'En Ejecución',
        'Entregado',
        'Anulada',
        'Pendiente Supervisor',
        'Pendiente Insumos',
        'Pendiente Cartera',
        'Rechazado Cartera',
        'Devuelto a Asesora'
    ];

    // ========== ESTADOS DE PROCESO ==========
    const PROCESO_EN_PROGRESO = 'En Progreso';
    const PROCESO_PENDIENTE = 'Pendiente';
    const PROCESO_COMPLETADO = 'Completado';
    const PROCESO_PAUSADO = 'Pausado';

    // ========== PROCESOS PRIORITARIOS ==========
    const PROCESOS_PRIORITARIOS = [
        'Despacho',
        'Insumos y Telas',
        'Costura',
        'Corte',
        'Control Calidad',
        'Creación de Orden',
        'tcc'
    ];

    // ========== DÍAS DE ENTREGA ==========
    const DIAS_ENTREGA = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35];

    // ========== VALORES POR DEFECTO ==========
    const VALOR_POR_DEFECTO_SEPARADOR = '-';
    const VALOR_POR_DEFECTO_DIAS = '-';

    // ========== FORMAS DE PAGO ==========
    const FORMAS_PAGO = [
        'Contado',
        'Crédito 15 días',
        'Crédito 30 días',
        'Crédito 60 días',
        'Transferencia',
        'Cheque'
    ];

    // ========== TÉCNICAS DE CONFECCIÓN ==========
    const TECNICAS_CONFECCION = [
        'Bordado',
        'Estampado',
        'DTF',
        'Sublimado',
        'Tejido',
    ];

    // ========== ESTADOS ADICIONALES ==========
    const ESTADO_COMPLETADO = 'Completado';

    // ========== MÉTODOS DE ACCESO ==========

    /**
     * Obtener array de estados para validación
     */
    public static function getEstados(): array
    {
        return self::ESTADOS;
    }

    /**
     * Obtener array de estados formateados para display
     */
    public static function getEstadosDisplay(): array
    {
        return self::ESTADOS_DISPLAY;
    }

    /**
     * Obtener array de procesos prioritarios
     */
    public static function getProcesosPrioritarios(): array
    {
        return self::PROCESOS_PRIORITARIOS;
    }

    /**
     * Obtener array de días de entrega disponibles
     */
    public static function getDiasEntrega(): array
    {
        return self::DIAS_ENTREGA;
    }

    /**
     * Verificar si un estado es válido
     */
    public static function esEstadoValido(string $estado): bool
    {
        return in_array($estado, self::ESTADOS);
    }

    /**
     * Verificar si un proceso es prioritario
     */
    public static function esProcesoPrioritario(string $proceso): bool
    {
        return in_array($proceso, self::PROCESOS_PRIORITARIOS);
    }

    /**
     * Obtener array de formas de pago disponibles
     */
    public static function getFormasPago(): array
    {
        return self::FORMAS_PAGO;
    }

    /**
     * Obtener array de técnicas de confección disponibles
     */
    public static function getTecnicasConfeccion(): array
    {
        return self::TECNICAS_CONFECCION;
    }
}
