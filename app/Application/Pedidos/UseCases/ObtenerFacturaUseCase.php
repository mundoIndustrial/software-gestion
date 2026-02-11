<?php

namespace App\Application\Pedidos\UseCases;

use App\Application\Pedidos\DTOs\ObtenerFacturaDTO;
use App\Application\Pedidos\Traits\ManejaPedidosUseCase;
use App\Domain\Pedidos\Repositories\PedidoProduccionRepository;
use Illuminate\Support\Facades\Log;

class ObtenerFacturaUseCase
{
    use ManejaPedidosUseCase;

    public function __construct(
        private PedidoProduccionRepository $pedidoProduccionRepository
    ) {}

    public function ejecutar(ObtenerFacturaDTO $dto): array
    {
        \Log::info(' [USECASE-FACTURA] ===== INICIO DE EJECUCIÓN =====', [
            'pedido_id' => $dto->pedidoId,
            'usuario_id' => \Auth::id(),
            'usuario_nombre' => \Auth::user()?->name ?? 'No autenticado',
        ]);
        
        try {
            // Obtener datos completos de factura desde el repositorio
            // Este método incluye procesos, imágenes, telas, fotos, etc.
            \Log::info('[USECASE-FACTURA] Llamando al repositorio para obtener datos', [
                'pedido_id' => $dto->pedidoId
            ]);
            
            $datos = $this->pedidoProduccionRepository->obtenerDatosFactura((int)$dto->pedidoId);
            
            \Log::info('[USECASE-FACTURA] Datos obtenidos correctamente del repositorio', [
                'pedido_id' => $dto->pedidoId,
                'prendas_count' => count($datos['prendas'] ?? []),
                'procesos_total' => collect($datos['prendas'] ?? [])->sum(fn($p) => count($p['procesos'] ?? []))
            ]);
            
            // LOG CRÍTICO: Verificar telas_array en cada prenda
            if (!empty($datos['prendas'])) {
                foreach ($datos['prendas'] as $idx => $prenda) {
                    \Log::debug('[USECASE-FACTURA-TELAS] Prenda ' . $idx . ' verificada', [
                        'prenda_nombre' => $prenda['nombre'] ?? 'N/A',
                        'telas_array_count' => count($prenda['telas_array'] ?? []),
                        'telas_array' => $prenda['telas_array'] ?? [],
                        'tela_simple' => $prenda['tela'] ?? null,
                        'color_simple' => $prenda['color'] ?? null,
                        'ref_simple' => $prenda['ref'] ?? null,
                    ]);
                }
            }
            
            \Log::info(' [USECASE-FACTURA] Retornando datos exitosamente');
            return $datos;
        } catch (\Exception $e) {
            \Log::error(' [USECASE-FACTURA] ERROR EN USECASE', [
                'pedido_id' => $dto->pedidoId,
                'usuario_id' => \Auth::id(),
                'error_mensaje' => $e->getMessage(),
                'error_código' => $e->getCode(),
                'error_clase' => get_class($e),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace_resumido' => substr($e->getTraceAsString(), 0, 500),
            ]);
            throw $e;
        }
    }
}


