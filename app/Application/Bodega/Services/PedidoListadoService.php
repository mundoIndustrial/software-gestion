<?php

namespace App\Application\Bodega\Services;

use App\Models\BodegaDetalleTalla;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PedidoListadoService
{
    private const ITEMS_PER_PAGE = 15;

    public function obtenerPendientesPorArea(Request $request, string $area): array
    {
        \Log::info('[PendientesPorArea] Request recibido', [
            'area' => $area,
            'full_url' => $request->fullUrl(),
            'query' => $request->query(),
            'user_id' => auth()->id(),
        ]);

        $query = $this->construirQueryBase($area);
        $query = $this->aplicarBusqueda($query, $request, $area);
        $query = $this->aplicarFiltros($query, $request, $filtrosAplicados);
        $query = $this->aplicarRetrasados($query, $request);

        $paginaSolicitada = max(1, (int) $request->get('page', 1));
        $paginador = $query
            ->orderBy(\DB::raw('CAST(bodega_detalles_talla.numero_pedido AS UNSIGNED)'), 'desc')
            ->paginate(self::ITEMS_PER_PAGE, ['*'], 'page', $paginaSolicitada);

        $totalPedidos = (int) $paginador->total();
        $paginaActual = (int) $paginador->currentPage();
        $pedidosFormateados = $this->formatearResultados(collect($paginador->items()), $area);

        \Log::info('[PendientesPorArea] Resultado', [
            'area' => $area,
            'total_pedidos' => $totalPedidos,
            'pagina_actual' => $paginaActual,
            'por_pagina' => self::ITEMS_PER_PAGE,
            'filtros_aplicados' => $filtrosAplicados ?? [],
            'search' => $request->query('search', ''),
            'query' => $request->query(),
        ]);

        return [
            'pedidosPorPagina' => $pedidosFormateados,
            'totalPedidos' => $totalPedidos,
            'paginaActual' => $paginaActual,
            'porPagina' => self::ITEMS_PER_PAGE,
            'search' => $request->query('search', ''),
            'estadisticas' => [],
            'area' => $area,
            'filtros_aplicados' => array_merge([
                'search' => $request->query('search', ''),
                'retrasados' => $request->boolean('retrasados', false),
            ], $filtrosAplicados ?? []),
            'paginacion_info' => $this->generarPaginacionInfo($totalPedidos, $paginaActual),
            'viewName' => $area === 'Costura' ? 'bodega.pendiente-costura' : 'bodega.pendiente-epp',
        ];
    }

    private function construirQueryBase(string $area)
    {
        return BodegaDetalleTalla::query()
            ->porArea($area)
            ->porEstado('Pendiente')
            ->leftJoin('pedidos_produccion as pp', 'pp.numero_pedido', '=', 'bodega_detalles_talla.numero_pedido')
            ->leftJoin('bodega_detalles_visto as bdv', function($join) {
                $join->on('bdv.bodega_detalle_id', '=', 'bodega_detalles_talla.id')
                     ->where('bdv.user_id', '=', auth()->id());
            })
            ->whereNotNull('bodega_detalles_talla.numero_pedido')
            ->where('bodega_detalles_talla.numero_pedido', '!=', '')
            ->whereNotIn('bodega_detalles_talla.numero_pedido', function($subquery) {
                $subquery->select('numero_pedido')
                    ->from('pedidos_produccion')
                    ->where('estado', 'Anulada')
                    ->whereNotNull('numero_pedido');
            })
            ->whereNotIn('bodega_detalles_talla.numero_pedido', function($subquery) {
                $subquery->select('numero_pedido')
                    ->from('pedidos_produccion')
                    ->where('estado', 'Entregado')
                    ->whereNotNull('numero_pedido');
            })
            ->whereNotIn('bodega_detalles_talla.estado_bodega', ['Entregado', 'Anulado'])
            ->select([
                'bodega_detalles_talla.numero_pedido',
                \DB::raw('MIN(bodega_detalles_talla.id) as id'),
                \DB::raw('MIN(pp.id) as pedido_produccion_id'),
                \DB::raw('MIN(bodega_detalles_talla.empresa) as empresa'),
                \DB::raw('MIN(bodega_detalles_talla.asesor) as asesor'),
                \DB::raw('MIN(bodega_detalles_talla.prenda_nombre) as prenda_nombre'),
                \DB::raw('MIN(bodega_detalles_talla.area) as area'),
                \DB::raw('MIN(bodega_detalles_talla.estado_bodega) as estado_bodega'),
                \DB::raw('MIN(bodega_detalles_talla.fecha_pedido) as fecha_pedido'),
                \DB::raw('MIN(bodega_detalles_talla.fecha_entrega) as fecha_entrega'),
                \DB::raw('MIN(bodega_detalles_talla.observaciones_bodega) as observaciones_bodega'),
                \DB::raw('MIN(bodega_detalles_talla.usuario_bodega_nombre) as usuario_bodega_nombre'),
                \DB::raw('MIN(bodega_detalles_talla.created_at) as created_at'),
                \DB::raw('MIN(bodega_detalles_talla.updated_at) as updated_at'),
                \DB::raw('MAX(bodega_detalles_talla.updated_at) as ultima_actualizacion_at'),
                \DB::raw('SUM(bodega_detalles_talla.cantidad) as cantidad_total'),
                \DB::raw('SUM(bodega_detalles_talla.pendientes) as pendientes_total'),
                \DB::raw('MIN(bodega_detalles_talla.talla) as talla_ejemplo'),
                \DB::raw('MAX(CASE WHEN bdv.id IS NOT NULL THEN 1 ELSE 0 END) as visto_exists'),
                \DB::raw('MAX(bdv.created_at) as ultimo_visto_at')
            ])
            ->groupBy('bodega_detalles_talla.numero_pedido');
    }

    private function aplicarBusqueda($query, Request $request, string $area)
    {
        if (!$request->filled('search')) {
            return $query;
        }

        $search = $request->get('search');
        return $query->where(function($q) use ($search, $area) {
            $q->where('bodega_detalles_talla.numero_pedido', 'LIKE', "%{$search}%")
              ->orWhere('bodega_detalles_talla.empresa', 'LIKE', "%{$search}%")
              ->orWhere('bodega_detalles_talla.asesor', 'LIKE', "%{$search}%")
              ->orWhere('bodega_detalles_talla.prenda_nombre', 'LIKE', "%{$search}%");

            if ($area === 'EPP') {
                $q->orWhere('bodega_detalles_talla.talla', 'LIKE', "%{$search}%");
            }
        });
    }

    private function aplicarFiltros($query, Request $request, &$filtrosAplicados)
    {
        $filtrosAplicados = [];

        if ($request->filled('numero_pedido')) {
            $numerosPedido = explode(',', $request->get('numero_pedido'));
            $query->whereIn('bodega_detalles_talla.numero_pedido', $numerosPedido);
            $filtrosAplicados['numero_pedido'] = $numerosPedido;
        }

        if ($request->filled('cliente')) {
            $clientes = explode(',', $request->get('cliente'));
            $query->whereIn('bodega_detalles_talla.empresa', $clientes);
            $filtrosAplicados['cliente'] = $clientes;
        }

        if ($request->filled('asesor')) {
            $asesores = explode(',', $request->get('asesor'));
            $query->whereIn('bodega_detalles_talla.asesor', $asesores);
            $filtrosAplicados['asesor'] = $asesores;
        }

        if ($request->filled('estado')) {
            $estados = explode(',', $request->get('estado'));
            $query->whereIn('bodega_detalles_talla.estado_bodega', $estados);
            $filtrosAplicados['estado'] = $estados;
        }

        if ($request->filled('fecha_creacion')) {
            $query = $this->aplicarFiltroFechas($query, $request->get('fecha_creacion'), 'bodega_detalles_talla.created_at');
            $filtrosAplicados['fecha_creacion'] = explode(',', $request->get('fecha_creacion'));
        }

        if ($request->filled('fecha_entrega')) {
            $query = $this->aplicarFiltroFechas($query, $request->get('fecha_entrega'), 'bodega_detalles_talla.fecha_entrega');
            $filtrosAplicados['fecha_entrega'] = explode(',', $request->get('fecha_entrega'));
        }

        return $query;
    }

    private function aplicarFiltroFechas($query, string $fechasParam, string $campo)
    {
        $fechas = explode(',', $fechasParam);
        return $query->where(function($q) use ($fechas, $campo) {
            foreach ($fechas as $index => $fecha) {
                $fechaDecodificada = urldecode(trim($fecha));

                try {
                    $fechaFormateada = Carbon::createFromFormat('d/m/Y', $fechaDecodificada)->format('Y-m-d');

                    if ($index === 0) {
                        $q->whereDate($campo, $fechaFormateada);
                    } else {
                        $q->orWhereDate($campo, $fechaFormateada);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error al procesar fecha '{$fechaDecodificada}': " . $e->getMessage());
                    continue;
                }
            }
        });
    }

    private function aplicarRetrasados($query, Request $request)
    {
        if ($request->boolean('retrasados', false)) {
            $query->retrasados();
        }

        return $query;
    }

    private function obtenerTotal($query): int
    {
        $queryParaContar = clone $query;
        return $queryParaContar->count();
    }

    private function obtenerPaginados($query, int $paginaActual)
    {
        return $query
            ->orderBy(\DB::raw('CAST(bodega_detalles_talla.numero_pedido AS UNSIGNED)'), 'desc')
            ->skip(($paginaActual - 1) * self::ITEMS_PER_PAGE)
            ->take(self::ITEMS_PER_PAGE)
            ->get();
    }

    private function formatearResultados($pedidos, string $area): array
    {
        return $pedidos->map(function($detalle) use ($area) {
            $datos = [
                'id' => $detalle->id,
                'pedido_produccion_id' => $detalle->pedido_produccion_id,
                'numero_pedido' => $detalle->numero_pedido,
                'cliente' => $detalle->empresa,
                'asesor' => $this->extraerAsesor($detalle->asesor),
                'estado' => $detalle->estado_bodega,
                'area' => $detalle->area,
                'prenda' => $detalle->prenda_nombre,
                'talla' => $detalle->talla_ejemplo,
                'cantidad' => $detalle->cantidad_total,
                'pendientes' => $detalle->pendientes_total,
                'observaciones' => $detalle->observaciones_bodega,
                'fecha_pedido' => $detalle->fecha_pedido,
                'fecha_entrega' => $detalle->fecha_entrega,
                'usuario_bodega' => $detalle->usuario_bodega_nombre,
                'created_at' => $detalle->created_at,
                'updated_at' => $detalle->updated_at,
                'tiene_pendientes' => $detalle->pendientes_total > 0,
                'esta_retrasado' => $detalle->fecha_entrega && $detalle->fecha_entrega < now(),
            ];

            $datos['visto_exists'] = $this->determinarVisto(
                (bool) $detalle->visto_exists,
                $detalle->ultimo_visto_at,
                $detalle->ultima_actualizacion_at
            );

            if ($area === 'EPP') {
                $datos['visto'] = $datos['visto_exists'];
            }

            return $datos;
        })->toArray();
    }

    private function extraerAsesor($asesor): string
    {
        if (is_string($asesor)) {
            return $asesor;
        }

        if (is_array($asesor) && isset($asesor['name'])) {
            return $asesor['name'];
        }

        if (is_object($asesor) && isset($asesor->name)) {
            return $asesor->name;
        }

        return 'No especificado';
    }

    private function determinarVisto(bool $tieneVistoHistorico, $ultimoVistoAt, $ultimaActualizacionAt): bool
    {
        if (!$tieneVistoHistorico) {
            return false;
        }

        $ultimoVistoTimestamp = $ultimoVistoAt ? strtotime((string) $ultimoVistoAt) : null;
        $ultimaActualizacionTimestamp = $ultimaActualizacionAt ? strtotime((string) $ultimaActualizacionAt) : null;

        if ($ultimoVistoTimestamp === null || $ultimaActualizacionTimestamp === null) {
            return $tieneVistoHistorico;
        }

        return $ultimoVistoTimestamp >= $ultimaActualizacionTimestamp;
    }

    private function generarPaginacionInfo(int $total, int $paginaActual): array
    {
        return [
            'pagina_actual' => $paginaActual,
            'total_paginas' => ceil($total / self::ITEMS_PER_PAGE),
            'total' => $total,
            'por_pagina' => self::ITEMS_PER_PAGE,
            'desde' => ($paginaActual - 1) * self::ITEMS_PER_PAGE + 1,
            'hasta' => min($paginaActual * self::ITEMS_PER_PAGE, $total),
        ];
    }
}
