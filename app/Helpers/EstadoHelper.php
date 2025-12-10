<?php

namespace App\Helpers;

use App\Domain\Cotizacion\ValueObjects\EstadoCotizacion;
use App\Enums\EstadoPedido;
use Illuminate\Support\Str;

/**
 * EstadoHelper
 * 
 * Helper para transformar valores de enums a etiquetas legibles
 * y para acceder a propiedades como color e icono
 */
class EstadoHelper
{
    /**
     * Obtener el label de un estado de cotización
     * 
     * @param string|EstadoCotizacion $estado
     * @return string
     */
    public static function labelCotizacion(string|EstadoCotizacion $estado): string
    {
        if ($estado instanceof EstadoCotizacion) {
            return $estado->label();
        }

        try {
            return EstadoCotizacion::from($estado)->label();
        } catch (\ValueError) {
            return self::humanizar($estado);
        }
    }

    /**
     * Obtener el color de un estado de cotización
     * 
     * @param string|EstadoCotizacion $estado
     * @return string
     */
    public static function colorCotizacion(string|EstadoCotizacion $estado): string
    {
        if ($estado instanceof EstadoCotizacion) {
            return $estado->colorUI();
        }

        try {
            return EstadoCotizacion::from($estado)->colorUI();
        } catch (\ValueError) {
            return 'secondary';
        }
    }

    /**
     * Obtener el icono de un estado de cotización
     * 
     * @param string|EstadoCotizacion $estado
     * @return string
     */
    public static function iconoCotizacion(string|EstadoCotizacion $estado): string
    {
        // El enum no tiene método icon(), retornar vacío
        return '';
    }

    /**
     * Obtener el label de un estado de pedido
     * 
     * @param string|EstadoPedido $estado
     * @return string
     */
    public static function labelPedido(string|EstadoPedido $estado): string
    {
        if ($estado instanceof EstadoPedido) {
            return $estado->label();
        }

        try {
            return EstadoPedido::from($estado)->label();
        } catch (\ValueError) {
            return self::humanizar($estado);
        }
    }

    /**
     * Obtener el color de un estado de pedido
     * 
     * @param string|EstadoPedido $estado
     * @return string
     */
    public static function colorPedido(string|EstadoPedido $estado): string
    {
        if ($estado instanceof EstadoPedido) {
            return $estado->color();
        }

        try {
            return EstadoPedido::from($estado)->color();
        } catch (\ValueError) {
            return 'gray';
        }
    }

    /**
     * Obtener el icono de un estado de pedido
     * 
     * @param string|EstadoPedido $estado
     * @return string
     */
    public static function iconoPedido(string|EstadoPedido $estado): string
    {
        if ($estado instanceof EstadoPedido) {
            return $estado->icon();
        }

        try {
            return EstadoPedido::from($estado)->icon();
        } catch (\ValueError) {
            return 'question-circle';
        }
    }

    /**
     * Humanizar un estado: convierte guiones bajos a espacios y pone en mayúscula
     * ENVIADA_CONTADOR → Enviada Contador
     * 
     * @param string $estado
     * @return string
     */
    public static function humanizar(string $estado): string
    {
        // Reemplazar guiones bajos con espacios
        $texto = str_replace('_', ' ', $estado);
        
        // Convertir a titulo (primera letra de cada palabra en mayúscula)
        return Str::title(Str::lower($texto));
    }

    /**
     * Obtener todos los estados de cotización con sus propiedades
     * 
     * @return array
     */
    public static function todosEstadosCotizacion(): array
    {
        $estados = [];

        foreach (EstadoCotizacion::cases() as $caso) {
            $estados[] = [
                'valor' => $caso->value,
                'label' => $caso->label(),
                'color' => $caso->color(),
                'icono' => $caso->icon(),
            ];
        }

        return $estados;
    }

    /**
     * Obtener todos los estados de pedido con sus propiedades
     * 
     * @return array
     */
    public static function todosEstadosPedido(): array
    {
        $estados = [];

        foreach (EstadoPedido::cases() as $caso) {
            $estados[] = [
                'valor' => $caso->value,
                'label' => $caso->label(),
                'color' => $caso->color(),
                'icono' => $caso->icon(),
            ];
        }

        return $estados;
    }
}
