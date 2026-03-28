<?php

namespace App\Application\Pedidos\Catalogs;

use App\Enums\EstadoPedido;

/**
 * EstadoPedidoCatalog
 * Centraliza todas las constantes y definiciones de estados de pedidos.
 * Unica fuente de verdad para estados validos, transiciones, mensajes de error, etc.
 * BENEFICIOS:
 * - 1 lugar para actualizar si cambian los estados
 * - Reutilizable en todas las capas (Application, Domain, Infrastructure)
 * - Consistencia garantizada
 * - facil testing
 * ELIMINA:
 * - Duplicación de constantes en multiples clases
 * - Validaciones de estados esparcidas en el código
 * - Mensajes de error inconsistentes
 * Uso:
 *   if (!EstadoPedidoCatalog::esValido($estado)) {
 *       throw new InvalidArgumentException(...);
 *   }
 */
final class EstadoPedidoCatalog
{
    /**
     * Estados validos del sistema
     */
    public const ESTADOS_VALIDOS = [
        'Pendiente',
        'No iniciado',
        'En Ejecución',
        'Entregado',
        'Anulada',
        'PENDIENTE_SUPERVISOR',
        'PENDIENTE_INSUMOS',
        'pendiente_cartera',
        'RECHAZADO_CARTERA',
        'DEVUELTO_A_ASESORA',
        'APROBADO_SUPERVISOR',
        'EN_PRODUCCION',
        'FINALIZADO',
    ];

    /**
     * Transiciones permitidas: desde_estado => [a_estados_permitidos]
     */
    private const TRANSICIONES_PERMITIDAS = [
        'Pendiente' => [],
        'No iniciado' => [],
        'En Ejecución' => [],
        'Entregado' => [],
        'Anulada' => [],
        'PENDIENTE_SUPERVISOR' => ['APROBADO_SUPERVISOR'],
        'PENDIENTE_INSUMOS' => [],
        'pendiente_cartera' => [],
        'RECHAZADO_CARTERA' => [],
        'DEVUELTO_A_ASESORA' => [],
        'APROBADO_SUPERVISOR' => ['EN_PRODUCCION'],
        'EN_PRODUCCION' => ['FINALIZADO'],
        'FINALIZADO' => [],
    ];

    /**
     * Mensajes de error estándar - Unica FUENTE DE VERDAD
     */
    private const MENSAJES_ERROR = [
        'pedido_no_encontrado' => 'Pedido {identificador} no encontrado',
        'estado_invalido' => "Estado '{estado}' no es valido. Estados permitidos: {estados_validos}",
        'transicion_no_permitida' => "No se puede cambiar de estado {estado_actual} a {estado_nuevo}",
        'pedido_no_tiene_prendas' => 'Pedido {identificador} no tiene prendas agregadas',
        'prenda_no_encontrada' => 'Prenda {identificador} no encontrada en el pedido',
        'validacion_fallida' => 'Validación fallida: {razon}',
    ];

    /**
     * Colores para UI
     */
    private const COLORES_UI = [
        'Pendiente' => '#3b82f6',
        'No iniciado' => '#6366f1',
        'En Ejecución' => '#f97316',
        'Entregado' => '#10b981',
        'Anulada' => '#ef4444',
        'PENDIENTE_SUPERVISOR' => '#3b82f6',    // Azul
        'PENDIENTE_INSUMOS' => '#8b5cf6',
        'pendiente_cartera' => '#f59e0b',
        'RECHAZADO_CARTERA' => '#dc2626',
        'DEVUELTO_A_ASESORA' => '#0ea5e9',
        'APROBADO_SUPERVISOR' => '#f59e0b',     // Naranja
        'EN_PRODUCCION' => '#f97316',           // Naranja oscuro
        'FINALIZADO' => '#10b981',              // Verde
    ];

    /**
     * Etiquetas legibles
     */
    private const ETIQUETAS = [
        'Pendiente' => 'Pendiente',
        'No iniciado' => 'No iniciado',
        'En Ejecución' => 'En Ejecución',
        'Entregado' => 'Entregado',
        'Anulada' => 'Anulada',
        'PENDIENTE_SUPERVISOR' => 'Pendiente de Supervisor',
        'PENDIENTE_INSUMOS' => 'Pendiente de Insumos',
        'pendiente_cartera' => 'Pendiente Cartera',
        'RECHAZADO_CARTERA' => 'Rechazado Cartera',
        'DEVUELTO_A_ASESORA' => 'Devuelto a Asesora',
        'APROBADO_SUPERVISOR' => 'Aprobado por Supervisor',
        'EN_PRODUCCION' => 'En Producción',
        'FINALIZADO' => 'Finalizado',
    ];

    /**
     * Validar si un estado es valido
     */
    public static function esValido(?string $estado): bool
    {
        return !empty($estado) && in_array($estado, self::ESTADOS_VALIDOS, true);
    }

    /**
     * Validar si una transición es permitida
     */
    public static function esTransicionPermitida(string $estadoActual, string $estadoNuevo): bool
    {
        if (!self::esValido($estadoActual) || !self::esValido($estadoNuevo)) {
            return false;
        }

        $transiciones = self::TRANSICIONES_PERMITIDAS[$estadoActual] ?? [];
        if (empty($transiciones)) {
            return true;
        }
        return in_array($estadoNuevo, $transiciones, true);
    }

    /**
     * Obtener transiciones permitidas desde un estado
     */
    public static function obtenerTransicionesPermitidas(string $estado): array
    {
        return self::TRANSICIONES_PERMITIDAS[$estado] ?? [];
    }

    /**
     * Obtener mensaje de error con placeholders reemplazados
     */
    public static function obtenerMensajeError(string $tipo, array $parametros = []): string
    {
        $mensaje = self::MENSAJES_ERROR[$tipo] ?? "Error desconocido";

        foreach ($parametros as $clave => $valor) {
            $mensaje = str_replace('{' . $clave . '}', $valor, $mensaje);
        }

        return $mensaje;
    }

    /**
     * Obtener color para UI
     */
    public static function obtenerColor(string $estado): string
    {
        return self::COLORES_UI[$estado] ?? '#9ca3af';
    }

    /**
     * Obtener etiqueta legible
     */
    public static function obtenerEtiqueta(string $estado): string
    {
        return self::ETIQUETAS[$estado] ?? $estado;
    }

    /**
     * Obtener todos los estados validos
     */
    public static function obtenerTodos(): array
    {
        $estados = [];

        foreach (self::ESTADOS_VALIDOS as $estado) {
            $estados[] = [
                'valor' => $estado,
                'etiqueta' => self::obtenerEtiqueta($estado),
                'color' => self::obtenerColor($estado),
                'transiciones' => self::obtenerTransicionesPermitidas($estado),
            ];
        }

        return $estados;
    }
}
