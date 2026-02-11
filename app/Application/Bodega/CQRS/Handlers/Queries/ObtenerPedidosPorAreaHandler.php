<?php

namespace App\Application\Bodega\CQRS\Handlers\Queries;

use App\Application\Bodega\CQRS\Queries\QueryInterface;
use App\Application\Bodega\CQRS\Queries\ObtenerPedidosPorAreaQuery;
use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Illuminate\Support\Collection;

/**
 * Handler para la Query ObtenerPedidosPorArea
 * Optimizado para operaciones de lectura con caching
 */
class ObtenerPedidosPorAreaHandler
{
    private PedidoRepositoryInterface $pedidoRepository;

    public function __construct(PedidoRepositoryInterface $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Manejar la query de obtener pedidos por área
     */
    public function handle(ObtenerPedidosPorAreaQuery $query): array
    {
        try {
            // 1. Obtener pedidos del área
            $pedidos = $this->pedidoRepository->findByAreas([$query->getArea()]);

            // 2. Filtrar por estados si se especificaron
            if (!empty($query->getFiltros()['estados'])) {
                $estadosFiltro = $query->getFiltros()['estados'];
                $estadosObjects = array_map(fn($e) => 
                    $e instanceof EstadoPedido ? $e : EstadoPedido::desdeString($e), 
                    $estadosFiltro
                );
                
                $pedidos = $pedidos->filter(function($pedido) use ($estadosObjects) {
                    return in_array($pedido->getEstado()->getValor(), 
                        array_map(fn($e) => $e->getValor(), $estadosObjects));
                });
            }

            // 3. Aplicar filtros adicionales
            $pedidos = $this->aplicarFiltrosAdicionales($pedidos, $query->getFiltros());

            // 4. Calcular estadísticas
            $estadisticas = $this->calcularEstadisticas($pedidos);

            // 5. Preparar datos para la vista
            $pedidosParaVista = $pedidos->map(function($pedido) {
                return [
                    'id' => $pedido->getId(),
                    'numero_pedido' => $pedido->getNumeroPedido(),
                    'cliente' => $pedido->getCliente(),
                    'asesor' => $pedido->getAsesorNombre(),
                    'estado' => $pedido->getEstado()->getValor(),
                    'fecha_pedido' => $pedido->getFechaPedido()?->format('d/m/Y'),
                    'fecha_estimada_entrega' => $pedido->getFechaEstimadaEntrega()?->format('d/m/Y'),
                    'novedades' => $pedido->getNovedades(),
                    'esta_en_retraso' => $pedido->estaEnRetraso(),
                    'dias_retraso' => $pedido->getDiasRetraso(),
                    'puede_ser_entregado' => $pedido->puedeSerEntregado(),
                    'fecha_entrega_real' => $pedido->getFechaEntregaReal()?->format('d/m/Y H:i'),
                ];
            });

            // 6. Paginar resultados
            $total = $pedidosParaVista->count();
            $offset = ($query->getPagina() - 1) * $query->getPorPagina();
            $pedidosPaginados = $pedidosParaVista->slice($offset, $query->getPorPagina())->values();

            // 7. Construir metadata de paginación
            $paginacion = [
                'pagina_actual' => $query->getPagina(),
                'por_pagina' => $query->getPorPagina(),
                'total' => $total,
                'total_paginas' => ceil($total / $query->getPorPagina()),
                'tiene_siguiente' => $query->getPagina() < ceil($total / $query->getPorPagina()),
                'tiene_anterior' => $query->getPagina() > 1,
            ];

            return [
                'success' => true,
                'query_id' => $query->getQueryId(),
                'area' => $query->getArea()->getValor(),
                'pedidos' => $pedidosPaginados->toArray(),
                'paginacion' => $paginacion,
                'estadisticas' => $estadisticas,
                'filtros_aplicados' => $this->obtenerFiltrosAplicados($query->getFiltros()),
                'cache_info' => [
                    'query_id' => $query->getQueryId(),
                    'generado_en' => now()->toDateTimeString()
                ]
            ];

        } catch (\Exception $e) {
            \Log::error("Error en ObtenerPedidosPorAreaHandler: " . $e->getMessage(), [
                'query_id' => $query->getQueryId(),
                'area' => $query->getArea()->getValor(),
                'filtros' => $query->getFiltros(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al obtener los pedidos del área',
                'query_id' => $query->getQueryId(),
                'area' => $query->getArea()->getValor(),
                'error_type' => 'system_error'
            ];
        }
    }

    /**
     * Verificar si puede manejar este tipo de query
     */
    public function canHandle(QueryInterface $query): bool
    {
        return $query instanceof ObtenerPedidosPorAreaQuery;
    }

    /**
     * Aplicar filtros adicionales a la colección
     */
    private function aplicarFiltrosAdicionales(Collection $pedidos, array $filtros): Collection
    {
        // Filtro por cliente
        if (!empty($filtros['cliente'])) {
            $pedidos = $pedidos->filter(function($pedido) use ($filtros) {
                return stripos($pedido->getCliente(), $filtros['cliente']) !== false;
            });
        }

        // Filtro por asesor
        if (!empty($filtros['asesor'])) {
            $pedidos = $pedidos->filter(function($pedido) use ($filtros) {
                $asesorNombre = $pedido->getAsesorNombre();
                return $asesorNombre && stripos($asesorNombre, $filtros['asesor']) !== false;
            });
        }

        // Filtro por número de pedido
        if (!empty($filtros['numero_pedido'])) {
            $pedidos = $pedidos->filter(function($pedido) use ($filtros) {
                return stripos($pedido->getNumeroPedido(), $filtros['numero_pedido']) !== false;
            });
        }

        // Filtro por retraso
        if (isset($filtros['solo_retrasados']) && $filtros['solo_retrasados']) {
            $pedidos = $pedidos->filter(function($pedido) {
                return $pedido->estaEnRetraso();
            });
        }

        return $pedidos;
    }

    /**
     * Calcular estadísticas de los pedidos
     */
    private function calcularEstadisticas(Collection $pedidos): array
    {
        $estadisticas = [
            'total' => $pedidos->count(),
            'por_estado' => [],
            'en_retraso' => 0,
            'entregados' => 0,
            'en_ejecucion' => 0,
            'no_iniciados' => 0,
            'promedio_dias_retraso' => 0
        ];

        $totalRetraso = 0;
        $pedidosConRetraso = 0;

        foreach ($pedidos as $pedido) {
            $estado = $pedido->getEstado()->getValor();
            
            if (!isset($estadisticas['por_estado'][$estado])) {
                $estadisticas['por_estado'][$estado] = 0;
            }
            $estadisticas['por_estado'][$estado]++;

            if ($pedido->estaEnRetraso()) {
                $estadisticas['en_retraso']++;
                $totalRetraso += $pedido->getDiasRetraso();
                $pedidosConRetraso++;
            }

            if ($pedido->getEstado()->esEntregado()) {
                $estadisticas['entregados']++;
            }

            if ($pedido->getEstado()->estaEnEjecucion()) {
                $estadisticas['en_ejecucion']++;
            }

            if ($pedido->getEstado()->estaNoIniciado()) {
                $estadisticas['no_iniciados']++;
            }
        }

        if ($pedidosConRetraso > 0) {
            $estadisticas['promedio_dias_retraso'] = round($totalRetraso / $pedidosConRetraso, 1);
        }

        return $estadisticas;
    }

    /**
     * Obtener resumen de filtros aplicados
     */
    private function obtenerFiltrosAplicados(array $filtros): array
    {
        $aplicados = [];
        
        if (!empty($filtros['cliente'])) {
            $aplicados[] = "Cliente: {$filtros['cliente']}";
        }
        
        if (!empty($filtros['asesor'])) {
            $aplicados[] = "Asesor: {$filtros['asesor']}";
        }
        
        if (!empty($filtros['numero_pedido'])) {
            $aplicados[] = "Pedido: {$filtros['numero_pedido']}";
        }
        
        if (isset($filtros['solo_retrasados']) && $filtros['solo_retrasados']) {
            $aplicados[] = "Solo retrasados";
        }

        if (!empty($filtros['estados'])) {
            $aplicados[] = "Estados: " . implode(', ', $filtros['estados']);
        }

        return $aplicados;
    }
}
