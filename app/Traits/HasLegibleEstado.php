<?php

namespace App\Traits;

use App\Helpers\EstadoHelper;

/**
 * HasLegibleEstado
 * 
 * Trait que proporciona métodos para acceder a propiedades legibles de estados
 * Se usa para transformar automáticamente los valores de enums en labels
 */
trait HasLegibleEstado
{
    /**
     * Obtener el label del estado actual (funciona con cualquier campo de estado)
     * 
     * @param string $campo Nombre del campo de estado (default: 'estado')
     * @return string
     */
    public function getEstadoLabel(string $campo = 'estado'): string
    {
        $valor = $this->{$campo};
        
        if (str_contains($campo, 'pedido') || $campo === 'estado_pedido') {
            return EstadoHelper::labelPedido($valor);
        }
        
        return EstadoHelper::labelCotizacion($valor);
    }

    /**
     * Obtener el color del estado actual
     * 
     * @param string $campo Nombre del campo de estado (default: 'estado')
     * @return string
     */
    public function getEstadoColor(string $campo = 'estado'): string
    {
        $valor = $this->{$campo};
        
        if (str_contains($campo, 'pedido') || $campo === 'estado_pedido') {
            return EstadoHelper::colorPedido($valor);
        }
        
        return EstadoHelper::colorCotizacion($valor);
    }

    /**
     * Obtener el icono del estado actual
     * 
     * @param string $campo Nombre del campo de estado (default: 'estado')
     * @return string
     */
    public function getEstadoIcono(string $campo = 'estado'): string
    {
        $valor = $this->{$campo};
        
        if (str_contains($campo, 'pedido') || $campo === 'estado_pedido') {
            return EstadoHelper::iconoPedido($valor);
        }
        
        return EstadoHelper::iconoCotizacion($valor);
    }

    /**
     * Obtener el estado formateado como array con todas sus propiedades
     * 
     * @param string $campo Nombre del campo de estado (default: 'estado')
     * @return array
     */
    public function getEstadoFormateado(string $campo = 'estado'): array
    {
        $valor = $this->{$campo};
        
        if (str_contains($campo, 'pedido') || $campo === 'estado_pedido') {
            return [
                'valor' => $valor,
                'label' => EstadoHelper::labelPedido($valor),
                'color' => EstadoHelper::colorPedido($valor),
                'icono' => EstadoHelper::iconoPedido($valor),
            ];
        }
        
        return [
            'valor' => $valor,
            'label' => EstadoHelper::labelCotizacion($valor),
            'color' => EstadoHelper::colorCotizacion($valor),
            'icono' => EstadoHelper::iconoCotizacion($valor),
        ];
    }
}
