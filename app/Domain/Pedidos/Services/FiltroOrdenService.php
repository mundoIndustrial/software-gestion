<?php

namespace App\Domain\Pedidos\Services;

use App\Models\PedidoProduccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Domain Service: FiltroOrdenService
 * 
 * Responsabilidad: Aplicar filtros complejos a consultas de órdenes
 * Patrón: Domain Service (Orquesta repositorio + lógica de dominio)
 * 
 * Filtra por:
 * - Estado (simple)
 * - Número de recibo (búsqueda)
 * - Días totales (rango)
 * - Cliente (relacional)
 * - Día de entrega (relacional)
 * - Fecha creación (rango)
 * - Fecha estimada (rango)
 * - Descripción (búsqueda)
 * - Cantidad (rango)
 * - Novedades (búsqueda)
 * - Encargado (búsqueda)
 */
class FiltroOrdenService
{
    /**
     * Aplicar filtros a consulta de órdenes
     * 
     * @param array $filtros Filtros a aplicar
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function aplicarFiltros(array $filtros): \Illuminate\Database\Eloquent\Builder
    {
        $query = PedidoProduccion::query();

        // ✅ Filtro por estado
        if (isset($filtros['estado']) && !empty($filtros['estado'])) {
            $query->whereIn('estado', (array) $filtros['estado']);
        }

        // ✅ Filtro por número de recibo
        if (isset($filtros['numero_recibo']) && !empty($filtros['numero_recibo'])) {
            $numeros = is_array($filtros['numero_recibo']) 
                ? $filtros['numero_recibo'] 
                : [$filtros['numero_recibo']];
            $query->whereIn('numero_recibo', array_filter($numeros, fn($n) => is_numeric($n)));
        }

        // ✅ Filtro por cliente (relacional)
        if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
            $clientes = (array) $filtros['cliente'];
            $query->whereIn('cliente', $clientes);
        }

        // ✅ Filtro por día de entrega (comparación)
        if (isset($filtros['dia_entrega']) && !empty($filtros['dia_entrega'])) {
            $dias = (array) $filtros['dia_entrega'];
            $query->whereIn('dia_de_entrega', array_filter($dias, fn($d) => is_numeric($d)));
        }

        // ✅ Filtro por fecha creación (rango)
        if (isset($filtros['fecha_creacion']) && is_array($filtros['fecha_creacion']) && 
            count($filtros['fecha_creacion']) >= 2) {
            [$desde, $hasta] = [$filtros['fecha_creacion'][0], $filtros['fecha_creacion'][1]];
            if ($desde) $query->whereDate('created_at', '>=', $desde);
            if ($hasta) $query->whereDate('created_at', '<=', $hasta);
        }

        // ✅ Filtro por fecha estimada (rango)
        if (isset($filtros['fecha_estimada']) && is_array($filtros['fecha_estimada']) && 
            count($filtros['fecha_estimada']) >= 2) {
            [$desde, $hasta] = [$filtros['fecha_estimada'][0], $filtros['fecha_estimada'][1]];
            if ($desde) $query->whereDate('fecha_estimada_de_entrega', '>=', $desde);
            if ($hasta) $query->whereDate('fecha_estimada_de_entrega', '<=', $hasta);
        }

        // ✅ Filtro por descripción (búsqueda LIKE)
        if (isset($filtros['descripcion']) && !empty($filtros['descripcion'])) {
            $query->where('descripcion', 'LIKE', '%' . $filtros['descripcion'] . '%');
        }

        // ✅ Filtro por cantidad (rango)
        if (isset($filtros['cantidad']) && is_array($filtros['cantidad']) && 
            count($filtros['cantidad']) >= 2) {
            [$desde, $hasta] = [$filtros['cantidad'][0], $filtros['cantidad'][1]];
            
            // Usar subquery para suma de cantidades en prendas
            if ($desde) {
                $query->whereHas('prendas', function($q) use ($desde) {
                    $q->selectRaw('grupo_id')
                      ->groupBy('grupo_id')
                      ->havingRaw('SUM(cantidad) >= ?', [$desde]);
                });
            }
            if ($hasta) {
                $query->whereHas('prendas', function($q) use ($hasta) {
                    $q->selectRaw('grupo_id')
                      ->groupBy('grupo_id')
                      ->havingRaw('SUM(cantidad) <= ?', [$hasta]);
                });
            }
        }

        // ✅ Filtro por novedades (búsqueda LIKE)
        if (isset($filtros['novedades']) && !empty($filtros['novedades'])) {
            $query->where('novedades', 'LIKE', '%' . $filtros['novedades'] . '%');
        }

        // ✅ Filtro por encargado
        if (isset($filtros['encargado']) && !empty($filtros['encargado'])) {
            $query->where('encargado_orden', $filtros['encargado']);
        }

        // ✅ SEGURIDAD: Excluir pedidos sin número
        $query->whereNotNull('numero_pedido');

        return $query;
    }

    /**
     * Obtener todas las opciones disponibles para filtros
     */
    public function obtenerOpcionesGenerales(): array
    {
        try {
            return [
                'estados' => PedidoProduccion::ESTADOS ?? [],
                'areas' => PedidoProduccion::distinct()
                    ->pluck('area')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray(),
                'clientes' => PedidoProduccion::distinct()
                    ->pluck('cliente')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray(),
                'asesores' => PedidoProduccion::with('asesora')
                    ->get()
                    ->pluck('asesora.name')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->toArray(),
                'formas_pago' => PedidoProduccion::distinct()
                    ->pluck('forma_de_pago')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray(),
                'encargados' => PedidoProduccion::distinct()
                    ->pluck('encargado_orden')
                    ->filter()
                    ->sort()
                    ->values()
                    ->toArray(),
                'dias_entrega' => $this->obtenerDiasEntregaDisponibles(),
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo opciones generales: ' . $e->getMessage());
            return [
                'estados' => [],
                'areas' => [],
                'clientes' => [],
                'asesores' => [],
                'formas_pago' => [],
                'encargados' => [],
                'dias_entrega' => [],
            ];
        }
    }

    /**
     * Obtener opciones de una columna específica con búsqueda y paginación
     */
    public function obtenerOpcionesColumna(
        string $columna,
        string $busqueda = '',
        int $pagina = 1,
        int $limite = 25
    ): array {
        try {
            $opciones = [];

            switch ($columna) {
                case 'estado':
                    $opciones = PedidoProduccion::ESTADOS ?? [];
                    break;

                case 'area':
                    $opciones = PedidoProduccion::distinct()
                        ->pluck('area')
                        ->filter()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;

                case 'cliente':
                    $opciones = PedidoProduccion::distinct()
                        ->pluck('cliente')
                        ->filter()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;

                case 'asesora':
                    $opciones = PedidoProduccion::with('asesora')
                        ->get()
                        ->pluck('asesora.name')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;

                case 'forma_pago':
                    $opciones = PedidoProduccion::distinct()
                        ->pluck('forma_de_pago')
                        ->filter()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;

                case 'encargado_orden':
                    $opciones = PedidoProduccion::distinct()
                        ->pluck('encargado_orden')
                        ->filter()
                        ->sort()
                        ->values()
                        ->toArray();
                    break;

                case 'dia_entrega':
                    $opciones = $this->obtenerDiasEntregaDisponibles();
                    break;
            }

            // 🔍 Filtrar por búsqueda
            if (!empty($busqueda)) {
                $opciones = array_filter($opciones, function($opcion) use ($busqueda) {
                    return stripos((string) $opcion, $busqueda) !== false;
                });
            }

            $total = count($opciones);

            // 📄 Aplicar paginación
            $offset = ($pagina - 1) * $limite;
            $paginatedOptions = array_slice($opciones, $offset, $limite);

            return [
                'total' => $total,
                'page' => $pagina,
                'limit' => $limite,
                'last_page' => ceil($total / $limite),
                'opciones' => array_values($paginatedOptions),
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo opciones de columna: ' . $e->getMessage());
            return [
                'total' => 0,
                'page' => $pagina,
                'limit' => $limite,
                'last_page' => 0,
                'opciones' => [],
            ];
        }
    }

    /**
     * Obtener días de entrega disponibles
     */
    private function obtenerDiasEntregaDisponibles(): array
    {
        try {
            return PedidoProduccion::distinct()
                ->whereNotNull('dia_de_entrega')
                ->pluck('dia_de_entrega')
                ->filter()
                ->sort()
                ->values()
                ->map(fn($d) => intval($d))
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Calcular total de días para una orden (post-filtrado)
     */
    public function calcularTotalDias(PedidoProduccion $orden): ?int
    {
        try {
            if (!$orden->fecha_estimada_de_entrega) {
                return null;
            }

            $created = \Carbon\Carbon::parse($orden->created_at);
            $estimada = \Carbon\Carbon::parse($orden->fecha_estimada_de_entrega);

            return $estimada->diffInDays($created);
        } catch (\Exception $e) {
            Log::warning('Error calculando días: ' . $e->getMessage());
            return null;
        }
    }
}
