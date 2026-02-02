<?php

namespace App\Services;

use App\Models\PedidoProduccion;
use Illuminate\Database\Eloquent\Builder;

/**
 * Servicio para construcción de queries específicas de RegistroOrden
 * 
 * Extrae toda la lógica de queries complejas del controlador
 * Responsabilidades:
 * - buildBaseQuery(): Query base con selecciones y relaciones
 * - applyRoleFilters(): Filtros por rol de usuario
 * - getUniqueValues(): Valores únicos para dropdowns de filtros
 * - formatDateValues(): Formateo de fechas a d/m/Y
 */
class RegistroOrdenExtendedQueryService
{
    /**
     * Construir query base para PedidoProduccion
     * 
     * Incluye:
     * - Selección de 13 columnas específicas
     * - Eager loading de relaciones necesarias (asesora, prendas)
     * - Relaciones nested (color, tela, tipoManga, tipoBroche de prendas) para descripción dinámica
     * - Filtro: Excluye pedidos con estado 'Pendiente'
     * 
     * @return Builder
     */
    public function buildBaseQuery(): Builder
    {
        return PedidoProduccion::query()
            ->select([
                'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
                'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
                'fecha_estimada_de_entrega', 'asesor_id', 'cliente_id', 'id'
            ])
            ->whereIn('estado', ['Entregado', 'En Ejecución', 'No iniciado', 'Anulada'])
            ->with([
                'asesora:id,name',
                'prendas:id,pedido_produccion_id,nombre_prenda,descripcion',
                'prendas.tallas:prenda_pedido_id,genero,talla,cantidad'
            ])
            ->orderBy('created_at', 'asc');
    }

    /**
     * Aplicar filtros según el rol del usuario
     * 
     * Para supervisores: Aplica filtro por estado "En Ejecución"
     * Para asesores y otros: Sin filtros automáticos
     * 
     * @param Builder $query
     * @param object $user Usuario autenticado
     * @param \Illuminate\Http\Request $request
     * @return Builder
     */
    public function applyRoleFilters(Builder $query, $user, $request): Builder
    {
        // Si es supervisor, filtrar por estado por defecto
        if ($user && $user->role && $user->role->name === 'supervisor') {
            $query->where('estado', 'En Ejecución');
        }

        return $query;
    }

    /**
     * Obtener valores únicos para un dropdown de filtro
     * 
     * Maneja columnas especiales:
     * - asesora: Requiere join a tabla users
     * - descripcion_prendas: Subquery a prendas_pedido
     * - encargado_orden: Subquery a procesos_prenda
     * - Columnas de fecha: Formatea a d/m/Y
     * 
     * @param string $column Nombre de la columna
     * @return array Valores únicos ordenados
     * @throws \InvalidArgumentException Si columna no permitida
     */
    public function getUniqueValues(string $column): array
    {
        $allowedColumns = [
            'numero_pedido', 'estado', 'area', 'cliente', 'forma_de_pago',
            'novedades', 'dia_de_entrega', 'fecha_de_creacion_de_orden',
            'fecha_estimada_de_entrega', 'descripcion_prendas', 'asesora', 'encargado_orden'
        ];

        if (!in_array($column, $allowedColumns)) {
            throw new \InvalidArgumentException("Columna '{$column}' no permitida");
        }

        $dateColumns = [
            'fecha_de_creacion_de_orden', 'fecha_estimada_de_entrega', 'inventario',
            'insumos_y_telas', 'corte', 'bordado', 'estampado', 'costura', 'reflectivo',
            'lavanderia', 'arreglos', 'marras', 'control_de_calidad', 'entrega'
        ];

        $values = [];

        // Manejar caso especial: asesora
        if ($column === 'asesora') {
            $values = PedidoProduccion::join('users', 'pedidos_produccion.asesor_id', '=', 'users.id')
                ->distinct()
                ->pluck('users.name')
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        }
        // Manejar caso especial: descripcion_prendas
        elseif ($column === 'descripcion_prendas') {
            $values = \DB::table('prendas_pedido')
                ->distinct()
                ->pluck('descripcion')
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        }
        // Manejar caso especial: encargado_orden
        elseif ($column === 'encargado_orden') {
            $values = \DB::table('procesos_prenda')
                ->where('proceso', 'Creación de Orden')
                ->distinct()
                ->pluck('encargado')
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        }
        // Manejo estándar para columnas de fecha
        elseif (in_array($column, $dateColumns)) {
            $uniqueValues = PedidoProduccion::distinct()
                ->pluck($column)
                ->filter()
                ->values()
                ->toArray();

            $values = array_map(function ($value) {
                try {
                    if (!empty($value)) {
                        $date = \Carbon\Carbon::parse($value);
                        return $date->format('d/m/Y');
                    }
                } catch (\Exception $e) {
                    // Si no se puede parsear, devolver valor original
                }
                return $value;
            }, $uniqueValues);

            // Eliminar duplicados y reindexar
            $values = array_values(array_unique($values));
        }
        // Manejo estándar para otras columnas
        else {
            $values = PedidoProduccion::distinct()
                ->pluck($column)
                ->filter()
                ->sort()
                ->values()
                ->toArray();
        }

        return $values;
    }

    /**
     * Formatear array de valores de fecha a formato d/m/Y
     * 
     * @param array $values Valores a formatear
     * @return array Valores formateados
     */
    public function formatDateValues(array $values): array
    {
        return array_map(function ($value) {
            try {
                if (!empty($value)) {
                    $date = \Carbon\Carbon::parse($value);
                    return $date->format('d/m/Y');
                }
            } catch (\Exception $e) {
                // Si no se puede parsear, devolver valor original
            }
            return $value;
        }, $values);
    }
}
