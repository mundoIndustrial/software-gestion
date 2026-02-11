<?php

namespace App\Application\Bodega\UseCases;

use App\Domain\Bodega\Repositories\PedidoRepositoryInterface;
use App\Domain\Bodega\ValueObjects\AreaBodega;
use App\Domain\Bodega\ValueObjects\EstadoPedido;
use Illuminate\Support\Collection;

/**
 * Use Case para listar pedidos por área
 * Orquesta la obtención de pedidos filtrados por área y estado
 */
class ListarPedidosPorAreaUseCase
{
    private PedidoRepositoryInterface $pedidoRepository;

    public function __construct(PedidoRepositoryInterface $pedidoRepository)
    {
        $this->pedidoRepository = $pedidoRepository;
    }

    /**
     * Ejecutar la consulta de pedidos por área
     */
    public function execute(AreaBodega $area, array $filtros = []): array
    {
        try {
            // 1. Determinar estados a incluir
            $estados = $this->obtenerEstadosPermitidos($filtros['estados'] ?? null);

            // 2. Obtener pedidos del área
            $pedidos = $this->pedidoRepository->findByAreas([$area]);

            // 3. Filtrar por estados si se especificaron
            if (!empty($estados)) {
                $pedidos = $pedidos->filter(function($pedido) use ($estados) {
                    return in_array($pedido->getEstado()->getValor(), $estados);
                });
            }

            // 4. Aplicar filtros adicionales
            $pedidos = $this->aplicarFiltrosAdicionales($pedidos, $filtros);

            // 5. Preparar datos para la vista
            $pedidosParaVista = $pedidos->map(function($pedido) {
                return [
                    'id' => $pedido->getId(),
                    'numero_pedido' => $pedido->getNumeroPedido(),
                    'cliente' => $pedido->getCliente(),
                    'asesor' => $pedido->getAsesorNombre(),
                    'estado' => $pedido->getEstado()->getValor(),
                    'fecha_pedido' => $pedido->getFechaPedido()?->format('d/m/Y'),
                    'novedades' => $pedido->getNovedades(),
                    'esta_en_retraso' => $pedido->estaEnRetraso(),
                    'dias_retraso' => $pedido->getDiasRetraso(),
                    'puede_ser_entregado' => $pedido->puedeSerEntregado(),
                ];
            });

            // 6. Obtener estadísticas
            $estadisticas = $this->calcularEstadisticas($pedidos);

            return [
                'success' => true,
                'area' => $area->getValor(),
                'pedidos' => $pedidosParaVista->toArray(),
                'total' => $pedidosParaVista->count(),
                'estadisticas' => $estadisticas,
                'filtros_aplicados' => [
                    'area' => $area->getValor(),
                    'estados' => $estados,
                    'otros' => $this->obtenerFiltrosAplicados($filtros)
                ]
            ];

        } catch (\Exception $e) {
            \Log::error("Error en ListarPedidosPorAreaUseCase: " . $e->getMessage(), [
                'area' => $area->getValor(),
                'filtros' => $filtros,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error al obtener los pedidos del área',
                'area' => $area->getValor()
            ];
        }
    }

    /**
     * Obtener estados permitidos para el filtrado
     */
    private function obtenerEstadosPermitidos(?array $estadosFiltro): array
    {
        if ($estadosFiltro) {
            return array_map(fn($e) => $e instanceof EstadoPedido ? $e->getValor() : $e, $estadosFiltro);
        }

        // Estados por defecto para pedidos activos
        return [
            'ENTREGADO',
            'EN EJECUCIÓN', 
            'NO INICIADO',
            'ANULADA',
            'PENDIENTE_SUPERVISOR',
            'PENDIENTE_INSUMOS',
            'DEVUELTO_A_ASESORA'
        ];
    }

    /**
     * Aplicar filtros adicionales a la colección de pedidos
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
            'no_iniciados' => 0
        ];

        foreach ($pedidos as $pedido) {
            $estado = $pedido->getEstado()->getValor();
            
            if (!isset($estadisticas['por_estado'][$estado])) {
                $estadisticas['por_estado'][$estado] = 0;
            }
            $estadisticas['por_estado'][$estado]++;

            if ($pedido->estaEnRetraso()) {
                $estadisticas['en_retraso']++;
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

        return $aplicados;
    }
}
