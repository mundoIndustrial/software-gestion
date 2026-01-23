<?php

namespace App\Application\Pedidos\Catalogs;

use App\Enums\EstadoPedido;

/**
 * EstadoPedidoCatalog
 * 
 * Centraliza todas las constantes y definiciones de estados de pedidos.
 * Ãšnica fuente de verdad para estados vÃ¡lidos, transiciones, mensajes de error, etc.
 * 
 * BENEFICIOS:
 * - 1 lugar para actualizar si cambian los estados
 * - Reutilizable en todas las capas (Application, Domain, Infrastructure)
 * - Consistencia garantizada
 * - FÃ¡cil testing
 * 
 * ELIMINA:
 * - DuplicaciÃ³n de constantes en mÃºltiples clases
 * - Validaciones de estados esparcidas en el cÃ³digo
 * - Mensajes de error inconsistentes
 * 
 * Uso:
 *   if (!EstadoPedidoCatalog::esValido($estado)) {
 *       throw new InvalidArgumentException(...);
 *   }
 */
final class EstadoPedidoCatalog
{
    /**
     * Estados vÃ¡lidos del sistema
     */
    public const ESTADOS_VALIDOS = [
        'PENDIENTE_SUPERVISOR',
        'APROBADO_SUPERVISOR',
        'EN_PRODUCCION',
        'FINALIZADO',
    ];

    /**
     * Transiciones permitidas: desde_estado => [a_estados_permitidos]
     */
    private const TRANSICIONES_PERMITIDAS = [
        'PENDIENTE_SUPERVISOR' => ['APROBADO_SUPERVISOR'],
        'APROBADO_SUPERVISOR' => ['EN_PRODUCCION'],
        'EN_PRODUCCION' => ['FINALIZADO'],
        'FINALIZADO' => [],
    ];

    /**
     * Mensajes de error estÃ¡ndar - ÃšNICA FUENTE DE VERDAD
     */
    private const MENSAJES_ERROR = [
        'pedido_no_encontrado' => 'Pedido {identificador} no encontrado',
        'estado_invalido' => "Estado '{estado}' no es vÃ¡lido. Estados permitidos: {estados_validos}",
        'transicion_no_permitida' => "No se puede cambiar de estado {estado_actual} a {estado_nuevo}",
        'pedido_no_tiene_prendas' => 'Pedido {identificador} no tiene prendas agregadas',
        'prenda_no_encontrada' => 'Prenda {identificador} no encontrada en el pedido',
        'validacion_fallida' => 'ValidaciÃ³n fallida: {razon}',
    ];

    /**
     * Colores para UI
     */
    private const COLORES_UI = [
        'PENDIENTE_SUPERVISOR' => '#3b82f6',    // Azul
        'APROBADO_SUPERVISOR' => '#f59e0b',     // Naranja
        'EN_PRODUCCION' => '#f97316',           // Naranja oscuro
        'FINALIZADO' => '#10b981',              // Verde
    ];

    /**
     * Etiquetas legibles
     */
    private const ETIQUETAS = [
        'PENDIENTE_SUPERVISOR' => 'Pendiente de Supervisor',
        'APROBADO_SUPERVISOR' => 'Aprobado por Supervisor',
        'EN_PRODUCCION' => 'En ProducciÃ³n',
        'FINALIZADO' => 'Finalizado',
    ];

    /**
     * Validar si un estado es vÃ¡lido
     */
    public static function esValido(?string $estado): bool
    {
        return !empty($estado) && in_array($estado, self::ESTADOS_VALIDOS, true);
    }

    /**
     * Validar si una transiciÃ³n es permitida
     */
    public static function esTransicionPermitida(string $estadoActual, string $estadoNuevo): bool
    {
        if (!self::esValido($estadoActual) || !self::esValido($estadoNuevo)) {
            return false;
        }

        $transiciones = self::TRANSICIONES_PERMITIDAS[$estadoActual] ?? [];
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
     * Obtener todos los estados vÃ¡lidos
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

