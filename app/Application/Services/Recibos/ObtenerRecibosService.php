<?php

namespace App\Application\Services\Recibos;

use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use App\Infrastructure\Repositories\AsesoresRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class ObtenerRecibosService
{
    protected PedidoProduccionRepository $pedidoProduccionRepository;
    protected AsesoresRepository $asesoresRepository;

    public function __construct(
        PedidoProduccionRepository $pedidoProduccionRepository,
        AsesoresRepository $asesoresRepository
    ) {
        $this->pedidoProduccionRepository = $pedidoProduccionRepository;
        $this->asesoresRepository = $asesoresRepository;
    }

    /**
     * Obtener recibo de un pedido específico
     * 
     * @param int $pedidoId
     * @return array
     * @throws \Exception
     */
    public function obtenerRecibo(int $pedidoId): array
    {
        // Verificar permisos
        if (!$this->esDelAsesor($pedidoId)) {
            throw new \Exception('No tienes permiso para ver este recibo', 403);
        }

        // Obtener datos del recibo
        $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($pedidoId);

        if (empty($datos)) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        // Agregar ancho y metraje a cada prenda
        if (isset($datos['prendas']) && is_array($datos['prendas'])) {
            foreach ($datos['prendas'] as $index => &$prenda) {
                $prendaId = $prenda['id'] ?? $prenda['prenda_pedido_id'] ?? null;
                
                if ($prendaId) {
                    // Buscar ancho y metraje para esta prenda específica
                    $anchoMetraje = \App\Models\PedidoAnchoMetraje::where('pedido_produccion_id', $pedidoId)
                        ->where('prenda_pedido_id', $prendaId)
                        ->first();
                    
                    if ($anchoMetraje) {
                        $prenda['ancho_metraje'] = [
                            'ancho' => $anchoMetraje->ancho,
                            'metraje' => $anchoMetraje->metraje,
                            'prenda_id' => $anchoMetraje->prenda_pedido_id
                        ];
                    } else {
                        $prenda['ancho_metraje'] = null;
                    }
                }
            }
        }

        return $datos;
    }

    public function listarRecibos(array $filtros = []): LengthAwarePaginator
    {
        $pedidos = $this->asesoresRepository->obtenerPedidosProduccion($filtros);

        return $pedidos;
    }

    /**
     * Obtener resumen de un recibo
     */
    public function obtenerResumen(int $pedidoId): array
    {
        $datos = $this->obtenerRecibo($pedidoId);

        $resumen = [
            'numero_pedido' => $datos['numero_pedido'] ?? 'N/A',
            'cliente' => $datos['cliente'] ?? 'Sin especificar',
            'fecha_creacion' => $datos['fecha_creacion'] ?? date('d/m/Y'),
            'total_prendas' => collect($datos['prendas'] ?? [])->count(),
            'total_procesos' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? [])),
            'estado' => $datos['estado'] ?? 'Desconocido',
            'forma_pago' => $datos['forma_pago'] ?? 'No especificado',
        ];

        return $resumen;
    }

    /**
     * Obtener detalles de procesos de una prenda especÃ­fica
     */
    public function obtenerProcesosPrenda(int $pedidoId, int $prendaId): array
    {
        Log::info(' [RECIBO-PROCESOS] Obteniendo procesos', [
            'pedido_id' => $pedidoId,
            'prenda_id' => $prendaId
        ]);

        $datos = $this->obtenerRecibo($pedidoId);
        
        $prendas = collect($datos['prendas'] ?? [])
            ->where('id', $prendaId)
            ->first();

        if (!$prendas) {
            throw new \Exception('Prenda no encontrada en el recibo', 404);
        }

        return [
            'prenda' => $prendas,
            'pedido_numero' => $datos['numero_pedido'],
            'cliente' => $datos['cliente'],
        ];
    }

    /**
     * Obtener estados disponibles para filtro
     */
    public function obtenerEstadosDisponibles(): array
    {
        $estados = $this->asesoresRepository->obtenerEstados();

        return $estados;
    }

    /**
     * Verificar si el pedido pertenece al asesor autenticado
     */
    private function esDelAsesor(int $pedidoId): bool
    {
        return $this->asesoresRepository->esDelAsesor($pedidoId);
    }

    /**
     * Exportar recibo a array para vistas
     */
    public function exportarParaVista(int $pedidoId): array
    {

        $recibo = $this->obtenerRecibo($pedidoId);
        $resumen = $this->obtenerResumen($pedidoId);

        return array_merge($recibo, ['resumen' => $resumen]);
    }
}

