<?php

namespace App\Application\Services\Recibos;

use App\Domain\PedidoProduccion\Repositories\PedidoProduccionRepository;
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
        Log::info('📄 [RECIBO] Obteniendo recibo para pedido: ' . $pedidoId);

        // Verificar permisos
        if (!$this->esDelAsesor($pedidoId)) {
            throw new \Exception('No tienes permiso para ver este recibo', 403);
        }

        // Obtener datos del recibo
        $datos = $this->pedidoProduccionRepository->obtenerDatosRecibos($pedidoId);

        if (empty($datos)) {
            throw new \Exception('Pedido no encontrado', 404);
        }

        Log::info(' [RECIBO] Datos obtenidos correctamente', [
            'pedido_id' => $pedidoId,
            'prendas' => count($datos['prendas'] ?? []),
            'procesos' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
        ]);

        return $datos;
    }

    /**
     * Obtener lista de recibos con filtros
     */
    public function listarRecibos(array $filtros = []): LengthAwarePaginator
    {
        Log::info(' [RECIBOS] Listando recibos', ['filtros' => $filtros]);

        $pedidos = $this->asesoresRepository->obtenerPedidosProduccion($filtros);

        Log::info(' [RECIBOS] Listado completado', [
            'cantidad' => $pedidos->count(),
            'total' => $pedidos->total()
        ]);

        return $pedidos;
    }

    /**
     * Obtener resumen de un recibo
     */
    public function obtenerResumen(int $pedidoId): array
    {
        Log::info(' [RECIBO-RESUMEN] Obteniendo resumen para pedido: ' . $pedidoId);

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

        Log::info(' [RECIBO-RESUMEN] Resumen generado', [
            'numero_pedido' => $resumen['numero_pedido'],
            'total_prendas' => $resumen['total_prendas'],
            'total_procesos' => $resumen['total_procesos']
        ]);

        return $resumen;
    }

    /**
     * Obtener detalles de procesos de una prenda específica
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

        Log::info(' [RECIBO-PROCESOS] Procesos obtenidos', [
            'prenda_id' => $prendaId,
            'procesos_count' => count($prendas['procesos'] ?? [])
        ]);

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
        Log::info('🏷️ [RECIBO] Obteniendo estados disponibles');

        $estados = $this->asesoresRepository->obtenerEstados();

        Log::info(' [RECIBO] Estados disponibles', ['count' => count($estados)]);

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
        Log::info('📤 [RECIBO-EXPORT] Exportando para vista: ' . $pedidoId);

        $recibo = $this->obtenerRecibo($pedidoId);
        $resumen = $this->obtenerResumen($pedidoId);

        return array_merge($recibo, ['resumen' => $resumen]);
    }
}
