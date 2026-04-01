<?php

namespace App\Infrastructure\Insumos\ReadModels;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecibosCosturaReadRepository
{
    public function buildBaseQuery()
    {
        return DB::table('consecutivos_recibos_pedidos')
            ->where('tipo_recibo', 'COSTURA')
            ->where('activo', 1)
            ->join('pedidos_produccion', 'consecutivos_recibos_pedidos.pedido_produccion_id', '=', 'pedidos_produccion.id')
            ->select(
                'consecutivos_recibos_pedidos.*',
                'consecutivos_recibos_pedidos.marcar_plooter',
                'pedidos_produccion.numero_pedido',
                'pedidos_produccion.numero_pedido as numero_pedido_original',
                'pedidos_produccion.cliente',
                'pedidos_produccion.estado as pedido_estado',
                'pedidos_produccion.area as pedido_area',
                'consecutivos_recibos_pedidos.estado as recibo_estado',
                'consecutivos_recibos_pedidos.area as recibo_area',
                'pedidos_produccion.created_at',
                'pedidos_produccion.dia_de_entrega',
                'pedidos_produccion.fecha_estimada_de_entrega'
            )
            ->where(function ($q) {
                // Mostrar recibos que estén en PENDIENTE_INSUMOS (estado del RECIBO, no del pedido)
                $q->where('consecutivos_recibos_pedidos.estado', 'PENDIENTE_INSUMOS')
                    // O también mostrar si el área del pedido contiene ciertos términos
                    ->orWhere('pedidos_produccion.area', 'LIKE', '%Corte%')
                    ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion%orden%')
                    ->orWhere('pedidos_produccion.area', 'LIKE', '%Creacion de orden%');
            })
            // Exclusión general: No mostrar si el pedido está en PENDIENTE_SUPERVISOR
            ->where('pedidos_produccion.estado', '!=', 'PENDIENTE_SUPERVISOR');
    }

    public function applyFilters($query, array $filterColumns = [], array $filterValuesArray = [], array $filterValues = [], string $search = '')
    {
        if (!empty($filterColumns) && !empty($filterValuesArray)) {
            foreach ($filterColumns as $idx => $column) {
                if (!isset($filterValuesArray[$idx])) {
                    continue;
                }

                $filterValue = $filterValuesArray[$idx];
                if ($column === 'estado' && $filterValue === 'Pendiente Insumos') {
                    $filterValue = 'PENDIENTE_INSUMOS';
                }

                $column = match ($column) {
                    'numero_pedido' => 'pedidos_produccion.numero_pedido',
                    'cliente' => 'pedidos_produccion.cliente',
                    'estado' => 'pedidos_produccion.estado',
                    'area' => 'pedidos_produccion.area',
                    'created_at' => 'pedidos_produccion.created_at',
                    default => $column,
                };

                if (in_array($column, ['pedidos_produccion.numero_pedido', 'pedidos_produccion.cliente'], true)) {
                    $query->where($column, 'LIKE', "%{$filterValue}%");
                    continue;
                }

                if ($column === 'pedidos_produccion.created_at') {
                    try {
                        $fecha = Carbon::createFromFormat('d/m/Y', $filterValue);
                        $query->whereDate($column, $fecha->format('Y-m-d'));
                    } catch (\Exception) {
                        \Log::warning("Error al convertir fecha: {$filterValue}");
                    }
                    continue;
                }

                $query->whereIn($column, [$filterValue]);
            }
        } elseif (!empty($filterValues)) {
            $filterValues = array_map(
                fn($value) => $value === 'Pendiente Insumos' ? 'PENDIENTE_INSUMOS' : $value,
                $filterValues
            );
            $query->whereIn('pedidos_produccion.estado', $filterValues);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('consecutivos_recibos_pedidos.consecutivo_actual', 'LIKE', "%{$search}%")
                    ->orWhere('pedidos_produccion.numero_pedido', 'LIKE', "%{$search}%")
                    ->orWhere('pedidos_produccion.cliente', 'LIKE', "%{$search}%");
            });
        }

        return $query;
    }

    public function obtenerMapaParciales($recibos): array
    {
        $parcialIds = $recibos
            ->map(function ($recibo) {
                $notas = isset($recibo->notas) ? (string) $recibo->notas : '';
                if ($notas !== '' && preg_match('/parcial_id:(\d+)/i', $notas, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($parcialIds)) {
            return [];
        }

        return DB::table('pedidos_parciales')
            ->whereNull('deleted_at')
            ->whereIn('id', $parcialIds)
            ->pluck('created_at', 'id')
            ->map(fn($dt) => $dt ? (string) $dt : null)
            ->toArray();
    }
}

