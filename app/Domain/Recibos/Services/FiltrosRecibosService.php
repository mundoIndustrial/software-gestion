<?php

namespace App\Domain\Recibos\Services;

use App\Models\ConsecutivoReciboPedido;
use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;

/**
 * Domain Service para validación y aplicación de filtros
 * 
 * Responsabilidades:
 * - Encapsular lógica de validación de filtros
 * - Construir queries según filtros
 * - Validar criterios de filtrado
 */
class FiltrosRecibosService
{
    /**
     * Validar que los criterios de filtro sean válidos
     * 
     * @param array $filtros
     * @return array ['valido' => bool, 'errores' => array]
     */
    public function validar(array $filtros): array
    {
        $errores = [];

        // Validar áreas
        if (isset($filtros['areas']) && !empty($filtros['areas'])) {
            $areasValidas = config('recibos.areas', ['CORTE', 'COSTURA', 'CONTROL_DE_CALIDAD', 'EMPAQUE']);
            $areasInvalidas = array_diff($filtros['areas'], $areasValidas);
            if (!empty($areasInvalidas)) {
                $errores[] = 'Áreas inválidas: ' . implode(', ', $areasInvalidas);
            }
        }

        // Validar número de página
        if (isset($filtros['page']) && $filtros['page'] < 1) {
            $errores[] = 'El número de página debe ser mayor a 0';
        }

        // Validar cantidad por página
        if (isset($filtros['per_page']) && ($filtros['per_page'] < 1 || $filtros['per_page'] > 500)) {
            $errores[] = 'La cantidad por página debe estar entre 1 y 500';
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores
        ];
    }

    /**
     * Aplicar filtros a una query
     * 
     * @param Builder $query
     * @param array $filtros
     * @return Builder Query filtrada
     */
    public function aplicar(Builder $query, array $filtros): Builder
    {
        // Filtrar por tipo de recibo
        if (!empty($filtros['tipo_recibo'])) {
            $query->whereIn('tipo_recibo', (array)$filtros['tipo_recibo']);
        }

        // Filtrar por áreas
        if (!empty($filtros['areas'])) {
            $query->whereIn('area', (array)$filtros['areas']);
        }

        // Filtrar por estado
        if (!empty($filtros['estado'])) {
            $query->whereIn('estado', (array)$filtros['estado']);
        }

        // Filtrar por clientes (a través de relación con pedido)
        if (!empty($filtros['clientes'])) {
            $clienteIds = (array)$filtros['clientes'];
            $query->whereHas('pedido.cliente', function($q) use ($clienteIds) {
                $q->whereIn('id', $clienteIds);
            });
        }

        // Filtrar por descripción (nombre de prenda)
        if (!empty($filtros['descripcion'])) {
            $descripcion = $filtros['descripcion'];
            $query->whereHas('prenda', function($q) use ($descripcion) {
                $q->where('nombre', 'like', '%' . $descripcion . '%');
            });
        }

        // Filtrar por número de recibo (consecutivo_actual)
        if (!empty($filtros['numero_recibo'])) {
            $query->where('consecutivo_actual', 'like', '%' . $filtros['numero_recibo'] . '%');
        }

        // Filtrar por rango de fechas
        if (!empty($filtros['fecha_desde'])) {
            $query->whereDate('created_at', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['fecha_hasta']);
        }

        // Ordenamiento
        $sortBy = $filtros['sort_by'] ?? 'created_at';
        $sortDir = strtolower($filtros['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        
        $columnasValidas = ['area', 'estado', 'tipo_recibo', 'consecutivo_actual', 'created_at'];
        if (in_array($sortBy, $columnasValidas)) {
            $query->orderBy($sortBy, $sortDir);
        }

        return $query;
    }

    /**
     * Obtener opciones de filtro dinámicas desde la BD
     * 
     * @return array Opciones disponibles
     */
    public function obtenerOpciones(): array
    {
        return [
            'tipo_recibo' => ConsecutivoReciboPedido::distinct('tipo_recibo')
                ->pluck('tipo_recibo')
                ->filter()
                ->toArray(),
            'areas' => ConsecutivoReciboPedido::distinct('area')
                ->pluck('area')
                ->filter()
                ->toArray(),
            'estados' => ConsecutivoReciboPedido::distinct('estado')
                ->pluck('estado')
                ->filter()
                ->toArray(),
            'clientes' => Cliente::pluck('nombre', 'id')->toArray(),
        ];
    }
}
