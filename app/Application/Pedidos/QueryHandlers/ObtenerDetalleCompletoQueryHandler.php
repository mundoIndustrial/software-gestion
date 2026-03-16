<?php

namespace App\Application\Pedidos\QueryHandlers;

use App\Domain\Pedidos\Contracts\PedidoRepository;
use App\Domain\Pedidos\Contracts\ConsecutivosService;
use App\Domain\Pedidos\Contracts\ImagenesEppService;
use App\Application\Pedidos\Contracts\PedidoTransformService;
use App\Application\Pedidos\Contracts\PedidoFilterService;
use App\Application\Pedidos\Contracts\PedidoEnricherService;
use App\Application\Pedidos\DTOs\PedidoDetalleDTO;
use App\Application\Pedidos\UseCases\ObtenerPedidoUseCase;
use Illuminate\Support\Facades\Log;

/**
 * ObtenerDetalleCompletoQueryHandler
 * 
 * Handler para obtencion de detalle completo de pedido
 * Orquesta los servicios del dominio y aplicación
 */
class ObtenerDetalleCompletoQueryHandler
{
    public function __construct(
        private PedidoRepository $pedidoRepository,
        private ConsecutivosService $consecutivosService,
        private ImagenesEppService $imagenesService,
        private PedidoTransformService $transformService,
        private PedidoFilterService $filterService,
        private PedidoEnricherService $enricherService,
        private ObtenerPedidoUseCase $obtenerPedidoUseCase,
    ) {}

    /**
     * Ejecutar query de detalle completo
     * 
     * @throws \DomainException
     */
    public function handle(int $pedidoId, bool $filtrarProcesosPendientes = false): PedidoDetalleDTO
    {
        try {
            // 1. Obtener pedido base
            $pedido = $this->obtenerPedido($pedidoId);

            // 2. Verificar permisos según rol
            $rol = auth()->check() ? auth()->user()->getRoleNames()->first() : null;
            if (!$this->filterService->puedeVerPedido($pedidoId, $rol ?? '')) {
                throw new \DomainException('No tienes permisos para ver este pedido');
            }

            // 3. Obtener datos completos del pedido
            $response = $this->obtenerPedidoUseCase->ejecutar($pedido->id, $filtrarProcesosPendientes);
            $datos = $response->toArray();

            // 4. Transformar procesos y tallas
            if (isset($datos['prendas']) && is_array($datos['prendas'])) {
                $datos['prendas'] = $this->transformService->transformarProcesos($datos['prendas']);
            }

            // 5. Enriquecer con datos adicionales
            $datos = $this->enricherService->enriquecerPrendas($pedido->id, $datos);
            $datos = $this->enricherService->enriquecerEntregas($datos['prendas'] ?? []);
            $datos = $this->enricherService->enriquecerRecibosParciales($pedido->id, $datos['prendas'] ?? []);

            // 6. Enriquecer con EPPs
            if (isset($datos['epps_transformados'])) {
                $datos['epps_transformados'] = $this->enricherService->enriquecerEpps($pedido->id, $datos['epps_transformados']);
            } else {
                $datos['epps_transformados'] = [];
            }

            // 7. Aplicar filtros según rol
            $datos = $this->filterService->aplicarFiltrosPorRol($datos, $rol);

            // 8. Crear y retornar DTO
            return new PedidoDetalleDTO(
                id: $pedido->id,
                numero_pedido: $pedido->numero_pedido,
                cliente: $pedido->cliente,
                prendas: $datos['prendas'] ?? [],
                epps_transformados: $datos['epps_transformados'] ?? [],
                ancho_metraje: $datos['ancho_metraje'] ?? null,
                estado: $pedido->estado,
                fecha_creacion: $datos['fecha_creacion'] ?? date('d/m/Y'),
                fecha_estimada_de_entrega: $pedido->fecha_estimada_de_entrega,
                area: $pedido->area,
                dia_de_entrega: $pedido->dia_de_entrega,
            );

        } catch (\DomainException $e) {
            Log::warning('[ObtenerDetalleCompletoQueryHandler] DomainException', [
                'pedido_id' => $pedidoId,
                'message' => $e->getMessage()
            ]);
            throw $e;

        } catch (\Exception $e) {
            Log::error('[ObtenerDetalleCompletoQueryHandler] Error', [
                'pedido_id' => $pedidoId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \DomainException('Error al obtener detalle del pedido');
        }
    }

    private function obtenerPedido(int $pedidoId)
    {
        $pedido = $this->pedidoRepository->obtenerPorId($pedidoId);
        
        if (!$pedido) {
            $pedido = $this->pedidoRepository->obtenerPorNumero($pedidoId);
        }

        if (!$pedido) {
            throw new \DomainException("Pedido {$pedidoId} no encontrado");
        }

        return $pedido;
    }
}
